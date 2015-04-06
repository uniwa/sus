<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UnitTypes
 *
 * @ORM\Table(name="unit_types")
 * @ORM\Entity
 */
class UnitTypes
{
    /**
     * @var integer
     *
     * @ORM\Column(name="unit_type_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $unitTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", nullable=true)
     */
    private $categoryId;

    /**
     * @var EducationLevels
     *
     * @ORM\ManyToOne(targetEntity="EducationLevels")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="education_level_id", referencedColumnName="education_level_id")
     * })
     */
    private $educationLevel;

    public function getUnitTypeId() {
        return $this->unitTypeId;
    }

    public function setUnitTypeId($unitTypeId) {
        $this->unitTypeId = $unitTypeId;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getCategoryId() {
        return $this->categoryId;
    }

    public function setCategoryId($categoryId) {
        $this->categoryId = $categoryId;
    }

    public function getEducationLevel() {
        return $this->educationLevel;
    }

    public function setEducationLevel(EducationLevels $educationLevel=null) {
        $this->educationLevel = $educationLevel;
    }

    public function __toString() {
        return $this->getName();
    }
}
