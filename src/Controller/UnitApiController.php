<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Port of old `SUS\AdminBundle\Controller\UnitApiController::getUnitExtraAction`
 * (annotation route `get_unit_extra`, GET /units_extra/{mmid}).
 *
 * Faithfully preserved quirks of the old endpoint:
 *  - NO authentication: the old app had no `@Secure` annotation and no access_control rule for
 *    /units_extra, and the firewall allowed anonymous access — the endpoint was effectively
 *    public. Preserved as-is; tighten consciously if the owner confirms it should be private.
 *  - Despite the `{mmid}` parameter name, the lookup is by `registryNo` (a rename/bugfix
 *    artifact in the old code — live behavior, kept).
 *  - `mmid` in the response is ALWAYS null: the old `Unit::getMmId()` read an undeclared
 *    `$mmId` property (the postLoad listener that was supposed to populate it from the MM
 *    service was fully commented out), so the old app always serialized null here. The new
 *    Unit entity has no such virtual property; null is emitted directly to keep the JSON
 *    output identical. (If a real MM id is ever wanted here, `getMmSyncId()` is the mapped
 *    column — a deliberate behavior change to discuss with the owner.)
 *  - Error payloads and status codes match the old ones exactly:
 *    400 {"error":"Invalid unit ID"}, 404 {"error":"Unit not found"},
 *    500 {"error":"Internal server error","message":<exception message>}.
 */
class UnitApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/units_extra/{mmid}', name: 'get_unit_extra', methods: ['GET'])]
    public function getUnitExtra(string $mmid): JsonResponse
    {
        try {
            // Ensure the id is numeric
            if (!is_numeric($mmid)) {
                return new JsonResponse(['error' => 'Invalid unit ID'], 400);
            }

            // Search by registryNo (NOT unitId / mmSyncId) — live behavior of the old app.
            $unit = $this->em->getRepository(Unit::class)->findOneBy(['registryNo' => $mmid]);

            if (!$unit) {
                return new JsonResponse(['error' => 'Unit not found'], 404);
            }

            $data = [
                'id' => $unit->getUnitId(),
                'mmid' => null, // see class docblock — old getMmId() was always null
                'name' => $unit->getName(),
                'registryno' => $unit->getRegistryNo() ?: '',
                'website' => $unit->getWebsite(),
            ];

            return new JsonResponse($data);
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
