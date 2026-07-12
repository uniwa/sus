<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τις Ειδικότητες Εργαζομένων. (old `SUS\SiteBundle\Entity\WorkerSpecializations`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - worker_specialization_id: "Ο Κωδικός ID της Ειδικότητας Εργαζόμενου."
 *  - name:                     "Το Όνομα της Ειδικότητας Εργαζόμενου."
 *
 * The DB has BOTH the unique key `name_UNIQUE` (declared in the old annotation) and a plain
 * index `name_idx` on `name` (never declared in the old code) — both are mapped so the schema
 * diff is clean.
 *
 * No __toString() on purpose — the old class had none (Sonata used the `name` property path).
 */
#[ORM\Entity]
#[ORM\Table(name: 'worker_specializations')]
#[ORM\UniqueConstraint(name: 'name_UNIQUE', columns: ['name'])]
#[ORM\Index(name: 'name_idx', columns: ['name'])]
class WorkerSpecialization
{
    #[ORM\Id]
    #[ORM\Column(name: 'worker_specialization_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $workerSpecializationId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->getWorkerSpecializationId();
    }

    public function getWorkerSpecializationId(): ?int
    {
        return $this->workerSpecializationId;
    }

    public function setWorkerSpecializationId(?int $workerSpecializationId): void
    {
        $this->workerSpecializationId = $workerSpecializationId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
