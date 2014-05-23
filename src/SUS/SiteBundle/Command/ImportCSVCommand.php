<?php
namespace SUS\SiteBundle\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use SUS\SiteBundle\Entity\Unit;
use SUS\SiteBundle\Entity\Workers;

class ImportCSVCommand extends ContainerAwareCommand
{
    protected function configure()
    {

        $this
            ->setName('sus:importcsv')
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
                'name' => ($fields['NAME']),
            ));
            if(isset($unit)) {
                $output->writeln('Skipping unit: '.$unit->getName());
                continue;
            }
            $unit = new Unit();
            $unit->setName($fields['NAME']);
            $unit->setCategory($this->em->getRepository('SUS\SiteBundle\Entity\UnitCategory')->findOneBy(array('name' => 'ΔΙΚΤΥΑΚΕΣ ΟΝΤΟΤΗΤΕΣ ΠΣΔ')));
            $unit->setUnitType($this->em->getRepository('SUS\SiteBundle\Entity\UnitTypes')->findOneBy(array('name' => 'ΚΟΜΒΟΣ ΠΣΔ')));
            $unit->setStreetAddress($fields['street_address'].' '.$fields['street_address_num']);
            $unit->setPostalCode($fields['TK']);
            $unit->setMunicipality($this->findEntityFromMMDictionary('municipalities', 'municipality_id', $fields['MUNICIPALITY_ID'], 'SUS\SiteBundle\Entity\Municipalities', 'name', 'name'));
            $unit->setPrefecture($this->findEntityFromMMDictionary('prefectures', 'prefecture_id', $fields['PERFECTURE_ID'], 'SUS\SiteBundle\Entity\Prefectures', 'name', 'name'));
            $unit->setImplementationEntity($this->findEntityFromMMDictionary('implementation_entities', 'implementation_entity_id', $fields['IMPLEMENTATION_ENTITY_ID'], 'SUS\SiteBundle\Entity\ImplementationEntities', 'name', 'name'));
            $unit->setPhoneNumber($fields['TELEPHONE']);
            $unit->setState($this->em->getRepository('SUS\SiteBundle\Entity\States')->find(1));

            $this->em->persist($unit);
            $this->em->flush($unit);
            $output->writeln('Unit added: '.$unit->getUnitId());

            if($fields['RESPONSIBLE'] != '') {
                $names = explode(' ', $fields['RESPONSIBLE']);
                $worker = $this->em->getRepository('SUS\SiteBundle\Entity\Workers')->findOneBy(array(
                    'lastname' => $names[0],
                    'firstname' => (isset($names[1]) ? $names[1] : null),
                ));
                if(!isset($worker)) {
                    $worker = new Workers();
                    $worker->setUnit($unit);
                    $output->writeln('Worker found: '.$fields['RESPONSIBLE']);
                } else {
                    $output->writeln('Worker added: '.$fields['RESPONSIBLE']);
                }
                $worker->setLastname($names[0]);
                if(isset($names[1])) { $worker->setFirstname($names[1]); }
                $unit->setManager($worker);
                $this->em->persist($worker);
                $this->em->flush(array($unit, $worker));
            }
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