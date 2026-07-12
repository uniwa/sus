<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τις Περιφερειακές Ενότητες. (old `SUS\SiteBundle\Entity\Prefectures`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - prefecture_id: "Ο Κωδικός ID της Περιφερειακής Ενότητας."
 *  - name:          "Το Όνομα της Περιφερειακής Ενότητας."
 *
 * The unique key on `name` has the legacy name `name` (not a Doctrine-generated UNIQ_ name),
 * so it is declared explicitly instead of `unique: true`.
 */
#[ORM\Entity]
#[ORM\Table(name: 'prefectures')]
#[ORM\UniqueConstraint(name: 'name', columns: ['name'])]
class Prefecture
{
    #[ORM\Id]
    #[ORM\Column(name: 'prefecture_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $prefectureId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getPrefectureId(): ?int
    {
        return $this->prefectureId;
    }

    public function setPrefectureId(?int $prefectureId): void
    {
        $this->prefectureId = $prefectureId;
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
