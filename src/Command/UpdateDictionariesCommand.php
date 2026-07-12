<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Unit;
use App\Service\MMService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Port of old `SUS\SiteBundle\Command\UpdateDictionariesCommand` (`sus:updatedictionaries`).
 *
 * Maintenance utility: for every MM-synced unit, fetch its record from the MM API and
 * overwrite the local dictionary rows' names (edu admin, region edu admin, implementation
 * entity, unit type, prefecture, municipality, category) with the MM values.
 *
 * Old quirks preserved:
 *  - one API GET per unit (slow); an MMException (0 or >1 results) aborts the whole run;
 *  - dictionary rows are SHARED — the last unit iterated wins for a shared row;
 *  - MM returns `implementation_entity` here as a NAME while the push side sends the ID
 *    (asymmetry in the MM API — preserve as-is);
 *  - the Workers half of the old command was fully commented out — not ported.
 */
#[AsCommand(name: 'sus:updatedictionaries', description: 'Update dictionaries with data from MM')]
final class UpdateDictionariesCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MMService $mmService,
    ) {
    }

    public function __invoke(OutputInterface $output): int
    {
        $output->writeln('Starting UpdateDictionaries process');
        $batchSize = 20;
        $i = 0;

        $q = $this->em->createQuery(
            'SELECT pc FROM ' . Unit::class . ' pc WHERE pc.mmSyncLastUpdateDate IS NOT NULL'
        );
        foreach ($q->toIterable() as $row) {
            /** @var Unit $row */
            $output->write('Updating dictionaries of unit ' . $row->getUnitId() . ' ' . $row->getName() . '...');
            $mmUnit = $this->mmService->findOneUnitBy(['mm_id' => $row->getMmSyncId()]);

            if ($row->getEduAdmin() !== null && ($mmUnit->edu_admin ?? '') != '') {
                $row->getEduAdmin()->setName($mmUnit->edu_admin);
                $this->em->persist($row->getEduAdmin());
            }

            if ($row->getRegionEduAdmin() !== null && ($mmUnit->region_edu_admin ?? '') != '') {
                $row->getRegionEduAdmin()->setName($mmUnit->region_edu_admin);
                $this->em->persist($row->getRegionEduAdmin());
            }

            if ($row->getImplementationEntity() !== null && ($mmUnit->implementation_entity ?? '') != '') {
                $row->getImplementationEntity()->setName($mmUnit->implementation_entity);
                $this->em->persist($row->getImplementationEntity());
            }

            if ($row->getUnitType() !== null && ($mmUnit->unit_type ?? '') != '') {
                $row->getUnitType()->setName($mmUnit->unit_type);
                $this->em->persist($row->getUnitType());
            }

            if ($row->getPrefecture() !== null && ($mmUnit->prefecture ?? '') != '') {
                $row->getPrefecture()->setName($mmUnit->prefecture);
                $this->em->persist($row->getPrefecture());
            }

            if ($row->getMunicipality() !== null && ($mmUnit->municipality ?? '') != '') {
                $row->getMunicipality()->setName($mmUnit->municipality);
                $this->em->persist($row->getMunicipality());
            }

            if ($row->getCategory() !== null && ($mmUnit->category ?? '') != '') {
                $row->getCategory()->setName($mmUnit->category);
                $this->em->persist($row->getCategory());
            }

            if (($i % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }
            ++$i;
        }
        $this->em->flush();
        // Old output string preserved verbatim (it said "synced" here too).
        $output->writeln('Units synced successfully');

        return Command::SUCCESS;
    }
}
