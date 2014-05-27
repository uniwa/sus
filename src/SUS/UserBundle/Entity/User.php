<?php
namespace SUS\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="Users")
 * @ORM\Entity(repositoryClass="SUS\UserBundle\Entity\Repositories\UserRepository")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     *
     */
    protected $name;

    /**
     * @ORM\OneToOne(targetEntity="SUS\SiteBundle\Entity\Unit")
     * @ORM\JoinColumn(name="mmId", referencedColumnName="unit_id", onDelete="SET NULL")
     */
    protected $unit;

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getUnit() {
        return $this->unit;
    }

    public function setUnit($unit) {
        $this->unit = $unit;
    }

    public function void() {}
}