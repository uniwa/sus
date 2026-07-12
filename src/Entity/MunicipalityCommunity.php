<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Old `SUS\SiteBundle\Entity\MunicipalityCommunities`.
 *
 * The ONLY table whose comments really exist in the DB — they are therefore kept in the
 * mapping `options` so the schema diff stays clean (see docs/port-inventory/entities.md §2.8).
 *
 * DB drift honored here: the old annotation said `name` was NOT NULL, but the real column is
 * `varchar(255) DEFAULT NULL` — mapped nullable (real schema wins). The DB also has a plain
 * index `name` the old annotation never declared.
 */
#[ORM\Entity]
#[ORM\Table(name: 'municipality_communities', options: ['comment' => 'Λεξικό με τις Δημοτικές Ενότητες. Η τροφοδότηση των δεδομένων πραγματοποιείται μέσω w/s με το MM.'])]
#[ORM\Index(name: 'name', columns: ['name'])]
class MunicipalityCommunity
{
    #[ORM\Id]
    #[ORM\Column(name: 'municipality_community_id', type: 'integer', nullable: false, options: ['comment' => 'Ο Κωδικός ID της Δημοτικής Ενότητας'])]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $municipalityCommunityId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true, options: ['comment' => 'Το Όνομα της Δημοτικής Ενότητας'])]
    private ?string $name = null;

    public function getMunicipalityCommunityId(): ?int
    {
        return $this->municipalityCommunityId;
    }

    public function setMunicipalityCommunityId(?int $municipalityCommunityId): void
    {
        $this->municipalityCommunityId = $municipalityCommunityId;
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
        // name is nullable in the DB — guard against PHP 8 __toString() TypeError.
        return $this->getName() ?? '';
    }
}
