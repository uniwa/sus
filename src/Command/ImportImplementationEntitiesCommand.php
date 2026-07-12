<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ImplementationEntity;
use App\Entity\Unit;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Port of old `SUS\SiteBundle\Command\ImportImplementationEntitiesCommand`
 * (`sus:importimplementationentities`).
 *
 * One-off XLSX import: sets the implementation entity (ΦΥ) per unit. Historical.
 * Columns: MM_id, FY. A missing/unknown FY silently sets NULL (old behavior preserved).
 *
 * The old code opened a PDO connection to the MM database that it never used — not reproduced.
 */
#[AsCommand(name: 'sus:importimplementationentities', description: 'Import a CSV with line data')]
final class ImportImplementationEntitiesCommand extends AbstractXlsxImportCommand
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

            $implementationEntity = ($fields['FY'] ?? '') != ''
                ? $this->em->getRepository(ImplementationEntity::class)->findOneBy(['implementationEntityId' => (int) $fields['FY']])
                : null;
            $unit->setImplementationEntity($implementationEntity);
            $this->em->persist($unit);
            $this->em->flush();
        }

        $output->writeln('Implementation entities imported successfully');

        return Command::SUCCESS;
    }
}
