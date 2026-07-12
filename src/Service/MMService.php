<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MMSyncableEntity;
use App\Entity\Unit;
use App\Entity\Worker;
use App\Exception\MMException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Central integration with the MM (Μητρώο Μονάδων) API.
 * Port of old `SUS\SiteBundle\Extension\MMService` (`sus.mm.service`).
 *
 * Transport quirks preserved from the old cURL implementation (see
 * docs/port-inventory/services.md §9):
 *  - Read calls are HTTP GET requests **carrying a JSON body** — the MM API reads its search
 *    parameters from the body. Do NOT convert to query-string parameters.
 *  - Auth is HTTP Basic on every call.
 *  - Reads: success = HTTP 200 + decodable JSON containing `->data`; anything else throws
 *    MMException.
 *  - Writes: the HTTP status code is IGNORED; success is `status == 200` INSIDE the JSON body.
 */
class MMService
{
    /**
     * Unit types allowed to sync to MM — copied verbatim from the old
     * `MMService::allowedUnitTypesToMMSync()` (the same list is inlined in the old
     * `SyncUnitsCommand` DQL; both must use this single constant in the port).
     */
    public const ALLOWED_UNIT_TYPES = [
        'ΚΕΣΥΠ', 'ΓΡΑΣΕΠ', 'ΣΣΝ', 'ΚΕΠΛΗΝΕΤ', 'ΕΚΦΕ', 'ΚΠΕ/ΚΕΠΕΑ', 'ΠΕΚ',
        'ΕΡΓΑΣΤΗΡΙΑ ΦΥΣΙΚΩΝ ΕΠΙΣΤΗΜΩΝ', 'ΣΧΟΛΙΚΕΣ ΒΙΒΛΙΟΘΗΚΕΣ',
        'ΓΕΝΙΚΟ ΑΡΧΕΙΟ ΚΡΑΤΟΥΣ', 'ΔΗΜΟΣΙΕΣ ΒΙΒΛΙΟΘΗΚΕΣ', 'ΚΟΜΒΟΣ ΠΣΔ',
        'ΣΧΟΛΙΚΗ ΕΠΙΤΡΟΠΗ ΠΡΩΤΟΒΑΘΜΙΑΣ', 'ΣΧΟΛΙΚΗ ΕΠΙΤΡΟΠΗ ΔΕΥΤΕΡΟΒΑΘΜΙΑΣ',
        'ΣΧΟΛΕΙΟ ΔΕΥΤΕΡΗΣ ΕΥΚΑΙΡΙΑΣ', 'ΙΝΣΤΙΤΟΥΤΟ ΕΠΑΓΓΕΛΜΑΤΙΚΗΣ ΚΑΤΑΡΤΙΣΗΣ',
        'ΣΧΟΛΗ ΕΠΑΓΓΕΛΜΑΤΙΚΗΣ ΚΑΤΑΡΤΙΣΗΣ', 'HELPDESK ΦΟΡΕΩΝ ΥΛΟΠΟΙΗΣΗΣ ΤΟΥ ΠΣΔ',
        'ΟΜΟΣΠΟΝΔΙΑ', 'ΕΛΜΕ', 'ΜΟΝΑΔΕΣ ΑΛΛΩΝ ΥΠΟΥΡΓΕΙΩΝ', 'ΔΗΜΟΤΙΚΕΣ ΒΙΒΛΙΟΘΗΚΕΣ', 'ΕΚΚΛΗΣΙΑΣΤΙΚΟ',
        'ΤΜΗΜΑ ΕΛΛΗΝΙΚΗΣ ΓΛΩΣΣΑΣ', 'ΝΗΠΙΑΓΩΓΕΙΟ', 'ΔΗΜΟΤΙΚΟ', 'ΓΥΜΝΑΣΙΟ', 'ΓΕΝΙΚΟ ΛΥΚΕΙΟ',
        'ΣΥΝΤΟΝΙΣΤΙΚΕΣ ΜΟΝΑΔΕΣ ΕΚΠΑΙΔΕΥΣΗΣ ΕΞΩΤΕΡΙΚΟΥ', 'ΠΕ.Κ.Ε.Σ.', 'ΕΠΑΓΓΕΛΜΑΤΙΚΗ ΣΧΟΛΗ ΚΑΤΑΡΤΙΣΗΣ',
        'ΣΧΟΛΕΣ ΑΝΩΤΕΡΗΣ ΕΠΑΓΓΕΛΜΑΤΙΚΗΣ ΚΑΤΑΡΤΙΣΗΣ', 'ΓΥΜΝΑΣΤΗΡΙΟ', 'ΣΧΟΛΕΣ ΜΑΘΗΤΕΙΑΣ ΥΠΟΨΗΦΙΩΝ ΚΛΗΡΙΚΩΝ',
        'ΚΕΝΤΡΟ ΚΑΙΝΟΤΟΜΙΑΣ',
    ];

