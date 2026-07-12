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
 * Port of old `SUS\SiteBundle\Command\ImportWorkersTmpCommand` (`sus:importworkerstmp`).
 *
 * One-off XLSX import of ΔΙΕΚ directors/deputies. Historical.
 * Columns (Greek headers): `Δ.Ι.Ε.Κ.`, `ΝΕΟΣ ΔΙΕΥΘΥΝΤΗΣ ΑΠΌ ΠΡΟΚΗΡΥΞΗ`, `ΥΠΟΔΙΕΥΘΥΝΤΗΣ`.
 *
 * Old bug preserved: the skip log references a nonexistent `MM_id` column (would notice-fail
 * in the old app; here it just prints an empty value).
 */
#[AsCommand(name: 'sus:importworkerstmp', description: 'Import a CSV with line data')]
final class ImportWorkersTmpCommand extends AbstractXlsxImportCommand
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
                'name' => 'ΙΕΚ ' . $this->str($fields['Δ.Ι.Ε.Κ.']),
            ]);
            if (!isset($unit)) {
                $output->writeln('Skipping unit: ' . ($fields['MM_id'] ?? ''));
                continue;
            }

            if (trim((string) $fields['ΝΕΟΣ ΔΙΕΥΘΥΝΤΗΣ ΑΠΌ ΠΡΟΚΗΡΥΞΗ']) == '') {
                continue;
            }
            $names = explode(' ', (string) $fields['ΝΕΟΣ ΔΙΕΥΘΥΝΤΗΣ ΑΠΌ ΠΡΟΚΗΡΥΞΗ']);
            $worker = $this->em->getRepository(Worker::class)->findOneBy([
                'lastname' => $names[0],
                'firstname' => $names[1] ?? null,
            ]);
            if (!isset($worker)) {
                $worker = new Worker();
                $output->writeln('Worker added: ' . $fields['ΝΕΟΣ ΔΙΕΥΘΥΝΤΗΣ ΑΠΌ ΠΡΟΚΗΡΥΞΗ']);
            } else {
                $output->writeln('Worker found: ' . $fields['ΝΕΟΣ ΔΙΕΥΘΥΝΤΗΣ ΑΠΌ ΠΡΟΚΗΡΥΞΗ']);
            }
            $worker->setLastname($names[0]);
            if (isset($names[1])) {
                $worker->setFirstname($names[1]);
            }
            $worker->setUnit($unit);
            $unit->setManager($worker);

            // Υποδιευθυντές — the deputies column holds entries like "1. ... 2. ..."
            foreach (explode('2.', (string) $fields['ΥΠΟΔΙΕΥΘΥΝΤΗΣ']) as $curName) {
                if (trim(trim($curName), '1.') == '') {
                    continue;
                }
                $names = explode(' ', trim(trim($curName), '1.'));
                $subworker = $this->em->getRepository(Worker::class)->findOneBy([
                    'lastname' => $names[0],
                    'firstname' => $names[1] ?? null,
                ]);
                if (!isset($subworker)) {
                    $subworker = new Worker();
                    $output->writeln('Subworker added: ' . trim(trim($curName), '1.'));
                } else {
                    $output->writeln('Subworker found: ' . trim(trim($curName), '1.'));
                }
                $subworker->setLastname($names[0]);
                if (isset($names[1])) {
                    $subworker->setFirstname($names[1]);
                }
                $unit->getResponsibles()->add($subworker);
                $subworker->getResponsibleUnits()->add($unit);
                $this->em->persist($subworker);
            }

            $this->em->persist($worker);
            $this->em->flush();
        }

        $output->writeln('Workers imported successfully');

        return Command::SUCCESS;
    }
}
