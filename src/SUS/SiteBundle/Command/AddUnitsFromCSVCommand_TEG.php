<?php
namespace SUS\SiteBundle\Command;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use SUS\SiteBundle\Entity\Unit;
use SUS\SiteBundle\Entity\Workers;

class AddUnitsFromCSVCommand extends ContainerAwareCommand
{
    protected function configure()
    {

        $this
            ->setName('sus:addunitsfromcsv')
            ->setDescription('Import a CSV with line data')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'xls file to import from')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting ImportCSV process');
        $this->container = $this->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();
        $this->pdo = new \PDO('mysql:host=vardb;dbname=mitr_sus;charset=utf8', 'mmuser', 'drHW()34@#');
        $this->cvsParsingOptions = array(
            'ignoreFirstLine' => true
        );
        $xls = $this->parseCSV($input->getOption('file'));
        $headersRow = $xls->getRowIterator(1)->current();
        $headers = $this->parseHeadersToArray($headersRow);
        foreach ($xls->getRowIterator(2) as $row) {
            $fields = $this->parseRowToArray($row, $headers);
            $unit = $this->em->getRepository('SUS\SiteBundle\Entity\Unit')->findOneBy(array(
                'name' => ($fields['UNIT_NAME']),
            ));
            if(isset($unit)) {
                $output->writeln('Skipping unit: '.$unit->getName());
                continue;
            }
            $unit = new Unit();
            $unit->setName($fields['UNIT_NAME']);
            $unit->setCategory($this->em->getRepository('SUS\SiteBundle\Entity\UnitCategory')->findOneBy(array('name' => 'ΣΧΟΛΙΚΕΣ ΜΟΝΑΔΕΣ')));
            $unit->setUnitType($this->em->getRepository('SUS\SiteBundle\Entity\UnitTypes')->findOneBy(array('name' => 'ΤΜΗΜΑ ΕΛΛΗΝΙΚΗΣ ΓΛΩΣΣΑΣ')));
            $unit->setState($this->em->getRepository('SUS\SiteBundle\Entity\States')->find(1));
            $unit->setLegalCharacter($this->em->getRepository('SUS\SiteBundle\Entity\LegalCharacters')->find(1));
            $unit->setImplementationEntity($this->em->getRepository('SUS\SiteBundle\Entity\ImplementationEntities')->findOneBy(array('name' => 'ΙΝΣΤΙΤΟΥΤΟ ΤΕΧΝΟΛΟΓΙΑΣ ΥΠΟΛΟΓΙΣΤΩΝ')));
            $unit->setRegionEduAdmin($this->em->getRepository('SUS\SiteBundle\Entity\RegionEduAdmins')->findOneBy(array('name' => 'ΔΙΠΟΔΕ')));
            $unit->setEduAdmin($this->findEntityFromMMDictionary('edu_admins', 'name', $fields['EDU_ADMIN'], 'SUS\SiteBundle\Entity\EduAdmins', 'name', 'name'));

            $this->em->persist($unit);
            $this->em->flush($unit);
            $output->writeln('Unit added: '.$unit->getUnitId());
        }

        $output->writeln('Units imported successfully');
    }

    private function findEntityFromMMDictionary($table, $idField, $value, $repo, $fieldToSearchDb, $fieldToSearchRepo) {
        if($value == '') { return null; }
        $query = 'SELECT * from '.$table.' WHERE '.$idField.' = "'.trim($value).'"';
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
            $result[] = trim($cell->getValue());
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
