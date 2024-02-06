<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Regions
 *
 * @ORM\Table(name="regions", options={"comment":"Λεξικό με τις Περιφέρειες."})
 * @ORM\Entity
 */
class Regions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="region_id", type="integer", nullable=false, options={"comment":"Ο Κωδικός ID της Περιφέρειας."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $regionId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, options={"comment":"Το Όνομα της Περιφέρειας."})
     */
    private $name;

    public function getRegionId() {
        return $this->regionId;
    }

    public function setRegionId($regionId) {
        $this->regionId = $regionId;
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
