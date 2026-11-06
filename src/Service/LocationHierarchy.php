<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Child → parent maps behind the dependent dropdowns of the Unit form, keyed by SUS ids.
 *
 * SUS's own dictionaries cannot provide this: municipalities.prefecture_id is stale (Attica is
 * lumped together, the Dodecanese/Cyclades point at deleted prefectures) and
 * municipality_communities has no municipality column. MM (`mitr_monadon`, same server, readable
 * by the app login) has both, but its prefecture and municipality ids differ from SUS's, so those
 * are matched BY NAME. Community ids are shared between the two databases.
 *
 * Neither database originally knew which Περιφέρεια a Περιφερειακή Ενότητα belongs to, so
 * `prefectures.region_id` was added to SUS and filled by hand; correct it there, not in code.
 *
 * Everything here only reads. If MM is unavailable the MM-backed maps are empty and the affected
 * dropdowns simply stay unfiltered; the form keeps working.
 */
final class LocationHierarchy
{
    private const MM_SCHEMA = 'mitr_monadon';
    private const CACHE_KEY = 'sus.location_hierarchy.v1';
    private const CACHE_TTL = 86400;
    private const CACHE_TTL_AFTER_FAILURE = 300;

    /** MM renamed these municipalities; SUS still has them under the old name. */
    private const MM_MUNICIPALITY_ALIASES = [
        'ΗΡΩΙΚΗΣ ΝΗΣΟΥ ΚΑΣΟΥ' => 'ΚΑΣΟΥ',
        'ΗΡΩΙΚΗΣ ΠΟΛΕΩΣ ΝΑΟΥΣΑΣ' => 'ΝΑΟΥΣΑΣ',
        'ΜΕΤΕΩΡΩΝ' => 'ΚΑΛΑΜΠΑΚΑΣ',
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @return array<int, int> prefecture id => region id */
    public function prefectureToRegion(): array
    {
        return $this->maps()['prefectureToRegion'];
    }

    /** @return array<int, int> municipality id => prefecture id */
    public function municipalityToPrefecture(): array
    {
        return $this->maps()['municipalityToPrefecture'];
    }

    /** @return array<int, int> municipality community id => municipality id */
    public function communityToMunicipality(): array
    {
        return $this->maps()['communityToMunicipality'];
    }

    /** @return array<int, int> edu admin id => region edu admin id */
    public function eduAdminToRegionEduAdmin(): array
    {
        return $this->maps()['eduAdminToRegionEduAdmin'];
    }

    /** @return array<string, array<int, int>> */
    private function maps(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $municipalityToPrefecture = [];
            $communityToMunicipality = [];
            $mmAvailable = true;

            $municipalityIds = $this->idsByName('municipalities', 'municipality_id');
            $prefectureIds = $this->idsByName('prefectures', 'prefecture_id');

            try {
                $municipalityToPrefecture = $this->loadMunicipalityToPrefecture($municipalityIds, $prefectureIds);
                $communityToMunicipality = $this->loadCommunityToMunicipality($municipalityIds);
            } catch (\Doctrine\DBAL\Exception $e) {
                $mmAvailable = false;
                $this->logger->warning('Could not read the location hierarchy from MM; dropdowns stay unfiltered.', ['exception' => $e]);
            }

            $item->expiresAfter($mmAvailable ? self::CACHE_TTL : self::CACHE_TTL_AFTER_FAILURE);

            return [
                'prefectureToRegion' => array_map('intval', $this->connection->fetchAllKeyValue(
                    'SELECT prefecture_id, region_id FROM prefectures WHERE region_id IS NOT NULL'
                )),
                'municipalityToPrefecture' => $municipalityToPrefecture,
                'communityToMunicipality' => $communityToMunicipality,
                'eduAdminToRegionEduAdmin' => array_map('intval', array_filter($this->connection->fetchAllKeyValue(
                    'SELECT edu_admin_id, region_edu_admin_id FROM edu_admins WHERE region_edu_admin_id IS NOT NULL'
                ))),
            ];
        });
    }

    /** @return array<string, int> */
    private function idsByName(string $table, string $idColumn): array
    {
        return array_map('intval', $this->connection->fetchAllKeyValue(
            "SELECT name, {$idColumn} FROM {$table} WHERE name IS NOT NULL"
        ));
    }

    /**
     * @param array<string, int> $municipalityIds
     * @param array<string, int> $prefectureIds
     *
     * @return array<int, int>
     */
    private function loadMunicipalityToPrefecture(array $municipalityIds, array $prefectureIds): array
    {
        $mm = self::MM_SCHEMA;
        $rows = $this->connection->fetchAllKeyValue(
            "SELECT m.name, p.name FROM `{$mm}`.municipalities m JOIN `{$mm}`.prefectures p ON p.prefecture_id = m.prefecture_id"
        );

        $map = [];
        foreach ($rows as $municipality => $prefecture) {
            if (isset($municipalityIds[$municipality], $prefectureIds[$prefecture])) {
                $map[$municipalityIds[$municipality]] = $prefectureIds[$prefecture];
            }
        }

        return $map;
    }

    /**
     * @param array<string, int> $municipalityIds
     *
     * @return array<int, int>
     */
    private function loadCommunityToMunicipality(array $municipalityIds): array
    {
        $mm = self::MM_SCHEMA;
        $rows = $this->connection->fetchAllKeyValue(
            "SELECT c.municipality_community_id, m.name FROM `{$mm}`.municipality_communities c JOIN `{$mm}`.municipalities m ON m.municipality_id = c.municipality_id"
        );

        $map = [];
        foreach ($rows as $community => $municipality) {
            $municipality = self::MM_MUNICIPALITY_ALIASES[$municipality] ?? $municipality;
            if (isset($municipalityIds[$municipality])) {
                $map[(int) $community] = $municipalityIds[$municipality];
            }
        }

        return $map;
    }
}
