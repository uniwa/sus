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
 * Port of old `SUS\SiteBundle\Command\ImportKathigitesPliroforikisCommand`
 * (`sus:kathigitespliroforikis`).
 *
 * One-off XLSX import — the body is IDENTICAL to `sus:importworkers` in the old app except
 * for the id column name (`mm_id` lowercase here vs `MM_id`). Historical; kept as a separate
 * command so both old names keep working.
 */
#[AsCommand(name: 'sus:kathigitespliroforikis', description: 'Import a CSV with line data')]
final class ImportKathigitesPliroforikisCommand extends AbstractXlsxImportCommand
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
            $unit = ($fields['mm_id'] ?? '') != ''
                ? $this->em->getRepository(Unit::class)->findOneBy(['mmSyncId' => (int) $fields['mm_id']])
                : null;
            if (!isset($unit)) {
                $output->writeln('Skipping unit: ' . $fields['mm_id']);
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
