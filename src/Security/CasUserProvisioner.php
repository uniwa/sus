<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Loads-or-creates the `Users` row for a CAS uid and persists the resolved role.
 *
 * This merges two pieces of the old app (security.md §10, §15.4):
 *  - `SUS\UserBundle\Model\UserProvider::createUser()` (BeSimple `create_users: true` with
 *    `created_users_roles: [ROLE_USER]`): fake email `<uid>@<uid>.com`, random md5 password.
 *  - The role write-back that `AdminAclVoter::vote()` used to do on EVERY vote (persist+flush of
 *    ROLE_USER1/2/4 from roles.yml, or ROLE_USER3 for LDAP official accounts) now happens once
 *    per login — `Users.roles` keeps flowing exactly as before, without a flush inside a voter.
 *
 * When no role is resolved (valid CAS user that is neither in sus_roles.yaml nor an official
 * LDAP account) the stored roles are left untouched — same as the old app, where the voter then
 * denied every admin action.
 */
class CasUserProvisioner
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function provision(string $username, ?string $resolvedRole): User
    {
        $canonical = $this->canonicalize($username);

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['usernameCanonical' => $canonical]);

        if (null === $user) {
            $user = new User();
            $user->setUsername($username);
            $user->setUsernameCanonical($canonical);
            // fake but unique email — email_canonical is NOT NULL UNIQUE in the read-only schema
            $user->setEmail($username.'@'.$username.'.com');
            $user->setEmailCanonical($canonical.'@'.$canonical.'.com');
            $user->setEnabled(true);
            // never used for authentication; columns are NOT NULL (FOS-era junk, as before)
            $user->setSalt(substr(base_convert(bin2hex(random_bytes(20)), 16, 36), 0, 31));
            $user->setPassword(md5((string) random_int(0, 10000)));
            $user->setRoles(['ROLE_USER']); // created_users_roles parity
        }

        if (null !== $resolvedRole) {
            $user->setRoles([$resolvedRole]);
        }

        $user->setLastLogin(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /** FOS canonicalizer equivalent (lowercasing; CAS uids are ASCII in practice). */
    private function canonicalize(string $value): string
    {
        return mb_strtolower($value);
    }
}
