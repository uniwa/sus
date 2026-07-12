<?php

declare(strict_types=1);

namespace App\Admin\Security;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * Sonata security handler that forwards the PLAIN permission attributes
 * (LIST / VIEW / EDIT / CREATE / EXPORT / DELETE / ...) to the Symfony security voters,
 * with the admin itself as the subject when no object is given.
 *
 * This replaces the old `sonata.admin.security.handler.acl` + gutted ACL voter combination:
 * Symfony 7 has no ACL component, and the old AdminAclVoter never actually read ACLs — it
 * decided from roles.yml/LDAP. Its port, App\Security\AdminAclVoter, votes on exactly these
 * plain attributes with a Unit entity or an AdminInterface subject, so the observable
 * permission matrix is unchanged (security.md §9, admins.md §5/§6).
 *
 * (Sonata 4's role handler was not used because it rewrites the attributes to
 * ROLE_SONATA_ADMIN_UNIT_* strings, which the ported voter deliberately does not speak.)
 */
final class SusAdminSecurityHandler implements SecurityHandlerInterface
{
    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public function isGranted(AdminInterface $admin, $attributes, ?object $object = null): bool
    {
        if (!\is_array($attributes)) {
            $attributes = [$attributes];
        }

        try {
            foreach ($attributes as $attribute) {
                if ($this->authorizationChecker->isGranted($attribute, $object ?? $admin)) {
                    return true;
                }
            }
        } catch (AuthenticationCredentialsNotFoundException) {
            return false;
        }

        return false;
    }

    public function getBaseRole(AdminInterface $admin): string
    {
        return \sprintf('ROLE_%s_%%s', str_replace('.', '_', strtoupper($admin->getCode())));
    }

    public function buildSecurityInformation(AdminInterface $admin): array
    {
        return [];
    }

    public function createObjectSecurity(AdminInterface $admin, object $object): void
    {
        // no object security — permissions are decided from the static roles table / LDAP
    }

    public function deleteObjectSecurity(AdminInterface $admin, object $object): void
    {
        // no object security
    }
}
