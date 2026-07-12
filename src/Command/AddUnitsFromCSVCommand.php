<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\EduAdmin;
use App\Entity\ImplementationEntity;
use App\Entity\LegalCharacter;
use App\Entity\RegionEduAdmin;
use App\Entity\State;
use App\Entity\Unit;
use App\Entity\UnitCategory;
use App\Entity\UnitType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Port of old `SUS\SiteBundle\Command\AddUnitsFromCSVCommand` (`sus:addunitsfromcsv`).
 *
 * One-off XLSX import of school units. Historical — kept in case bulk import is still wanted.
 * (The dead `_SDE`/`_TEG` file variants of this command were never registered in the old app
 * and are deliberately NOT ported — see docs/port-inventory/commands.md.)
 *
 * Expected columns: name, special_name, registry_no, street_address, phone_number, email,
 * unit_type, edu_admin.
 *
 * The old code's dictionary "lookups" for unit_type / edu_admin went against the app's OWN
 * database (mitr_sus, via a second hardcoded-credentials PDO connection) purely as an
 * existence check before resolving the local entity — the port runs them on the app's own
 * Doctrine DBAL connection instead.
 *
 * NOTE: in the old prod environment every flush here also fired the MMSyncableListener
 * (immediate MM push); in the port that is governed by MMSCH_SYNC_ENABLED.
 */
#[AsCommand(name: 'sus:addunitsfromcsv', description: 'Import a CSV with line data')]
final class AddUnitsFromCSVCommand extends AbstractXlsxImportCommand
{
    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'xls file to import from', name: 'file')]
        ?string $file = null,
    ): int {
        $output->writeln('Starting ImportCSV process');
        $xls = $this->loadWorksheet($file);
        $headers = $this->parseHeadersToArray($xls->getRowIterator(1)->current());
        foreach ($xls->getRowIterator(2) as $row) {
            $fields = $this->parseRowToArray($row, $headers);
            $unit = $this->em->getRepository(Unit::class)->findOneBy([
                'name' => $fields['name'],
            ]);
            if (isset($unit)) {
                $output->writeln('Skipping unit: ' . $unit->getName());
                continue;
            }
            $unit = new Unit();
            $unit->setName($this->str($fields['name']));
            $unit->setSpecialName($this->str($fields['special_name']));
            $unit->setRegistryNo($this->str($fields['registry_no']));
            $unit->setStreetAddress($this->str($fields['street_address']));
            $unit->setPhoneNumber($this->str($fields['phone_number']));
            $unit->setEmail($this->str($fields['email']));
            $unit->setCategory($this->em->getRepository(UnitCategory::class)->findOneBy(['name' => 'ΣΧΟΛΙΚΕΣ ΜΟΝΑΔΕΣ']));
            $unit->setUnitType($this->findEntityFromOwnDictionary('unit_types', 'name', $fields['unit_type'], UnitType::class, 'name', 'name'));
            $unit->setState($this->em->getRepository(State::class)->find(1));
            $unit->setLegalCharacter($this->em->getRepository(LegalCharacter::class)->find(1));
            $unit->setImplementationEntity($this->em->getRepository(ImplementationEntity::class)->findOneBy(['name' => 'ΙΝΣΤΙΤΟΥΤΟ ΤΕΧΝΟΛΟΓΙΑΣ ΥΠΟΛΟΓΙΣΤΩΝ']));
            $unit->setRegionEduAdmin($this->em->getRepository(RegionEduAdmin::class)->findOneBy(['name' => 'ΔΙΠΟΔΕ']));
            $unit->setEduAdmin($this->findEntityFromOwnDictionary('edu_admins', 'name', $fields['edu_admin'], EduAdmin::class, 'name', 'name'));

            $this->em->persist($unit);
            $this->em->flush();
            $output->writeln('Unit added: ' . $unit->getUnitId());
        }

        $output->writeln('Units imported successfully');

        return Command::SUCCESS;
    }

    /**
     * Same contract as findEntityFromMMDictionary(), but against the app's OWN database
     * (the old command's second PDO pointed at mitr_sus itself).
     *
     * @param class-string $repo
     */
    private function findEntityFromOwnDictionary(string $table, string $idField, mixed $value, string $repo, string $fieldToSearchDb, string $fieldToSearchRepo): ?object
    {
        if ($value == '') {
            return null;
        }
        $query = 'SELECT * FROM `' . $table . '` WHERE ' . $idField . ' = ?';
        $row = $this->em->getConnection()->fetchAssociative($query, [$value]);
        if (!$row) {
            throw new \Exception($query . "\n" . var_export($value, true));
        }
        $entity = $this->em->getRepository($repo)->findOneBy([$fieldToSearchRepo => $row[$fieldToSearchDb]]);
        if (!isset($entity)) {
            throw new \Exception('Entity not found: ' . $table . '.' . $value);
        }

        return $entity;
    }
}
