<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;

/**
 * Port of `SUS\AdminBundle\Voter\UserPermissions` (old service id `security.user.permissions`).
 *
 * Despite the old name this is NOT a voter — it is the LDAP helper that decides whether a CAS
 * uid is an "official school account" (physicalDeliveryOfficeName == ΕΠΙΣΗΜΟΣ ΛΟΓΑΡΙΑΣΜΟΣ) and,
 * if so, resolves the unit's ΥΠΑΙΘ registry code (`gsnregistrycode`, matched against
 * `units.mmSyncId`). See docs/port-inventory/security.md §7.
 *
 * Differences from the old code (intentional):
 *  - Zend\Ldap → symfony/ldap (ext-ldap based).
 *  - The uid is LDAP-escaped (the old code interpolated it raw).
 *  - Results are cached per request/process — the old app hit LDAP up to 3× per request.
 * The two Greek exception messages are preserved verbatim (they surface as auth errors).
 */
class UserPermissions
{
    private ?LdapInterface $ldap = null;

    /** @var array<string, string|null> per-request cache of uid → gsnregistrycode|null */
    private array $cache = [];

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $bindDn,
        private readonly string $bindPassword,
    ) {
    }

    /**
     * Returns the unit registry code ("mm id") for an official school account, or null when the
     * account exists in LDAP but is not an official one / has no resolvable unit.
     *
     * @throws \Exception when the uid does not exist in LDAP or is ambiguous (Greek messages,
     *                    identical to the old app — they end up on the auth failure page)
     */
    public function checkPrincipal(string $username): ?string
    {
        if (\array_key_exists($username, $this->cache)) {
            return $this->cache[$username];
        }

        $ldap = $this->getLdap();
        $uid = $ldap->escape($username, '', LdapInterface::ESCAPE_FILTER);

        // 1. find the account by uid
        $uidRows = $ldap->query('ou=people,dc=sch,dc=gr', sprintf('(uid=%s)', $uid))->execute()->toArray();

        if (0 === \count($uidRows)) {
            throw new \Exception('Δεν βρέθηκαν δεδομένα για το συγκεκριμένο λογαριασμό στον LDAP με βάση το Uid του λογαριασμού.');
        }
        if (\count($uidRows) > 1) {
            throw new \Exception('Βρέθηκαν πολλαπλοί λογαριασμοί στον LDAP με το ίδιο Uid.');
        }

        // 2. must be an official school account
        $officeName = $this->firstAttribute($uidRows[0], 'physicaldeliveryofficename');
        if (null === $officeName || 'ΕΠΙΣΗΜΟΣ ΛΟΓΑΡΙΑΣΜΟΣ' !== $officeName) {
            return $this->cache[$username] = null;
        }

        // 3. resolve the unit entry from the account's `l` attribute,
        //    e.g. "ou=10gym-perist,ou=att-g,ou=units,dc=sch,dc=gr"
        $l = explode(',', (string) $this->firstAttribute($uidRows[0], 'l'));
        if (\count($l) < 2) {
            return $this->cache[$username] = null;
        }
        $baseDn = $l[1].',ou=units,dc=sch,dc=gr';
        $lRows = $ldap->query($baseDn, '('.$l[0].')')->execute()->toArray();

        $mmId = isset($lRows[0]) ? $this->firstAttribute($lRows[0], 'gsnregistrycode') : null;

        return $this->cache[$username] = $mmId;
    }

    private function getLdap(): LdapInterface
    {
        if (null === $this->ldap) {
            $ldap = Ldap::create('ext_ldap', [
                'host' => $this->host,
                'port' => $this->port,
                // sch.gr LDAP speaks plain ldap:// on 389 (old Zend\Ldap defaults)
                'encryption' => 'none',
                'options' => ['protocol_version' => 3, 'referrals' => false],
            ]);
            $ldap->bind($this->bindDn, $this->bindPassword);
            $this->ldap = $ldap;
        }

        return $this->ldap;
    }

    /** Case-insensitive single-value attribute read (Zend\Ldap lowercased all keys). */
    private function firstAttribute(Entry $entry, string $name): ?string
    {
        foreach ($entry->getAttributes() as $key => $values) {
            if (0 === strcasecmp($key, $name)) {
                return isset($values[0]) ? (string) $values[0] : null;
            }
        }

        return null;
    }
}
