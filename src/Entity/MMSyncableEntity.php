<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Base class for entities that are synchronized with the MM (Μητρώο Μονάδων) web service.
 *
 * Legacy column comments (present only in the old annotations, NOT in the real DB — omitted
 * from the mapping so the schema diff stays clean):
 *  - mmSyncId:             "Ο Κωδικός ΜΜ της Μονάδας."
 *  - mmSyncLastUpdateDate: "Η Ημερομηνία Συγχρονισμού της Μονάδας με το ΜΜ."
 */
#[ORM\MappedSuperclass]
abstract class MMSyncableEntity
{
    #[ORM\Column(name: 'mmSyncId', type: 'integer', nullable: true)]
    protected ?int $mmSyncId = null;

    #[ORM\Column(name: 'mmSyncLastUpdateDate', type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $mmSyncLastUpdateDate = null;

    public function getMmSyncId(): ?int
    {
        return $this->mmSyncId;
    }

    public function setMmSyncId(?int $mmSyncId): void
    {
        $this->mmSyncId = $mmSyncId;
    }

    public function getMmSyncLastUpdateDate(): ?\DateTimeInterface
    {
        return $this->mmSyncLastUpdateDate;
    }

    public function setMmSyncLastUpdateDate(?\DateTimeInterface $mmSyncLastUpdateDate): void
    {
        $this->mmSyncLastUpdateDate = $mmSyncLastUpdateDate;
    }

    abstract public function isActive(): bool;
}
