<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Περιέχει πληροφορίες σχετικά με τα Στοιχεία Εργαζόμενων. (old `SUS\SiteBundle\Entity\Workers`)
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - worker_id:   "Ο Κωδικός ID του Εργαζόμενου."
 *  - registry_no: "Ο Κωδικός Μητρώου του Εργαζόμενου."
 *  - tax_number:  "Το ΑΦΜ του Εργαζόμενου."
 *  - lastname:    "Το Επίθετο του Εργαζόμενου."
 *  - firstname:   "Το Όνομα του Εργαζόμενου."
 *  - fathername:  "Το Όνομα Πατρός του Εργαζόμενου."
 *  - sex:         "Το Φύλο του Εργαζόμενου."
 *
 * DRIFT (entities.md §2.2): the old class had `extends MMSyncableEntity` commented out, but the
 * real `workers` table DOES contain `mmSyncId` and `mmSyncLastUpdateDate` — so the new class
 * extends the mapped superclass to map them (otherwise --dump-sql would DROP the columns).
 */
#[ORM\Entity]
#[ORM\Table(name: 'workers')]
#[ORM\UniqueConstraint(name: 'registry_no_UNIQUE', columns: ['registry_no'])]
#[ORM\Index(name: 'tax_number_idx', columns: ['tax_number'])]
#[ORM\Index(name: 'lastname_idx', columns: ['lastname'])]
#[ORM\Index(name: 'firstname_idx', columns: ['firstname'])]
#[ORM\Index(name: 'fathername_idx', columns: ['fathername'])]
#[ORM\Index(name: 'sex_idx', columns: ['sex'])]
class Worker extends MMSyncableEntity
{
    #[ORM\Id]
    #[ORM\Column(name: 'worker_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $workerId = null;

    /** Owning side of Unit::$manager. */
    #[ORM\OneToOne(targetEntity: Unit::class, inversedBy: 'manager')]
    #[ORM\JoinColumn(name: 'unit_id', referencedColumnName: 'unit_id')]
    private ?Unit $unit = null;

    /** @var Collection<int, Unit> */
    #[ORM\ManyToMany(targetEntity: Unit::class, mappedBy: 'responsibles')]
    private Collection $responsibleUnits;

    #[ORM\Column(name: 'registry_no', type: 'string', length: 255, nullable: true)]
    private ?string $registryNo = null;

    #[ORM\Column(name: 'tax_number', type: 'string', length: 255, nullable: true)]
    private ?string $taxNumber = null;

    #[ORM\Column(name: 'lastname', type: 'string', length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(name: 'firstname', type: 'string', length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(name: 'fathername', type: 'string', length: 255, nullable: true)]
    private ?string $fathername = null;

    #[ORM\Column(name: 'sex', type: 'string', length: 1, nullable: true)]
    private ?string $sex = null;

    #[ORM\ManyToOne(targetEntity: WorkerSpecialization::class)]
    #[ORM\JoinColumn(name: 'worker_specialization_id', referencedColumnName: 'worker_specialization_id')]
    private ?WorkerSpecialization $workerSpecialization = null;

    public function __construct()
    {
        $this->responsibleUnits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->getWorkerId();
    }

    public function getWorkerId(): ?int
    {
        return $this->workerId;
    }

    public function setWorkerId(?int $workerId): void
    {
        $this->workerId = $workerId;
    }

    public function getUnit(): ?Unit
    {
        return $this->unit;
    }

    public function setUnit(?Unit $unit = null): void
    {
        $this->unit = $unit;
    }

    /** @return Collection<int, Unit> */
    public function getResponsibleUnits(): Collection
    {
        return $this->responsibleUnits;
    }

    /** @param iterable<Unit>|null $responsibleUnits */
    public function setResponsibleUnits($responsibleUnits = null): void
    {
        $collection = new ArrayCollection();
        foreach ($responsibleUnits ?? [] as $unit) {
            $collection->add($unit);
        }
        $this->responsibleUnits = $collection;
    }

    public function getRegistryNo(): ?string
    {
        return $this->registryNo;
    }

    public function setRegistryNo(?string $registryNo): void
    {
        $this->registryNo = $registryNo;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(?string $taxNumber): void
    {
        $this->taxNumber = $taxNumber;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getFathername(): ?string
    {
        return $this->fathername;
    }

    public function setFathername(?string $fathername): void
    {
        $this->fathername = $fathername;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(?string $sex): void
    {
        $this->sex = $sex;
    }

    public function getWorkerSpecialization(): ?WorkerSpecialization
    {
        return $this->workerSpecialization;
    }

    public function setWorkerSpecialization(?WorkerSpecialization $workerSpecialization = null): void
    {
        $this->workerSpecialization = $workerSpecialization;
    }

    public function __toString(): string
    {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }

    public function isActive(): bool
    {
        return true;
    }
}
