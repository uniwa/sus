<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τους Δήμους Ο.Τ.Α. (old `SUS\SiteBundle\Entity\Municipalities`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - municipality_id: "Ο Κωδικός ID του Δήμου Ο.Τ.Α."
 *  - name:            "Το Όνομα του Δήμου Ο.Τ.Α."
 *  - prefecture_id:   "Ο Κωδικός ID της Περιφερειακής Ενότητας του Δήμου Ο.Τ.Α."
 *
 * `prefecture_id` is intentionally a scalar integer: the real DB has no FK and no index on it.
 */
#[ORM\Entity]
#[ORM\Table(name: 'municipalities')]
class Municipality
{
    #[ORM\Id]
    #[ORM\Column(name: 'municipality_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $municipalityId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: 'prefecture_id', type: 'integer', nullable: true)]
    private ?int $prefectureId = null;

    public function getMunicipalityId(): ?int
    {
        return $this->municipalityId;
    }

    public function setMunicipalityId(?int $municipalityId): void
    {
        $this->municipalityId = $municipalityId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPrefectureId(): ?int
    {
        return $this->prefectureId;
    }

    public function setPrefectureId(?int $prefectureId): void
    {
        $this->prefectureId = $prefectureId;
    }

    public function __toString(): string
    {
        // name is nullable in the DB — PHP 8 fatals if __toString() returns null (old app tolerated it).
        return $this->getName() ?? '';
    }
}
