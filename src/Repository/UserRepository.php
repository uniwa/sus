<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Replacement for the old (empty) `SUS\UserBundle\Entity\Repositories\UserRepository`.
 *
 * The old `BaseRepository`/`UnitsRepository` were dead code and are intentionally not ported
 * (docs/port-inventory/entities.md §0.4).
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /** Lookup used by the CAS authenticator (usernames come from sso.sch.gr). */
    public function findOneByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }
}