    private string $baseUrl;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $em,
        #[Autowire(env: 'MMSCH_API_BASE_URL')]
        string $baseUrl,
        #[Autowire(env: 'MMSCH_API_USERNAME')]
        private readonly string $username,
        #[Autowire(env: 'MMSCH_API_PASSWORD')]
        private readonly string $password,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    protected function allowedUnitTypesToMMSync(?string $unitType): bool
    {
        return \in_array($unitType, self::ALLOWED_UNIT_TYPES, true);
    }

    /**
     * Old behavior preserved: works only for local units fresher than one day; a stale or
     * missing unit triggers an MM lookup whose hydration was disabled in the old app and
     * unconditionally throws.
     */
    public function findUnit(int|string $mmid): ?Unit
    {
        $unit = $this->em->getRepository(Unit::class)->find($mmid);
        $yesterday = new \DateTime('yesterday');
        if (!isset($unit) || $unit->getUpdatedAt() < $yesterday) {
            // Query the MM and try to find the unit
            $mmUnitEntries = $this->queryUnits([
                'mm_id' => $mmid,
                'count' => 1,
            ]);
            if (\count($mmUnitEntries) === 1) {
                $unit = $this->hydrateUnit($mmUnitEntries[0]);
            } elseif (\count($mmUnitEntries) > 1) {
                throw new MMException('Found more than one unit: ' . \count($mmUnitEntries));
            } else {
                $unit = null;
            }
        }

        return $unit;
    }

    /**
     * Returns raw stdClass rows from the MM API (hydration into local entities was abandoned
     * in the old app). NOTE: the old code also contained a debug-leftover `ldapuid` filter
     * stub that hard-coded `mm_id = 1000003` — deliberately NOT ported.
     *
     * @return list<\stdClass>
     */
    public function findUnitsBy(array $filters = []): array
    {
        $params = ['searchtype' => 'EXACT'];
        if (isset($filters['mm_id']) && $filters['mm_id'] != '') {
            $params['mm_id'] = $filters['mm_id'];
        }
        if (isset($filters['registry_no']) && $filters['registry_no'] != '') {
            $params['registry_no'] = $filters['registry_no'];
        }
        if (isset($filters['name']) && $filters['name'] != '') {
            $params['name'] = $filters['name'];
        }
        if (isset($filters['fy']) && $filters['fy'] != '') {
            $params['implementation_entity'] = $filters['fy'];
        }
        // NOTE (faithful quirk): a 'limit' filter is NOT forwarded — the old code ignored it,
        // so even findOneUnitBy() queries with the default count of 10.

        return $this->queryUnits($params);
    }

