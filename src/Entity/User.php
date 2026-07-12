<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Old `SUS\UserBundle\Entity\User extends FOS\UserBundle\Model\User`, table `Users` (capital U!).
 *
 * FOSUserBundle is dead in Symfony 7, so every column the FOS model used to map is inlined here
 * (entities.md §2.17). Several FOS-era columns (`locked`, `expired`, `credentials_expired`, ...)
 * are unused by the new security system but MUST stay mapped — the DB is read-only and the
 * schema diff must stay clean.
 *
 * `roles` is the legacy PHP-serialized `(DC2Type:array)` type removed in DBAL 4 — mapped with
 * the custom App\Doctrine\Types\LegacyArrayType and an explicit column comment matching the DB.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'Users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'username', type: 'string', length: 255, nullable: false)]
    private ?string $username = null;

    #[ORM\Column(name: 'username_canonical', type: 'string', length: 255, nullable: false, unique: true)]
    private ?string $usernameCanonical = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: false)]
    private ?string $email = null;

    #[ORM\Column(name: 'email_canonical', type: 'string', length: 255, nullable: false, unique: true)]
    private ?string $emailCanonical = null;

    #[ORM\Column(name: 'enabled', type: 'boolean', nullable: false)]
    private bool $enabled = false;

    #[ORM\Column(name: 'salt', type: 'string', length: 255, nullable: true)]
    private ?string $salt = null;

    #[ORM\Column(name: 'password', type: 'string', length: 255, nullable: false)]
    private ?string $password = null;

    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    /** FOS-era leftover, unused by the new security system — kept mapped (read-only DB). */
    #[ORM\Column(name: 'locked', type: 'boolean', nullable: false)]
    private bool $locked = false;

    /** FOS-era leftover, unused — kept mapped. */
    #[ORM\Column(name: 'expired', type: 'boolean', nullable: false)]
    private bool $expired = false;

    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(name: 'confirmation_token', type: 'string', length: 255, nullable: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(name: 'password_requested_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $passwordRequestedAt = null;

    /**
     * PHP-serialized role array, e.g. a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}.
     *
     * @var list<string>
     */
    #[ORM\Column(name: 'roles', type: 'array', nullable: false, options: ['comment' => '(DC2Type:array)'])]
    private array $roles = [];

    /** FOS-era leftover, unused — kept mapped. */
    #[ORM\Column(name: 'credentials_expired', type: 'boolean', nullable: false)]
    private bool $credentialsExpired = false;

    #[ORM\Column(name: 'credentials_expire_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $credentialsExpireAt = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    /** Join column is literally named `mmId` in the DB (camelCase — explicit name required). */
    #[ORM\OneToOne(targetEntity: Unit::class)]
    #[ORM\JoinColumn(name: 'mmId', referencedColumnName: 'unit_id', onDelete: 'SET NULL')]
    private ?Unit $unit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getUsernameCanonical(): ?string
    {
        return $this->usernameCanonical;
    }

    public function setUsernameCanonical(?string $usernameCanonical): void
    {
        $this->usernameCanonical = $usernameCanonical;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmailCanonical(): ?string
    {
        return $this->emailCanonical;
    }

    public function setEmailCanonical(?string $emailCanonical): void
    {
        $this->emailCanonical = $emailCanonical;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt): void
    {
        $this->salt = $salt;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    public function isExpired(): bool
    {
        return $this->expired;
    }

    public function setExpired(bool $expired): void
    {
        $this->expired = $expired;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): void
    {
        $this->confirmationToken = $confirmationToken;
    }

    public function getPasswordRequestedAt(): ?\DateTimeInterface
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(?\DateTimeInterface $passwordRequestedAt): void
    {
        $this->passwordRequestedAt = $passwordRequestedAt;
    }

    /**
     * Like the old FOS model: the stored roles plus the implicit ROLE_USER.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER'; // FOS ROLE_DEFAULT equivalent

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function isCredentialsExpired(): bool
    {
        return $this->credentialsExpired;
    }

    public function setCredentialsExpired(bool $credentialsExpired): void
    {
        $this->credentialsExpired = $credentialsExpired;
    }

    public function getCredentialsExpireAt(): ?\DateTimeInterface
    {
        return $this->credentialsExpireAt;
    }

    public function setCredentialsExpireAt(?\DateTimeInterface $credentialsExpireAt): void
    {
        $this->credentialsExpireAt = $credentialsExpireAt;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit = null): void
    {
        $this->unit = $unit;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function eraseCredentials(): void
    {
        // nothing sensitive kept in memory
    }

    /** Old-API no-op kept for template/admin compatibility. */
    public function void(): void
    {
    }

    public function __toString(): string
    {
        return $this->getUsername() ?? '';
    }
}
