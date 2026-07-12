<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τις Διευθύνσεις Εκπαίδευσης. (old `SUS\SiteBundle\Entity\EduAdmins`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - edu_admin_id:        "Ο Κωδικός ID της Διευθύνσης Εκπαίδευσης."
 *  - name:                "Το Όνομα της Διευθύνσης Εκπαίδευσης."
 *  - region_edu_admin_id: "Ο Κωδικός ID της Περιφέρειας της Διευθύνσης Εκπαίδευσης."
 *
 * `region_edu_admin_id` is intentionally a scalar integer: the real DB has no FK and no
 * index on it (schema is source of truth).
 */
#[ORM\Entity]
#[ORM\Table(name: 'edu_admins')]
class EduAdmin
{
    #[ORM\Id]
    #[ORM\Column(name: 'edu_admin_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $eduAdminId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    #[ORM\Column(name: 'region_edu_admin_id', type: 'integer', nullable: true)]
    private ?int $regionEduAdminId = null;

    public function getEduAdminId(): ?int
    {
        return $this->eduAdminId;
    }

    public function setEduAdminId(?int $eduAdminId): void
    {
        $this->eduAdminId = $eduAdminId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getRegionEduAdminId(): ?int
    {
        return $this->regionEduAdminId;
    }

    public function setRegionEduAdminId(?int $regionEduAdminId): void
    {
        $this->regionEduAdminId = $regionEduAdminId;
    }

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }
}
