<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ImplementationEntities
 *
 * @ORM\Table(name="implementation_entities", options={"comment":"Λεξικό με τους Φορείς Υλοποίησης."}, uniqueConstraints={@ORM\UniqueConstraint(name="name_UNIQUE", columns={"name"}), @ORM\UniqueConstraint(name="initials_UNIQUE", columns={"initials"})}, indexes={@ORM\Index(name="street_address_idx", columns={"street_address"}), @ORM\Index(name="postal_code_idx", columns={"postal_code"}), @ORM\Index(name="email_idx", columns={"email"}), @ORM\Index(name="phone_number_idx", columns={"phone_number"}), @ORM\Index(name="domain_idx", columns={"domain"}), @ORM\Index(name="url_idx", columns={"url"})})
 * @ORM\Entity
 */
class ImplementationEntities
{
    /**
     * @var integer
     *
     * @ORM\Column(name="implementation_entity_id", type="integer", nullable=false, options={"comment":"Ο Κωδικός ID του Φορέα Υλοποίησης."})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $implementationEntityId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false, options={"comment":"Το Όνομα του Φορέα Υλοποίησης."})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="initials", type="string", length=255, nullable=false, options={"comment":"Τα αρχικά του Ονόματος του Φορέα Υλοποίησης."})
     */
    private $initials;

    /**
     * @var string
     *
     * @ORM\Column(name="street_address", type="string", length=255, nullable=true, options={"comment":"Η Διεύθυνση του Φορέα Υλοποίησης."})
     */
    private $streetAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="postal_code", type="string", length=255, nullable=true, options={"comment":"Ο Ταχυδρομικός Κώδικας του Φορέα Υλοποίησης."})
     */
    private $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true, options={"comment":"Το Ηλεκτρονικό Ταχυδρομείο του Φορέα Υλοποίησης."})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=255, nullable=true, options={"comment":"Ο Τηλεφωνικός Αριθμός του Φορέα Υλοποίησης."})
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=255, nullable=true, options={"comment":"Το Domain του Φορέα Υλοποίησης."})
     */
    private $domain;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true, options={"comment":"Η Διεύθυνση Url του Φορέα Υλοποίησης"})
     */
    private $url;

    public function getImplementationEntityId() {
        return $this->implementationEntityId;
    }

    public function setImplementationEntityId($implementationEntityId) {
        $this->implementationEntityId = $implementationEntityId;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getInitials() {
        return $this->initials;
    }

    public function setInitials($initials) {
        $this->initials = $initials;
    }

    public function getStreetAddress() {
        return $this->streetAddress;
    }

    public function setStreetAddress($streetAddress) {
        $this->streetAddress = $streetAddress;
    }

    public function getPostalCode() {
        return $this->postalCode;
    }

    public function setPostalCode($postalCode) {
        $this->postalCode = $postalCode;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getPhoneNumber() {
        return $this->phoneNumber;
    }

    public function setPhoneNumber($phoneNumber) {
        $this->phoneNumber = $phoneNumber;
    }

    public function getDomain() {
        return $this->domain;
    }

    public function setDomain($domain) {
        $this->domain = $domain;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function __toString() {
        return $this->getName();
    }
}
