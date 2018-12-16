<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MunicipalityCommunities
 *
 * @ORM\Table(name="municipality_communities", options={"comment":"Λεξικό με τις Δημοτικές Ενότητες. Η τροφοδότηση των δεδομένων πραγματοποιείται μέσω w/s με το MM."})
 * @ORM\Entity
 */
class MunicipalityCommunities
{
    /**
     * @var integer
     *
     * @ORM\Column(name="municipality_community_id", type="integer", nullable=false, options={"comment":"Ο Κωδικός ID της Δημοτικής Ενότητας"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $municipalityCommunityId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, options={"comment":"Το Όνομα της Δημοτικής Ενότητας"})
     */
    private $name;

    public function getMunicipalityCommunityId() {
        return $this->municipalityCommunityId;
    }

    public function setMunicipalityCommunityId($municipalityCommunityId) {
        $this->municipalityCommunityId = $municipalityCommunityId;
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
