<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Unit;
use App\Entity\User;
use Sonata\AdminBundle\Admin\AdminInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Port of `SUS\AdminBundle\Voter\AdminAclVoter` — the real authorization engine of the old app
 * (security.md §9). Decides Sonata admin permissions (LIST/VIEW/EDIT/CREATE/EXPORT/…) on the
 * Unit admin / Unit entities from the sus_roles.yaml permission lists:
 *
 *   ROLE_USER1/2/4 → grant iff the requested permission is in the user's `permissions` list
 *   ROLE_USER3     → grant iff the permission ∈ {VIEW, LIST, EDIT} (hardcoded, as before)
 *   no role        → deny
 *
 * Deliberate deviations from the old voter (per the inventory's port recommendation, §9/§15.6):
 *  - It ABSTAINS instead of blanket-granting when the subject is null or not a Unit/admin
 *    (the old `$object === null → ACCESS_GRANTED` made every subject-less isGranted() pass).
 *  - It never writes to the DB — the role persist/flush side effect moved to login time
 *    (CasUserProvisioner), so roles are refreshed once per login instead of on every vote.
 *  - Sonata 4 uses SHOW where the old AdminPermissionMap used VIEW — both are accepted and
 *    mapped to the yaml permission VIEW.
 */
class AdminAclVoter extends Voter
{
    private const SUPPORTED_ATTRIBUTES = ['LIST', 'VIEW', 'SHOW', 'EDIT', 'CREATE', 'EXPORT', 'DELETE'];
    private const ROLE_USER3_PERMISSIONS = ['VIEW', 'LIST', 'EDIT'];

    public function __construct(private readonly RolesRegistry $rolesRegistry)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array(strtoupper($attribute), self::SUPPORTED_ATTRIBUTES, true)
            && ($subject instanceof Unit || $subject instanceof AdminInterface);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // 'SHOW' (Sonata 4) ≡ 'VIEW' (old AdminPermissionMap / roles.yml lists)
        $permission = strtoupper($attribute);
        if ('SHOW' === $permission) {
            $permission = 'VIEW';
        }

        $username = $token->getUserIdentifier();
        $roles = $user->getRoles();

        // ROLE_USER1/2/4: permissions come from the static roles table
        if ($this->rolesRegistry->has($username)
            && array_intersect(['ROLE_USER1', 'ROLE_USER2', 'ROLE_USER4'], $roles)) {
            return \in_array($permission, $this->rolesRegistry->getPermissions($username), true);
        }

        // ROLE_USER3 (official school accounts): hardcoded VIEW/LIST/EDIT on their own unit
        if (\in_array('ROLE_USER3', $roles, true)) {
            return \in_array($permission, self::ROLE_USER3_PERMISSIONS, true);
        }

        return false;
    }
}
