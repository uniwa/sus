<?php
namespace SUS\SiteBundle\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use SUS\SiteBundle\Entity\Unit;
use SUS\SiteBundle\Entity\Workers;

class ImportWorkersCommand extends ContainerAwareCommand
{
    protected function configure()
    {

        $this
            ->setName('sus:importworkers')
            ->setDescription('Import a CSV with line data')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'xls file to import from')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting ImportCSV process');
        $this->container = $this->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();
        $this->pdo = new \PDO('mysql:host=localhost;dbname=mmsch;charset=utf8', 'root', '');
        $mmservice = $this->container->get('sus.mm.service');
        $this->cvsParsingOptions = array(
            'ignoreFirstLine' => true
        );
        $xls = $this->parseCSV($input->getOption('file'));
        $headersRow = $xls->getRowIterator(1)->current();
        $headers = $this->parseHeadersToArray($headersRow);
        foreach ($xls->getRowIterator(2) as $row) {
            $fields = $this->parseRowToArray($row, $headers);
            $unit = $this->em->getRepository('SUS\SiteBundle\Entity\Unit')->findOneBy(array(
                'mmSyncId' => ($fields['MM_id']),
            ));
            if(!isset($unit)) {
                $output->writeln('Skipping unit: '.$fields['MM_id']);
                continue;
            }

            $names = explode(' ', $fields['name']);
            $worker = $this->em->getRepository('SUS\SiteBundle\Entity\Workers')->findOneBy(array(
                'lastname' => $names[0],
                'firstname' => (isset($names[1]) ? $names[1] : null),
            ));
            if(!isset($worker)) {
                $worker = new Workers();
                $output->writeln('Worker added: '.$fields['name']);
            } else {
                $output->writeln('Worker found: '.$fields['name']);
            }
            $worker->setLastname($names[0]);
            if(isset($names[1])) { $worker->setFirstname($names[1]); }
            if($fields['type'] === 'ΥΠΕΥΘΥΝΟΣ ΕΚΠΛΗΝΕΤ') {
                $worker->setUnit($unit);
                $unit->setManager($worker);
            } else {
                $unit->getResponsibles()->add($worker);
                $worker->getResponsibleUnits()->add($unit);
            }
            $this->em->persist($worker);
            $this->em->flush(array($unit, $worker));
        }

        $output->writeln('Units imported successfully');
    }

    private function findEntityFromMMDictionary($table, $idField, $value, $repo, $fieldToSearchDb, $fieldToSearchRepo) {
        if($value == '') { return null; }
        $query = 'SELECT * from '.$table.' WHERE '.$idField.' = '.$value;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        if(!$row) { throw new \Exception($query."\n".var_export($stmt->errorinfo(), true)); }
        $entity = $this->em->getRepository($repo)->findOneBy(array($fieldToSearchRepo => $row[$fieldToSearchDb]));
        if(!isset($entity)) { throw new \Exception('Entity not found: '.$table.'.'.$value); }
        return $entity;
    }

    private function parseHeadersToArray($headersRow) {
        $cellIterator = $headersRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false); 
        $result = array();
        foreach ($cellIterator as $cell) {
            $result[] = $cell->getValue();
        }
        return $result;
    }

    private function parseRowToArray($row, $headers) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false); 
        $result = array();
        $i = 0;
        foreach ($cellIterator as $cell) {
            $result[$headers[$i]] = $cell->getValue();
            $i++;
        }
        return $result;
    }

    private function parseCSV($file)
    {
        $ignoreFirstLine = $this->cvsParsingOptions['ignoreFirstLine'];

        $finder = new Finder();
        $finder->files()->in(dirname($file))->name(basename($file));
        ;
        foreach ($finder as $file) { $csv = $file; }

        $phpExcelObject = $this->getContainer()->get('xls.load_xls2007')->load($csv->getRealPath());
        $sheet = $phpExcelObject->getSheet(0);
        //$objReader = PHPExcel_IOFactory::createReader($inputFileType);
        return $sheet;
    }

    private function fixInconsistencies() {
        /*
Fix incorrect type ids
----------------------------------------
# Dialup with 2mbps
UPDATE Circuit SET type_id = 2 WHERE type_id = 1 and LOWER(bandwidth) = '2mbps';
UPDATE Circuit SET type_id = 2 WHERE type_id = 1 and LOWER(bandwidth) = '2 mbps';

# Dialup with 24mbps
UPDATE Circuit SET type_id = 2 WHERE type_id = 1 and LOWER(bandwidth) = '24mbps';
UPDATE Circuit SET type_id = 2 WHERE type_id = 1 and LOWER(bandwidth) = '24 mbps';

# ISDN with 2mbps
UPDATE Circuit SET type_id = 5 WHERE type_id = 4 and LOWER(bandwidth) = '2mbps';
UPDATE Circuit SET type_id = 5 WHERE type_id = 4 and LOWER(bandwidth) = '2 mbps';

# ISDN with 24mbps
UPDATE Circuit SET type_id = 5 WHERE type_id = 4 and LOWER(bandwidth) = '24mbps';
UPDATE Circuit SET type_id = 5 WHERE type_id = 4 and LOWER(bandwidth) = '24 mbps';

# ISDN ADSL with 128kbps
UPDATE Circuit SET bandwidth = '24mbps' WHERE type_id = 5 and LOWER(bandwidth) = '128kbps';
UPDATE Circuit SET bandwidth = '24mbps' WHERE type_id = 5 and LOWER(bandwidth) = '128 kbps';

Set bandwidth profiles
----------------------------------------
# ADSL 2 Mbps
UPDATE Circuit SET bandwidth_profile_id = 10, bandwidth = NULL WHERE type_id = 2 and LOWER(bandwidth) = '2mbps';
UPDATE Circuit SET bandwidth_profile_id = 10, bandwidth = NULL WHERE type_id = 2 and LOWER(bandwidth) = '2 mbps';

# ADSL 24 Mbps
UPDATE Circuit SET bandwidth_profile_id = 10, bandwidth = NULL WHERE type_id = 2 and LOWER(bandwidth) = '24mbps';
UPDATE Circuit SET bandwidth_profile_id = 10, bandwidth = NULL WHERE type_id = 2 and LOWER(bandwidth) = '24 mbps';

# ISDN 128kbps
UPDATE Circuit SET bandwidth_profile_id = 2, bandwidth = NULL WHERE type_id = 4 and LOWER(bandwidth) = '128kbps';
UPDATE Circuit SET bandwidth_profile_id = 2, bandwidth = NULL WHERE type_id = 4 and LOWER(bandwidth) = '128 kbps';
         */
    }
}