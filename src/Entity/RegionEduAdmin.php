<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τις Περιφέρειες (Διοίκησης Εκπαίδευσης). (old `SUS\SiteBundle\Entity\RegionEduAdmins`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - region_edu_admin_id: "Ο Κωδικός ID της Περιφέρειας."
 *  - name:                "Το Όνομα της Περιφέρειας."
 */
#[ORM\Entity]
#[ORM\Table(name: 'region_edu_admins')]
class RegionEduAdmin
{
    #[ORM\Id]
    #[ORM\Column(name: 'region_edu_admin_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $regionEduAdminId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getRegionEduAdminId(): ?int
    {
        return $this->regionEduAdminId;
    }

    public function setRegionEduAdminId(?int $regionEduAdminId): void
    {
        $this->regionEduAdminId = $regionEduAdminId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }
}
