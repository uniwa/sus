<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Unit;
use App\Entity\Worker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Port of old `SUS\SiteBundle\Command\ImportWorkersCommand` (`sus:importworkers`).
 *
 * One-off XLSX import of ΚΕΠΛΗΝΕΤ workers. Historical.
 * Columns: MM_id, name, type. `sus:kathigitespliroforikis` is an identical copy of this
 * command in the old app (only the id column is lowercase there).
 *
 * The old code opened a PDO connection to the MM database that it never used — not reproduced
 * (the shared lazy connection only opens when a dictionary lookup actually runs).
 */
#[AsCommand(name: 'sus:importworkers', description: 'Import a CSV with line data')]
final class ImportWorkersCommand extends AbstractXlsxImportCommand
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
            $unit = ($fields['MM_id'] ?? '') != ''
                ? $this->em->getRepository(Unit::class)->findOneBy(['mmSyncId' => (int) $fields['MM_id']])
                : null;
            if (!isset($unit)) {
                $output->writeln('Skipping unit: ' . $fields['MM_id']);
                continue;
            }

            $names = explode(' ', (string) $fields['name']);
            $worker = $this->em->getRepository(Worker::class)->findOneBy([
                'lastname' => $names[0],
                'firstname' => $names[1] ?? null,
            ]);
            if (!isset($worker)) {
                $worker = new Worker();
                $output->writeln('Worker added: ' . $fields['name']);
            } else {
                $output->writeln('Worker found: ' . $fields['name']);
            }
            $worker->setLastname($names[0]);
            if (isset($names[1])) {
                $worker->setFirstname($names[1]);
            }
            if ($fields['type'] === 'ΥΠΕΥΘΥΝΟΣ ΕΚΠΛΗΝΕΤ') {
                $worker->setUnit($unit);
                $unit->setManager($worker);
            } else {
                $unit->getResponsibles()->add($worker);
                $worker->getResponsibleUnits()->add($unit);
            }
            $this->em->persist($worker);
            $this->em->flush();
        }

        $output->writeln('Workers imported successfully');

        return Command::SUCCESS;
    }
}
