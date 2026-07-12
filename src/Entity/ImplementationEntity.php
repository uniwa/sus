<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τους Φορείς Υλοποίησης. (old `SUS\SiteBundle\Entity\ImplementationEntities`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - implementation_entity_id: "Ο Κωδικός ID του Φορέα Υλοποίησης."
 *  - name:                     "Το Όνομα του Φορέα Υλοποίησης."
 *  - initials:                 "Τα αρχικά του Ονόματος του Φορέα Υλοποίησης."
 *  - street_address:           "Η Διεύθυνση του Φορέα Υλοποίησης."
 *  - postal_code:              "Ο Ταχυδρομικός Κώδικας του Φορέα Υλοποίησης."
 *  - email:                    "Το Ηλεκτρονικό Ταχυδρομείο του Φορέα Υλοποίησης."
 *  - phone_number:             "Ο Τηλεφωνικός Αριθμός του Φορέα Υλοποίησης."
 *  - domain:                   "Το Domain του Φορέα Υλοποίησης."
 *  - url:                      "Η Διεύθυνση Url του Φορέα Υλοποίησης"
 *
 * Note: `postal_code` is a string here (varchar(255) in DB), unlike `units.postal_code`.
 */
#[ORM\Entity]
#[ORM\Table(name: 'implementation_entities')]
#[ORM\UniqueConstraint(name: 'name_UNIQUE', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'initials_UNIQUE', columns: ['initials'])]
#[ORM\Index(name: 'street_address_idx', columns: ['street_address'])]
#[ORM\Index(name: 'postal_code_idx', columns: ['postal_code'])]
#[ORM\Index(name: 'email_idx', columns: ['email'])]
#[ORM\Index(name: 'phone_number_idx', columns: ['phone_number'])]
#[ORM\Index(name: 'domain_idx', columns: ['domain'])]
#[ORM\Index(name: 'url_idx', columns: ['url'])]
class ImplementationEntity
{
    #[ORM\Id]
    #[ORM\Column(name: 'implementation_entity_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $implementationEntityId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    #[ORM\Column(name: 'initials', type: 'string', length: 255, nullable: false)]
    private ?string $initials = null;

    #[ORM\Column(name: 'street_address', type: 'string', length: 255, nullable: true)]
    private ?string $streetAddress = null;

    #[ORM\Column(name: 'postal_code', type: 'string', length: 255, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'phone_number', type: 'string', length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(name: 'domain', type: 'string', length: 255, nullable: true)]
    private ?string $domain = null;

    #[ORM\Column(name: 'url', type: 'string', length: 255, nullable: true)]
    private ?string $url = null;

    public function getImplementationEntityId(): ?int
    {
        return $this->implementationEntityId;
    }

    public function setImplementationEntityId(?int $implementationEntityId): void
    {
        $this->implementationEntityId = $implementationEntityId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getInitials(): ?string
    {
        return $this->initials;
    }

    public function setInitials(?string $initials): void
    {
        $this->initials = $initials;
    }

    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    public function setStreetAddress(?string $streetAddress): void
    {
        $this->streetAddress = $streetAddress;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): void
    {
        $this->domain = $domain;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }
}
