<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\MMService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Port of old `SUS\AdminBundle\Controller\UnitController::getUnitsAction`
 * (FOSRest `type: rest` route `get_units`, GET /units, `@Secure("ROLE_USER")`).
 *
 * Consumer: the select2 autocomplete widget (`field_mmunit.html.twig` form theme) sent
 * `?name=<term>&filters=<json>`; the `filters` query parameter was ignored by the old action
 * and is ignored here too.
 *
 * Behavior notes (vs. the old app):
 *  - The old route was `/units.{_format}` with `_format` optional/json by default; nothing was
 *    found using the extension, so the port answers on the plain `/units` path only.
 *  - The old code merged the three MM lookups with `array_unique()` (default SORT_STRING),
 *    which cannot stringify the stdClass rows returned by the MM API; the port deduplicates
 *    with SORT_REGULAR (structural comparison), which is what the code always intended.
 *  - A commented-out block that would have added an `fy` filter for non-ROLE_KEDO users was
 *    dead code in the old app and was not ported.
 */
class UnitController extends AbstractController
{
    public function __construct(
        private readonly MMService $mmService,
    ) {
    }

    #[Route('/units', name: 'get_units', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getUnits(Request $request): JsonResponse
    {
        $name = $request->query->get('name');

        $unitsByName = $this->mmService->findUnitsBy([
            'name' => $name,
        ]);
        $unitsByRegistryNo = $this->mmService->findUnitsBy([
            'registry_no' => $name,
        ]);
        $unitsByMmId = $this->mmService->findUnitsBy([
            'mm_id' => $name,
        ]);

        $units = array_values(array_unique(
            array_merge($unitsByName, $unitsByRegistryNo, $unitsByMmId),
            SORT_REGULAR
        ));

        return new JsonResponse($units);
    }
}
