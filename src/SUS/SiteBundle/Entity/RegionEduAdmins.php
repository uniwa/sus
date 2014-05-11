<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RegionEduAdmins
 *
 * @ORM\Table(name="region_edu_admins")
 * @ORM\Entity
 */
class RegionEduAdmins
{
    /**
     * @var integer
     *
     * @ORM\Column(name="region_edu_admin_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $regionEduAdminId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    public function getRegionEduAdminId() {
        return $this->regionEduAdminId;
    }

    public function setRegionEduAdminId($regionEduAdminId) {
        $this->regionEduAdminId = $regionEduAdminId;
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
