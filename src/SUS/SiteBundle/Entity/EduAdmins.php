<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EduAdmins
 *
 * @ORM\Table(name="edu_admins")
 * @ORM\Entity
 */
class EduAdmins
{
    /**
     * @var integer
     *
     * @ORM\Column(name="edu_admin_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $eduAdminId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="region_edu_admin_id", type="integer", nullable=true)
     */
    private $regionEduAdminId;

    public function getEduAdminId() {
        return $this->eduAdminId;
    }

    public function setEduAdminId($eduAdminId) {
        $this->eduAdminId = $eduAdminId;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getRegionEduAdminId() {
        return $this->regionEduAdminId;
    }

    public function setRegionEduAdminId($regionEduAdminId) {
        $this->regionEduAdminId = $regionEduAdminId;
    }

    public function __toString() {
        return $this->getName();
    }
}
