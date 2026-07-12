<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τις Περιφέρειες. (old `SUS\SiteBundle\Entity\Regions`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - region_id: "Ο Κωδικός ID της Περιφέρειας."
 *  - name:      "Το Όνομα της Περιφέρειας."
 */
#[ORM\Entity]
#[ORM\Table(name: 'regions')]
class Region
{
    #[ORM\Id]
    #[ORM\Column(name: 'region_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $regionId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    public function setRegionId(?int $regionId): void
    {
        $this->regionId = $regionId;
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
