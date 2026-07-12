<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ImplementationEntity;
use App\Entity\Municipality;
use App\Entity\Prefecture;
use App\Entity\State;
use App\Entity\Unit;
use App\Entity\UnitCategory;
use App\Entity\UnitType;
use App\Entity\Worker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Port of old `SUS\SiteBundle\Command\ImportCSVCommand` (`sus:importcsv`).
 *
 * One-off XLSX import of ΚΟΜΒΟΣ ΠΣΔ network nodes. Historical.
 *
 * Columns: NAME, street_address, street_address_num, TK, MUNICIPALITY_ID, PERFECTURE_ID (sic),
 * IMPLEMENTATION_ENTITY_ID, TELEPHONE, RESPONSIBLE.
 *
 * Dictionary lookups (municipality/prefecture/implementation entity by numeric id → name) go
 * against the MM database via MMSCH_DB_DSN (the old code hardcoded root@localhost/mmsch).
 */
#[AsCommand(name: 'sus:importcsv', description: 'Import a CSV with line data')]
final class ImportCSVCommand extends AbstractXlsxImportCommand
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
                'name' => $fields['NAME'],
            ]);
            if (isset($unit)) {
                $output->writeln('Skipping unit: ' . $unit->getName());
                continue;
            }
            $unit = new Unit();
            $unit->setName($this->str($fields['NAME']));
            $unit->setCategory($this->em->getRepository(UnitCategory::class)->findOneBy(['name' => 'ΔΙΚΤΥΑΚΕΣ ΟΝΤΟΤΗΤΕΣ ΠΣΔ']));
            $unit->setUnitType($this->em->getRepository(UnitType::class)->findOneBy(['name' => 'ΚΟΜΒΟΣ ΠΣΔ']));
            $unit->setStreetAddress($this->str($fields['street_address']) . ' ' . $this->str($fields['street_address_num']));
            $unit->setPostalCode($this->intOrNull($fields['TK']));
            $unit->setMunicipality($this->findEntityFromMMDictionary('municipalities', 'municipality_id', $fields['MUNICIPALITY_ID'], Municipality::class, 'name', 'name'));
            $unit->setPrefecture($this->findEntityFromMMDictionary('prefectures', 'prefecture_id', $fields['PERFECTURE_ID'], Prefecture::class, 'name', 'name'));
            $unit->setImplementationEntity($this->findEntityFromMMDictionary('implementation_entities', 'implementation_entity_id', $fields['IMPLEMENTATION_ENTITY_ID'], ImplementationEntity::class, 'name', 'name'));
            $unit->setPhoneNumber($this->str($fields['TELEPHONE']));
            $unit->setState($this->em->getRepository(State::class)->find(1));

            $this->em->persist($unit);
            $this->em->flush();
            $output->writeln('Unit added: ' . $unit->getUnitId());

            if (($fields['RESPONSIBLE'] ?? '') != '') {
                $names = explode(' ', (string) $fields['RESPONSIBLE']);
                $worker = $this->em->getRepository(Worker::class)->findOneBy([
                    'lastname' => $names[0],
                    'firstname' => $names[1] ?? null,
                ]);
                if (!isset($worker)) {
                    $worker = new Worker();
                    $worker->setUnit($unit);
                    // Old cosmetic bug preserved: the found/added log strings are swapped.
                    $output->writeln('Worker found: ' . $fields['RESPONSIBLE']);
                } else {
                    $output->writeln('Worker added: ' . $fields['RESPONSIBLE']);
                }
                $worker->setLastname($names[0]);
                if (isset($names[1])) {
                    $worker->setFirstname($names[1]);
                }
                $unit->setManager($worker);
                $this->em->persist($worker);
                $this->em->flush();
            }
        }

        $output->writeln('Units imported successfully');

        return Command::SUCCESS;
    }
}
