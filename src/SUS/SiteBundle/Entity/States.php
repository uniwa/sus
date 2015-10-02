<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * States
 *
 * @ORM\Table(name="states", options={"comment":"Λεξικό με τις Λειτουργικές Καταστάσεις."})
 * @ORM\Entity
 */
class States
{
    /**
     * @var integer
     *
     * @ORM\Column(name="state_id", type="integer", nullable=false, options={"comment":"Ο Κωδικός ID της Λειτουργικής Κατάστασης."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $stateId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, options={"comment":"Το Όνομα της Λειτουργικής Κατάστασης."})
     */
    private $name;

    public function getStateId() {
        return $this->stateId;
    }

    public function setStateId($stateId) {
        $this->stateId = $stateId;
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
