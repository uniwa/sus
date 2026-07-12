<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Λεξικό με τις Κατηγορίες. (old `SUS\SiteBundle\Entity\UnitCategory`)
 *
 * NOTE: class name and table name differ on purpose — the table really is `categories`.
 *
 * Legacy column comments (annotation-only, absent from the real DB):
 *  - category_id: "Ο Κωδικός ID της Κατηγορίας."
 *  - name:        "Το Όνομα της Κατηγορίας."
 */
#[ORM\Entity]
#[ORM\Table(name: 'categories')]
class UnitCategory
{
    #[ORM\Id]
    #[ORM\Column(name: 'category_id', type: 'integer', nullable: false)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $categoryId = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private ?string $name = null;

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): void
    {
        $this->categoryId = $categoryId;
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
