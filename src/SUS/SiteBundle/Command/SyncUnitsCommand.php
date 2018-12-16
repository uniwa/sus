<?php
namespace SUS\SiteBundle\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncUnitsCommand extends ContainerAwareCommand
{
    protected function configure()
    {

        $this
            ->setName('sus:syncunits')
            ->setDescription('Sync units with MM')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //set allowed unit types to sync with mm
        $allowedTypes = "(  'ΚΕΣΥΠ', 'ΓΡΑΣΕΠ', 'ΣΣΝ', 'ΚΕΠΛΗΝΕΤ', 'ΕΚΦΕ', 'ΚΠΕ', 'ΠΕΚ', 
                            'ΕΡΓΑΣΤΗΡΙΑ ΦΥΣΙΚΩΝ ΕΠΙΣΤΗΜΩΝ', 'ΣΧΟΛΙΚΕΣ ΒΙΒΛΙΟΘΗΚΕΣ',
                            'ΓΕΝΙΚΟ ΑΡΧΕΙΟ ΚΡΑΤΟΥΣ', 'ΔΗΜΟΣΙΕΣ ΒΙΒΛΙΟΘΗΚΕΣ', 'ΚΟΜΒΟΣ ΠΣΔ', 
                            'ΣΧΟΛΙΚΗ ΕΠΙΤΡΟΠΗ ΠΡΩΤΟΒΑΘΜΙΑΣ', 'ΣΧΟΛΙΚΗ ΕΠΙΤΡΟΠΗ ΔΕΥΤΕΡΟΒΑΘΜΙΑΣ',
                            'ΣΧΟΛΕΙΟ ΔΕΥΤΕΡΗΣ ΕΥΚΑΙΡΙΑΣ', 'ΙΝΣΤΙΤΟΥΤΟ ΕΠΑΓΓΕΛΜΑΤΙΚΗΣ ΚΑΤΑΡΤΙΣΗΣ', 
                            'ΣΧΟΛΗ ΕΠΑΓΓΕΛΜΑΤΙΚΗΣ ΚΑΤΑΡΤΙΣΗΣ', 'HELPDESK ΦΟΡΕΩΝ ΥΛΟΠΟΙΗΣΗΣ ΤΟΥ ΠΣΔ',
                            'ΟΜΟΣΠΟΝΔΙΑ','ΕΛΜΕ','ΜΟΝΑΔΕΣ ΑΛΛΩΝ ΥΠΟΥΡΓΕΙΩΝ','ΔΗΜΟΤΙΚΕΣ ΒΙΒΛΙΟΘΗΚΕΣ','ΕΚΚΛΗΣΙΑΣΤΙΚΟ' )";
        
        $output->writeln('Starting SyncUnits process');
        $this->container = $this->getContainer();
        $em = $this->container->get('doctrine')->getManager();
        $mmservice = $this->container->get('sus.mm.service');
        $batchSize = 20;
        $i = 0;
        // Units
        $q = $em->createQuery("SELECT pc FROM SUS\SiteBundle\Entity\Unit pc
                               JOIN pc.unitType ut
                               WHERE (pc.mmSyncLastUpdateDate IS NULL OR pc.mmSyncLastUpdateDate < pc.updatedAt) AND ut.name IN $allowedTypes ");
        
        $iterableResult = $q->iterate();
        foreach($iterableResult AS $row) {
            $row = $row[0];
            $output->write('Syncing unit '.$row->getUnitId().' '.$row->getName().'...');
            $mmservice->persistMM($row);
            $output->writeln(' got '.$row->getMmSyncId());
            //row->getMmSyncId()==null)? $output->writeln(' NULL FAILURE '):$output->writeln(' SUCCESS ');
            if (($i % $batchSize) == 0) {
                $em->flush();
                $em->clear();
            }
            ++$i;
        }
        $output->writeln('Units synced successfully');
        // Workers
        // Units
        $q = $em->createQuery('SELECT pc FROM SUS\SiteBundle\Entity\Workers pc WHERE pc.mmSyncLastUpdateDate IS NULL');
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
        
        $em->flush();
        $output->writeln('Workers synced successfully');
    }
}
