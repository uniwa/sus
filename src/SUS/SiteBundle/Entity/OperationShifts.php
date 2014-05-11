<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationShifts
 *
 * @ORM\Table(name="operation_shifts")
 * @ORM\Entity
 */
class OperationShifts
{
    /**
     * @var integer
     *
     * @ORM\Column(name="operation_shift_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $operationShiftId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var \UnitCategory
     *
     * @ORM\ManyToOne(targetEntity="UnitCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="category_id")
     * })
     */
    private $category;

    public function getOperationShiftId() {
        return $this->operationShiftId;
    }

    public function setOperationShiftId($operationShiftId) {
        $this->operationShiftId = $operationShiftId;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getCategory() {
        return $this->category;
    }

    public function setCategory(\UnitCategory $category) {
        $this->category = $category;
    }

    public function __toString() {
        return $this->getName();
    }
}
