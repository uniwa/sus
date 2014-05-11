<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Municipalities
 *
 * @ORM\Table(name="municipalities")
 * @ORM\Entity
 */
class Municipalities
{
    /**
     * @var integer
     *
     * @ORM\Column(name="municipality_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $municipalityId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="prefecture_id", type="integer", nullable=true)
     */
    private $prefectureId;

    public function getMunicipalityId() {
        return $this->municipalityId;
    }

    public function setMunicipalityId($municipalityId) {
        $this->municipalityId = $municipalityId;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getPrefectureId() {
        return $this->prefectureId;
    }

    public function setPrefectureId($prefectureId) {
        $this->prefectureId = $prefectureId;
    }

    public function __toString() {
        return $this->getName();
    }
}
