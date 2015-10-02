<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TaxOffices
 *
 * @ORM\Table(name="tax_offices", options={"comment":"Λεξικό με τις Δ.Ο.Υ (Εφορία)."})
 * @ORM\Entity
 */
class TaxOffices
{
    /**
     * @var integer
     *
     * @ORM\Column(name="tax_office_id", type="integer", nullable=false, options={"comment":"Ο Κωδικός ID της Δ.Ο.Υ (Εφορίας)."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $taxOfficeId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, options={"comment":"Το Όνομα της Δ.Ο.Υ (Εφορίας)."})
     */
    private $name;

    public function getTaxOfficeId() {
        return $this->taxOfficeId;
    }

    public function setTaxOfficeId($taxOfficeId) {
        $this->taxOfficeId = $taxOfficeId;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function __toString() {
        return $this->getName();
    }
}
