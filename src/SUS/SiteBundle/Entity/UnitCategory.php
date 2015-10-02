<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UnitCategory
 *
 * @ORM\Table(name="categories", options={"comment":"Λεξικό με τις Κατηγορίες."})
 * @ORM\Entity
 */
class UnitCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", nullable=false, options={"comment":"Ο Κωδικός ID της Κατηγορίας."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $categoryId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, options={"comment":"Το Όνομα της Κατηγορίας."})
     */
    private $name;

    public function getCategoryId() {
        return $this->categoryId;
    }

    public function setCategoryId($categoryId) {
        $this->categoryId = $categoryId;
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
