<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Port of old `SUS\AdminBundle\Controller\FyController::getFysAction`
 * (FOSRest `type: rest` route `get_fys`, GET /fys, `@Secure("ROLE_USER")`).
 *
 * IMPORTANT behavior note: the old endpoint was FATALLY BROKEN. It called
 * `$repo->findFys(...)` on the Unit repository, but `UnitsRepository` was never wired to the
 * entity (no `repositoryClass` annotation), the DQL it contained selected `u.fyName` /
 * `u.fyInitials` fields that do not exist on the Unit entity or in the `units` table, and the
 * `UnitFy` class it hydrated does not exist anywhere in the old src/. Any request therefore
 * produced a 500. The consumer (the `mmfy` select2 widget) is likewise dead code.
 *
 * The port implements the evident intent instead: return the distinct FYs (Φορείς Υλοποίησης —
 * implementation entities) referenced by units, as `[{"name": ..., "initials": ...}, ...]`,
 * optionally filtered by a `name` LIKE query — mirroring the shape of the old
 * `UnitsRepository::findFys()` code.
 */
class FyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/fys', name: 'get_fys', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getFys(Request $request): JsonResponse
    {
        $name = $request->query->get('name');

        $qb = $this->em->createQueryBuilder()
            ->select('ie.name AS name, ie.initials AS initials')
            ->from(Unit::class, 'u')
            ->join('u.implementationEntity', 'ie')
            ->groupBy('ie.name, ie.initials');

        if ($name !== null && $name !== '') {
            $qb->andWhere('ie.name LIKE :fyname');
            $qb->setParameter('fyname', '%'.$name.'%');
        }

        $fys = array_values(array_filter(
            $qb->getQuery()->getResult(),
            static fn (array $row): bool => ($row['name'] ?? '') !== ''
        ));

        return new JsonResponse($fys);
    }
}
