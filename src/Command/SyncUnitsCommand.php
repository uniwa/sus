<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Unit;
use App\Entity\Worker;
use App\Service\MMService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Port of old `SUS\SiteBundle\Command\SyncUnitsCommand` (`sus:syncunits`).
 *
 * THE nightly cron job: pushes every unit whose MM sync stamp is missing or older than its
 * `updatedAt` (and whose type is in the MM sync whitelist) to the MM registry API.
 * The production crontab must call `bin/console sus:syncunits` (name preserved).
 *
 * The whitelist of unit types is single-sourced from {@see MMService::ALLOWED_UNIT_TYPES}
 * (the old command inlined a character-identical copy of the list in its DQL).
 *
 * Workers phase: in the OLD app this phase was dead code — `Workers` no longer mapped the
 * `mmSyncId`/`mmSyncLastUpdateDate` fields, so the workers DQL threw a QueryException every
 * night right after the units loop (and the final flush was never reached). The new `Worker`
 * entity maps those fields again (real DB columns), so the phase WOULD run now; to preserve
 * the effective production behavior (workers never synced) it is gated behind the opt-in
 * `--sync-workers` flag instead of being reproduced unconditionally.
 */
#[AsCommand(name: 'sus:syncunits', description: 'Sync units with MM')]
final class SyncUnitsCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MMService $mmService,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[Option(description: 'Also sync workers with MM (dead code in the old app — off by default)', name: 'sync-workers')]
        bool $syncWorkers = false,
    ): int {
        $output->writeln('Starting SyncUnits process');
        $batchSize = 20;
        $i = 0;

        // Units
        $q = $this->em->createQuery(
            'SELECT pc FROM ' . Unit::class . ' pc
             JOIN pc.unitType ut
             WHERE (pc.mmSyncLastUpdateDate IS NULL OR pc.mmSyncLastUpdateDate < pc.updatedAt)
               AND ut.name IN (:allowedTypes)'
        )->setParameter('allowedTypes', MMService::ALLOWED_UNIT_TYPES);

        foreach ($q->toIterable() as $row) {
            /** @var Unit $row */
            $output->write('Syncing unit ' . $row->getUnitId() . ' ' . $row->getName() . '...');
            $this->mmService->persistMM($row);
            $output->writeln(' got ' . $row->getMmSyncId());
            // Old quirk preserved: the modulo check runs BEFORE the increment, so a flush also
            // happens at i=0.
            if (($i % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }
            ++$i;
        }
        // The old code never flushed here (its trailing flush sat after the workers loop, which
        // crashed before reaching it, silently dropping up to 19 trailing units' sync stamps per
        // run). Flushing here fixes that without changing what gets synced.
        $this->em->flush();
        $output->writeln('Units synced successfully');

        // Workers (opt-in — see class docblock)
        if ($syncWorkers) {
            $q = $this->em->createQuery(
                'SELECT pc FROM ' . Worker::class . ' pc WHERE pc.mmSyncLastUpdateDate IS NULL'
            );
            foreach ($q->toIterable() as $row) {
                /** @var Worker $row */
                $output->write('Syncing worker ' . $row->getWorkerId() . ' ' . $row->getFirstname() . ' ' . $row->getLastname() . '...');
                $this->mmService->persistMM($row);
                $output->writeln(' got ' . $row->getMmSyncId());
                if (($i % $batchSize) === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
                ++$i;
            }

            $this->em->flush();
            $output->writeln('Workers synced successfully');
        }

        return Command::SUCCESS;
    }
}