    /**
     * @return list<\stdClass>
     */
    public function findWorkersBy(array $filters = []): array
    {
        $params = [];
        if (isset($filters['registry_no']) && $filters['registry_no'] != '') {
            $params['registry_no'] = $filters['registry_no'];
        }
        if (isset($filters['worker']) && $filters['worker'] != '') {
            $params['worker'] = $filters['worker'];
        }
        // NOTE (faithful quirk): a 'searchtype' filter is NOT forwarded — the old code ignored
        // it, so persistWorker()'s duplicate search ran as a non-EXACT search despite passing
        // 'searchtype' => 'EXACT'.

        return $this->queryWorkers($params);
    }

    public function findOneUnitBy(array $filters = []): \stdClass
    {
        $units = $this->findUnitsBy($filters + ['limit' => 1]);
        if (!isset($units[0])) {
            throw new MMException('The unit was not found');
        }
        if (\count($units) > 1) {
            throw new MMException('Found more than one unit: ' . \count($units));
        }

        return $units[0];
    }

    public function persistMM(MMSyncableEntity $entity): void
    {
        if ($entity instanceof Unit) {
            $this->persistUnit($entity);
        } elseif ($entity instanceof Worker) {
            $this->persistWorker($entity);
        } else {
            throw new MMException('Unsupported entity');
        }
    }

    /**
     * Hydration of MM rows into local Units was disabled in the old app (body commented out,
     * unconditional throw) — behavior preserved. The old commented-out code mapped:
     * mm_id→mmId/unitId, state, name, postal_code, registry_no, street_address, category.
     */
    protected function hydrateUnit(\stdClass $entry, bool $flush = false): Unit
    {
        throw new \Exception('Not supported');
    }

    /**
     * @return list<\stdClass>
     */
    protected function queryUnits(array $params = []): array
    {
        if (!isset($params['limit']) || $params['limit'] == '') {
            $params['count'] = 10;
        } else {
            $params['count'] = $params['limit'];
        }
        if (!isset($params['startat']) || $params['startat'] == '') {
            $params['startat'] = 0;
        }

        return $this->queryMM('units', $params);
    }

    /**
     * @return list<\stdClass>
     */
    protected function queryWorkers(array $params = []): array
    {
        if (!isset($params['limit']) || $params['limit'] == '') {
            $params['count'] = 10;
        } else {
            $params['count'] = $params['limit'];
        }
        if (!isset($params['startat']) || $params['startat'] == '') {
            $params['startat'] = 0;
        }

        return $this->queryMM('workers', $params);
    }

    /**
     * @return list<\stdClass>
     */
    protected function queryUnitWorkers(array $params = []): array
    {
        if (!isset($params['limit']) || $params['limit'] == '') {
            $params['count'] = 10;
        } else {
            $params['count'] = $params['limit'];
        }
        if (!isset($params['startat']) || $params['startat'] == '') {
            $params['startat'] = 0;
        }

        return $this->queryMM('unit_workers', $params);
    }

