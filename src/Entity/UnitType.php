<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τους Τύπους Μονάδων. (old `SUS\SiteBundle\Entity\UnitTypes`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - unit_type_id: "Ο Κωδικός ID του Τύπου Μονάδας."
 *  - name:         "Το Όνομα του Τύπου Μονάδας."
 *
 * DRIFT (entities.md §2.15): the old entity mapped `category_id` as a ManyToOne, but the real
 * DB has NO foreign key and NO index on `unit_types.category_id`. The association is kept for
 * behavior parity (Sonata forms rely on the object); the phantom FK+index Doctrine would
 * otherwise want to create are removed from the computed schema by
 * {@see \App\Doctrine\LegacySchemaListener} so `doctrine:schema:update --dump-sql` stays clean.
 *
 * The old property was misleadingly named `$categoryId` although it held a UnitCategory object;
 * the accessors getCategoryId()/setCategoryId() are kept with the same (object) semantics.
 */
#[ORM\Entity]
#[ORM\Table(name: 'unit_types')]
class UnitType
{
    #[ORM\Id]
    #[ORM\Column(name: 'unit_type_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $unitTypeId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: UnitCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'category_id')]
    private ?UnitCategory $category = null;

    #[ORM\ManyToOne(targetEntity: EducationLevel::class)]
    #[ORM\JoinColumn(name: 'education_level_id', referencedColumnName: 'education_level_id')]
    private ?EducationLevel $educationLevel = null;

    public function getUnitTypeId(): ?int
    {
        return $this->unitTypeId;
    }

    public function setUnitTypeId(?int $unitTypeId): void
    {
        $this->unitTypeId = $unitTypeId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /** Old-API alias: returns the UnitCategory object (the old property was named categoryId). */
    public function getCategoryId(): ?UnitCategory
    {
        return $this->category;
    }

    public function setCategoryId(?UnitCategory $category = null): void
    {
        $this->category = $category;
    }

    public function getCategory(): ?UnitCategory
    {
        return $this->category;
    }

    public function setCategory(?UnitCategory $category = null): void
    {
        $this->category = $category;
    }

    public function getEducationLevel(): ?EducationLevel
    {
        return $this->educationLevel;
    }

    public function setEducationLevel(?EducationLevel $educationLevel = null): void
    {
        $this->educationLevel = $educationLevel;
    }

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }
}
