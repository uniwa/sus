<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EducationLevels
 *
 * @ORM\Table(name="education_levels")
 * @ORM\Entity
 */
class EducationLevels
{
    /**
     * @var integer
     *
     * @ORM\Column(name="education_level_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $educationLevel;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    public function getEducationLevel() {
        return $this->educationLevel;
    }

    public function setEducationLevel($educationLevelId) {
        $this->educationLevel = $educationLevelId;
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