    /**
     * Read call: GET with the parameters JSON-encoded in the request BODY (MM API quirk).
     * Success = HTTP 200 and a JSON body containing `data`.
     *
     * @return list<\stdClass>
     */
    protected function queryMM(string $resource, array $params = []): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . $resource, [
                'auth_basic' => [$this->username, $this->password],
                'body' => json_encode($params),
            ]);
            $httpStatus = $response->getStatusCode();
            $data = $response->getContent(false);
        } catch (HttpClientExceptionInterface $e) {
            throw new MMException('MMSCH Error: ' . $e->getMessage(), 0, $e);
        }

        if (200 === $httpStatus) {
            $decodedData = json_decode($data);
            if (!$decodedData || !isset($decodedData->data)) {
                throw new MMException('MMSCH Error: ' . $data);
            }

            return $decodedData->data;
        }

        throw new MMException('MMSCH Error: ' . $data);
    }

    /**
     * Write call: POST/PUT with JSON body. The HTTP status code is IGNORED (old behavior);
     * success is `status == 200` inside the decoded JSON body. The (grammatically odd) error
     * message 'Error adding unit: ...' was used verbatim for units, workers AND unit_workers
     * in the old code — preserved.
     */
    protected function writeMM(string $resource, string $method, array $params): \stdClass
    {
        try {
            $response = $this->httpClient->request($method, $this->baseUrl . $resource, [
                'auth_basic' => [$this->username, $this->password],
                'body' => json_encode($params),
            ]);
            $origData = $response->getContent(false);
        } catch (HttpClientExceptionInterface $e) {
            throw new MMException('Error adding unit: ' . $e->getMessage(), 0, $e);
        }

        $data = json_decode($origData);
        if (!\is_object($data) || !isset($data->status) || $data->status != 200) {
            throw new MMException('Error adding unit: ' . $origData);
        }

        return $data;
    }

    public function persistUnit(Unit $unit): void
    {
        if ($unit->getMmSyncId() !== null) {
            $method = 'PUT';
            $extraParams = ['unit_id' => $unit->getMmSyncId()];
        } else {
            $curUnit = $this->findUnitsBy(['name' => $unit->getName()]);
            if (isset($curUnit[0])) { // Check if already exists — adopt the MM id, do not push
                $unit->setMmSyncId((int) $curUnit[0]->mm_id);
                // Deliberate +2min skew so the sync command's
                // `mmSyncLastUpdateDate < updatedAt` check doesn't immediately re-flag the unit.
                $modifyDateTime = new \DateTime('now');
                $unit->setMmSyncLastUpdateDate($modifyDateTime->add(new \DateInterval('PT2M')));
                $this->em->persist($unit);
                $this->em->flush();

                return;
            }
            $method = 'POST';
            $extraParams = [];
        }

        $lastUpdate = $unit->getUpdatedAt();
        $params = array_merge($extraParams, [
            'mm_id' => $unit->getMmSyncId(),
            'registry_no' => $unit->getRegistryNo(),
            'name' => $unit->getName(),
            'source' => 'SUS',
            'category' => $unit->getCategory()?->getName(),
            'suspended' => !$unit->isActive(),
            'state' => $unit->getState()?->getName(),
            'education_level' => $unit->getUnitType()?->getEducationLevel()?->getName(),
            'special_name' => $unit->getSpecialName(),
            'region_edu_admin' => $unit->getRegionEduAdmin()?->getName(),
            'edu_admin' => $unit->getEduAdmin()?->getName(),
            'implementation_entity' => $unit->getImplementationEntity()?->getImplementationEntityId(),
            'municipality' => $unit->getMunicipality()?->getName(),
            'municipality_community' => $unit->getMunicipalityCommunity()?->getName(),
            'prefecture' => $unit->getPrefecture()?->getName(),
            'unit_type' => $unit->getUnitType()?->getName(),
            'legal_character' => $unit->getLegalCharacter()?->getName(),
            'postal_code' => $unit->getPostalCode(),
            'last_update' => $lastUpdate instanceof \DateTimeInterface ? $lastUpdate->format('Y-m-d H:i:s') : null,
            'last_sync' => $lastUpdate instanceof \DateTimeInterface ? $lastUpdate->format('Y-m-d H:i:s') : null,
            'email' => $unit->getEmail(),
            'fax_number' => $unit->getFaxNumber(),
            'street_address' => $unit->getStreetAddress(),
            'phone_number' => $unit->getPhoneNumber(),
            'tax_number' => $unit->getTaxNumber(),
            'tax_office' => $unit->getTaxOffice()?->getName(),
            'comments' => $unit->getComments(),
            'latitude' => $unit->getLatitude(),
            'longitude' => $unit->getLongitude(),
            'country' => $unit->getCountry(),
            'positioning' => $unit->getPositioning(),
        ]);

        // Push only units whose type is in the MM sync whitelist. (The old code called
        // getUnitType()->getName() without a null check here and would fatal on a NULL
        // unitType — the port guards and skips instead, per the inventory recommendation.)
        if (!$this->allowedUnitTypesToMMSync($unit->getUnitType()?->getName())) {
            return;
        }

        $data = $this->writeMM('units', $method, $params);
        if ('POST' === $method) {
            $unit->setMmSyncId((int) $data->mm_id);
        }
        $modifyDateTime = new \DateTime('now');
        $unit->setMmSyncLastUpdateDate($modifyDateTime->add(new \DateInterval('PT2M')));
        // NOTE: the caller (listener or sync command) is responsible for flushing.
    }

    public function persistWorker(Worker $worker): void
    {
        if ($worker->getMmSyncId() !== null) {
            $method = 'PUT';
            $extraParams = ['worker_id' => $worker->getMmSyncId()];
        } else {
            $curWorker = $this->findWorkersBy([
                'worker' => $worker->getLastname() . ' ' . $worker->getFirstname(),
                'searchtype' => 'EXACT',
            ]);
            if (isset($curWorker[0])) { // Check if already exists — adopt the MM id, do not push
                $worker->setMmSyncId((int) $curWorker[0]->worker_id);
                $worker->setMmSyncLastUpdateDate(new \DateTime('now'));
                $this->em->persist($worker);
                $this->em->flush();
                foreach ($this->getWorkerUnits($worker) as $curUnit) {
                    $this->addUnitWorker($curUnit, $worker);
                }

                return;
            }
            $method = 'POST';
            $extraParams = [];
        }

        $params = array_merge($extraParams, [
            'worker_id' => $worker->getWorkerId(),
            'registry_no' => $worker->getRegistryNo(),
            'lastname' => $worker->getLastname(),
            'firstname' => $worker->getFirstname(),
            'fathername' => $worker->getFathername(),
            'sex' => $worker->getSex(),
            'source' => 'SUS',
        ]);

        $data = $this->writeMM('workers', $method, $params);
        if ('POST' === $method) {
            $worker->setMmSyncId((int) $data->worker_id);
        }
        $worker->setMmSyncLastUpdateDate(new \DateTime('now'));
        foreach ($this->getWorkerUnits($worker) as $curUnit) {
            $this->addUnitWorker($curUnit, $worker);
        }
    }

    /**
     * @return list<Unit>
     */
    private function getWorkerUnits(Worker $worker): array
    {
        $units = [];
        if ($worker->getUnit() !== null) {
            $units[] = $worker->getUnit();
        }
        foreach ($worker->getResponsibleUnits() as $curUnit) {
            $units[] = $curUnit;
        }

        return $units;
    }

    private function addUnitWorker(Unit $unit, Worker $worker): void
    {
        if ($worker->getMmSyncId() === null || $unit->getMmSyncId() === null) {
            throw new MMException('Worker or unit is not synced: ' . $worker->getMmSyncId() . ' ' . $unit->getMmSyncId());
        }
        $extraParams = [];
        $curUnitWorker = $this->queryUnitWorkers([
            'worker' => $worker->getLastname() . ' ' . $worker->getFirstname(),
            'unit' => $unit->getMmSyncId(),
            'searchtype' => 'EXACT',
        ]);
        if (isset($curUnitWorker[0])) { // Check if already exists
            $method = 'PUT';
            $extraParams['unit_worker_id'] = $curUnitWorker[0]->unit_worker_id;
        } else {
            $method = 'POST';
        }
        $params = array_merge($extraParams, [
            'worker' => $worker->getMmSyncId(),
            'mm_id' => $unit->getMmSyncId(),
            'worker_position' => $worker->getUnit() === $unit ? 'ΔΙΕΥΘΥΝΤΗΣ ΚΕΠΛΗΝΕΤ' : 'ΤΕΧΝΙΚΟΣ ΥΠΕΥΘΥΝΟΣ ΚΕΠΛΗΝΕΤ',
        ]);

        $this->writeMM('unit_workers', $method, $params);
    }
}
