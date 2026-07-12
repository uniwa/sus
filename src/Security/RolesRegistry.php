<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Yaml\Yaml;

/**
 * Static user→role/permission table, ported verbatim from the old
 * `SUS/UserBundle/Resources/config/roles.yml` (now `config/sus_roles.yaml`).
 *
 * The old app parsed that file on every request in THREE places (PhpCasValidation,
 * AdminAclVoter, UnitAdmin — security.md §8); here it is parsed once and shared.
 *
 * Entry shape (keys are CAS uids):
 *   name: string ("Administrator", "Υπευθυνος ΓΓΔΒΜ", ...)
 *   role: ROLE_USER1|ROLE_USER2|ROLE_USER4
 *   unit_types: list<string> (or [all] / empty ⇒ all for Administrators)
 *   legal_character: list<string>
 *   permissions: list<string> (VIEW, LIST, EXPORT, EDIT, CREATE)
 *   parent_unit / parent_unit_ou: carried along but read nowhere (as in the old app)
 */
final class RolesRegistry
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $roles = null;

    public function __construct(private readonly string $rolesFile)
    {
    }

    public function has(string $username): bool
    {
        return \array_key_exists($username, $this->all());
    }

    /** @return array<string, mixed>|null */
    public function get(string $username): ?array
    {
        return $this->all()[$username] ?? null;
    }

    public function getRole(string $username): ?string
    {
        return $this->get($username)['role'] ?? null;
    }

    /** @return list<string> */
    public function getPermissions(string $username): array
    {
        return $this->get($username)['permissions'] ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        if (null === $this->roles) {
            $this->roles = Yaml::parseFile($this->rolesFile) ?? [];
        }

        return $this->roles;
    }
}
