<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τους Νομικούς Χαρακτήρες Μονάδων. (old `SUS\SiteBundle\Entity\LegalCharacters`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - legal_character_id: "Ο Κωδικός ID του Νομικού Χαρακτήρα Μονάδων."
 *  - name:               "Το Όνομα του Νομικού Χαρακτήρα Μονάδων."
 */
#[ORM\Entity]
#[ORM\Table(name: 'legal_characters')]
class LegalCharacter
{
    #[ORM\Id]
    #[ORM\Column(name: 'legal_character_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $legalCharacterId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getLegalCharacterId(): ?int
    {
        return $this->legalCharacterId;
    }

    public function setLegalCharacterId(?int $legalCharacterId): void
    {
        $this->legalCharacterId = $legalCharacterId;
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
