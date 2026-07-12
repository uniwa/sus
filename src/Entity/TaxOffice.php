<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τις Δ.Ο.Υ (Εφορία). (old `SUS\SiteBundle\Entity\TaxOffices`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - tax_office_id: "Ο Κωδικός ID της Δ.Ο.Υ (Εφορίας)."
 *  - name:          "Το Όνομα της Δ.Ο.Υ (Εφορίας)."
 */
#[ORM\Entity]
#[ORM\Table(name: 'tax_offices')]
class TaxOffice
{
    #[ORM\Id]
    #[ORM\Column(name: 'tax_office_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $taxOfficeId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getTaxOfficeId(): ?int
    {
        return $this->taxOfficeId;
    }

    public function setTaxOfficeId(?int $taxOfficeId): void
    {
        $this->taxOfficeId = $taxOfficeId;
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
