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
 * Port of old `SUS\AdminBundle\Controller\CategoryController::getCategoriesAction`
 * (FOSRest `type: rest` route `get_categories`, GET /categories, `@Secure("ROLE_USER")`).
 *
 * IMPORTANT behavior note: like /fys, the old endpoint was FATALLY BROKEN — it called
 * `$repo->findCategories(...)` on the default Unit repository (custom `UnitsRepository` was
 * never wired via `repositoryClass`), and the DQL in that repository selected a
 * `u.categoryName` field that does not exist on the entity or in the `units` table. Any
 * request produced a 500; the consuming `mmcategory` select2 widget is dead code.
 *
 * The port implements the evident intent instead: return the distinct category names
 * referenced by units, as `[{"name": ...}, ...]`, optionally filtered by a `name` LIKE query —
 * mirroring the shape of the old `UnitsRepository::findCategories()` code.
 */
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/categories', name: 'get_categories', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCategories(Request $request): JsonResponse
    {
        $name = $request->query->get('name');

        $qb = $this->em->createQueryBuilder()
            ->select('c.name AS name')
            ->from(Unit::class, 'u')
            ->join('u.category', 'c')
            ->groupBy('c.name');

        if ($name !== null && $name !== '') {
            $qb->andWhere('c.name LIKE :catname');
            $qb->setParameter('catname', '%'.$name.'%');
        }

        $categories = array_values(array_filter(
            $qb->getQuery()->getResult(),
            static fn (array $row): bool => ($row['name'] ?? '') !== ''
        ));

        return new JsonResponse($categories);
    }
}
