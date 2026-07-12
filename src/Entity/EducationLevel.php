<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τα Επίπεδα Εκπαίδευσης. (old `SUS\SiteBundle\Entity\EducationLevels`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - education_level_id: "Ο Κωδικός ID του Επίπεδου Εκπαίδευσης."
 *  - name:               "Το Όνομα του Επίπεδου Εκπαίδευσης."
 */
#[ORM\Entity]
#[ORM\Table(name: 'education_levels')]
class EducationLevel
{
    #[ORM\Id]
    #[ORM\Column(name: 'education_level_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $educationLevelId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getEducationLevelId(): ?int
    {
        return $this->educationLevelId;
    }

    public function setEducationLevelId(?int $educationLevelId): void
    {
        $this->educationLevelId = $educationLevelId;
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
