<?php
namespace SUS\SiteBundle\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDictionariesCommand extends ContainerAwareCommand
{
    protected function configure()
    {

        $this
            ->setName('sus:updatedictionaries')
            ->setDescription('Update dictionaries with data from MM')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting UpdateDictionaries process');
        $this->container = $this->getContainer();
        $em = $this->container->get('doctrine')->getManager();
        $mmservice = $this->container->get('sus.mm.service');
        $batchSize = 20;
        $i = 0;
        // Units
        $q = $em->createQuery('select pc from SUS\SiteBundle\Entity\Unit pc WHERE pc.mmSyncLastUpdateDate IS NOT NULL');
        $iterableResult = $q->iterate();
        foreach($iterableResult AS $row) {
            $row = $row[0];
            $output->write('Updating dictionaries of unit '.$row->getUnitId().' '.$row->getName().'...');
            $mmUnit = $mmservice->findOneUnitBy(array('mm_id' => $row->getMmSyncId()));

            $row->getEduAdmin()->setName($mmUnit->edu_admin);
            $em->persist($row->getEduAdmin());

            $row->getRegionEduAdmin()->setName($mmUnit->region_edu_admin);
            $em->persist(getRegionEduAdmin());

            $row->getImplementationEntity()->setName($mmUnit->implementation_entity);
            $em->persist($row->getImplementationEntity());

            $row->getUnitType()->setName($mmUnit->unit_type);
            $em->persist($row->getUnitType());

            $row->getPrefecture()->setName($mmUnit->prefecture);
            $em->persist($row->getPrefecture());

            $row->getMunicipality()->setName($mmUnit->municipality);
            $em->persist($row->getMunicipality());

            $row->getCategory()->setName($mmUnit->category);
            $em->persist($row->getCategory());
            
            if (($i % $batchSize) == 0) {
                $em->flush();
                $em->clear();
            }
            ++$i;
        }
        $output->writeln('Units synced successfully');
        // Workers
        // Units
        /*$q = $em->createQuery('select pc from SUS\SiteBundle\Entity\Workers pc WHERE pc.mmSyncLastUpdateDate IS NOT NULL');
        $iterableResult = $q->iterate();
        foreach($iterableResult AS $row) {
            $row = $row[0];
            $output->write('Syncing worker '.$row->getWorkerId().' '.$row->getFirstname().' '.$row->getLastname().'...');
            $mmservice->persistMM($row);
            $output->writeln(' got '.$row->getMmSyncId());
            if (($i % $batchSize) == 0) {
                $em->flush();
                $em->clear();
            }
            ++$i;
        }
        $output->writeln('Workers synced successfully');*/
    }
}