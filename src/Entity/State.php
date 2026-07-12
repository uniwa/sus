<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τις Λειτουργικές Καταστάσεις. (old `SUS\SiteBundle\Entity\States`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - state_id: "Ο Κωδικός ID της Λειτουργικής Κατάστασης."
 *  - name:     "Το Όνομα της Λειτουργικής Κατάστασης."
 */
#[ORM\Entity]
#[ORM\Table(name: 'states')]
class State
{
    #[ORM\Id]
    #[ORM\Column(name: 'state_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $stateId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getStateId(): ?int
    {
        return $this->stateId;
    }

    public function setStateId(?int $stateId): void
    {
        $this->stateId = $stateId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }
}
