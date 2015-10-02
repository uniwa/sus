<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Prefectures
 *
 * @ORM\Table(name="prefectures", options={"comment":"Λεξικό με τις Περιφερειακές Ενότητες."})
 * @ORM\Entity
 */
class Prefectures
{
    /**
     * @var integer
     *
     * @ORM\Column(name="prefecture_id", type="integer", nullable=false, options={"comment":"Ο Κωδικός ID της Περιφερειακής Ενότητας."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $prefectureId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, unique=true, options={"comment":"Το Όνομα της Περιφερειακής Ενότητας."})
     */
    private $name;

    public function getPrefectureId() {
        return $this->prefectureId;
    }

    public function setPrefectureId($prefectureId) {
        $this->prefectureId = $prefectureId;
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
