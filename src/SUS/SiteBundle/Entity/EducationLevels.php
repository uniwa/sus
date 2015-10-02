<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EducationLevels
 *
 * @ORM\Table(name="education_levels", options={"comment":"Λεξικό με τα Επίπεδα Εκπαίδευσης."})
 * @ORM\Entity
 */
class EducationLevels
{
    /**
     * @var integer
     *
     * @ORM\Column(name="education_level_id", type="integer", nullable=false, options={"comment":"Ο Κωδικός ID του Επίπεδου Εκπαίδευσης."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $educationLevelId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, options={"comment":"Το Όνομα του Επίπεδου Εκπαίδευσης."})
     */
    private $name;

    public function getEducationLevelId() {
        return $this->educationLevelId;
    }

    public function setEducationLevelId($educationLevelId) {
        $this->educationLevelId = $educationLevelId;
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
