<?php

namespace SUS\SiteBundle\Extension;

use SUS\SiteBundle\Exception\MMException;
use SUS\SiteBundle\Entity\Unit;
use SUS\SiteBundle\Entity\Workers;
use SUS\SiteBundle\Extension\MMSyncableListener;
use SUS\SiteBundle\Entity\MMSyncableEntity;

class MMService {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    protected function allowedUnitTypesToMMSync( $unit_type ) {

        $allowed = array (  'ΚΕΣΥΠ', 'ΓΡΑΣΕΠ', 'ΣΣΝ', 'ΚΕΠΛΗΝΕΤ', 'ΕΚΦΕ', 'ΚΠΕ/ΚΕΠΕΑ', 'ΠΕΚ', 
                            'ΕΡΓΑΣΤΗΡΙΑ ΦΥΣΙΚΩΝ ΕΠΙΣΤΗΜΩΝ', 'ΣΧΟΛΙΚΕΣ ΒΙΒΛΙΟΘΗΚΕΣ',
                            'ΓΕΝΙΚΟ ΑΡΧΕΙΟ ΚΡΑΤΟΥΣ', 'ΔΗΜΟΣΙΕΣ ΒΙΒΛΙΟΘΗΚΕΣ', 'ΚΟΜΒΟΣ ΠΣΔ', 
                            'ΣΧΟΛΙΚΗ ΕΠΙΤΡΟΠΗ ΠΡΩΤΟΒΑΘΜΙΑΣ', 'ΣΧΟΛΙΚΗ ΕΠΙΤΡΟΠΗ ΔΕΥΤΕΡΟΒΑΘΜΙΑΣ',
                            'ΣΧΟΛΕΙΟ ΔΕΥΤΕΡΗΣ ΕΥΚΑΙΡΙΑΣ', 'ΙΝΣΤΙΤΟΥΤΟ ΕΠΑΓΓΕΛΜΑΤΙΚΗΣ ΚΑΤΑΡΤΙΣΗΣ',
                            'ΣΧΟΛΗ ΕΠΑΓΓΕΛΜΑΤΙΚΗΣ ΚΑΤΑΡΤΙΣΗΣ', 'HELPDESK ΦΟΡΕΩΝ ΥΛΟΠΟΙΗΣΗΣ ΤΟΥ ΠΣΔ',
                            'ΟΜΟΣΠΟΝΔΙΑ','ΕΛΜΕ','ΜΟΝΑΔΕΣ ΑΛΛΩΝ ΥΠΟΥΡΓΕΙΩΝ','ΔΗΜΟΤΙΚΕΣ ΒΙΒΛΙΟΘΗΚΕΣ','ΕΚΚΛΗΣΙΑΣΤΙΚΟ', 
                            'ΤΜΗΜΑ ΕΛΛΗΝΙΚΗΣ ΓΛΩΣΣΑΣ', 'ΝΗΠΙΑΓΩΓΕΙΟ', 'ΔΗΜΟΤΙΚΟ', 'ΓΥΜΝΑΣΙΟ', 'ΓΕΝΙΚΟ ΛΥΚΕΙΟ',
			    'ΣΥΝΤΟΝΙΣΤΙΚΕΣ ΜΟΝΑΔΕΣ ΕΚΠΑΙΔΕΥΣΗΣ ΕΞΩΤΕΡΙΚΟΥ', 'ΠΕ.Κ.Ε.Σ.','ΕΠΑΓΓΕΛΜΑΤΙΚΗ ΣΧΟΛΗ ΚΑΤΑΡΤΙΣΗΣ',
                            'ΣΧΟΛΕΣ ΑΝΩΤΕΡΗΣ ΕΠΑΓΓΕΛΜΑΤΙΚΗΣ ΚΑΤΑΡΤΙΣΗΣ' );

        $syncToMM = (in_array($unit_type, $allowed) ? true : false );

        return $syncToMM;
    }

    public function findUnit($mmid) {
        $em = $this->container->get('doctrine')->getManager();
        $repo = $em->getRepository('SUS\SiteBundle\Entity\Unit');
        $unit = $repo->find($mmid);
        $yesterday = new \DateTime('yesterday');
        if(!isset($unit) || $unit->getUpdatedAt() < $yesterday) {
            // Query the MM and try to find the unit
            $mmUnitEntries = $this->queryUnits(array(
                'mm_id' => $mmid,
                'count' => 1,
            ));
            if(count($mmUnitEntries) == 1) {
                $unit = $this->hydrateUnit($mmUnitEntries[0]);
            } elseif(count($mmUnitEntries) > 1) {
                throw new MMException('Found more than one unit: '.count($mmUnitEntries));
            } else {
                $unit = null;
            }
        }
        return $unit;
    }

    public function findUnitsBy(array $filters = array()) {
        $results = array();
        $params = array('searchtype' => 'EXACT');
        if(isset($filters['mm_id']) && $filters['mm_id'] != '') {
            $params['mm_id'] = $filters['mm_id'];
        }
        if(isset($filters['registry_no']) && $filters['registry_no'] != '') {
            $params['registry_no'] = $filters['registry_no'];
        }
        if(isset($filters['name']) && $filters['name'] != '') {
            $params['name'] = $filters['name'];
        }
        if(isset($filters['fy']) && $filters['fy'] != '') {
            $params['implementation_entity'] = $filters['fy'];
        }
        if(isset($filters['ldapuid']) && $filters['ldapuid'] != '') {
            /* ldap – Πίνακας λογαριασμών ldap
            Πεδίο	Τύπος	Όνομα Πεδίου	Περιγραφή
            ldap_id	int(11)		Ο κωδικός του λογαριασμού ldap
            uid	varchar(255)		To uid του λογαριασμού ldap
            unit_id	int(11)		Η μοναδα που ανήκει ο λογαριασμός ldap */
            $params['mm_id'] = '1000003';
        }
        $mmUnitEntries = $this->queryUnits($params);
        /*foreach($mmUnitEntries as $curMmUnitEntry) {
            $results[] = $this->hydrateUnit($curMmUnitEntry);
        }
        $this->container->get('doctrine')->getManager()->flush($results);
        return $results;*/
        return $mmUnitEntries;
    }

    public function findWorkersBy(array $filters = array()) {
        $params = array();
        if(isset($filters['registry_no']) && $filters['registry_no'] != '') {
            $params['registry_no'] = $filters['registry_no'];
        }
        if(isset($filters['worker']) && $filters['worker'] != '') {
            $params['worker'] = $filters['worker'];
        }

        $mmUnitEntries = $this->queryWorkers($params);
        return $mmUnitEntries;
    }

    public function findOneUnitBy(array $filters = array()) {
        $units = $this->findUnitsBy($filters+array('limit' => 1));
        if(!isset($units[0])) {
            throw new MMException('The unit was not found');
        }
        if(count($units) > 1) {
            throw new MMException('Found more than one unit: '.count($units));
        }
        return $units[0];
    }

    public function persistMM(MMSyncableEntity $entity) {
        if($entity instanceof Unit) {
            return $this->persistUnit($entity);
        } elseif($entity instanceof Workers) {
            return $this->persistWorker($entity);
        } else {
            throw new MMException('Unsupported entity');
        }
    }

    protected function hydrateUnit($entry, $flush = false) {
        throw new \Exception('Not supported');
        // Unit not found or its too old. Query the WS for fresh data.
        /*$em = $this->container->get('doctrine')->getManager();

        $unit = new Unit;
        $unit->setMmId($entry->mm_id);
        $unit->setUnitId($entry->mm_id);
        $unit->setState($em->find('SUS\SiteBundle\Entity\States', $entry->state));
        $unit->setName($entry->name);
        $unit->setPostalCode($entry->postal_code);
        $unit->setRegistryNo($entry->registry_no);
        $unit->setStreetAddress($entry->street_address);
        $unit->setCategoryName($entry->category);
        $unit->setCreatedAt(new \DateTime('now'));
        $unit->setUpdatedAt(new \DateTime('now'));

        $unit = $em->merge($unit);
        if($flush == true) {
            $em->flush($unit);
        }

        return $unit;*/
    }

    protected function queryUnits($params = array()) {
        if(!isset($params['limit']) || $params['limit'] == '') {
            $params['count'] = 10;
        } else {
            $params['count'] = $params['limit'];
        }
        if(!isset($params['startat']) || $params['startat'] == '') {
            $params['startat'] = 0;
        }
        /*if(!isset($params['category']) || $params['category'] == '') {
            "category" => "ΣΧΟΛΙΚΕΣ ΚΑΙ ΔΙΟΙΚΗΤΙΚΕΣ ΜΟΝΑΔΕΣ",
        }*/
        return $this->queryMM('units', $params);
    }

    protected function queryWorkers($params = array()) {
        if(!isset($params['limit']) || $params['limit'] == '') {
            $params['count'] = 10;
        } else {
            $params['count'] = $params['limit'];
        }
        if(!isset($params['startat']) || $params['startat'] == '') {
            $params['startat'] = 0;
        }
        /*if(!isset($params['category']) || $params['category'] == '') {
            "category" => "ΣΧΟΛΙΚΕΣ ΚΑΙ ΔΙΟΙΚΗΤΙΚΕΣ ΜΟΝΑΔΕΣ",
        }*/
        return $this->queryMM('workers', $params);
    }

    protected function queryUnitWorkers($params = array()) {
        if(!isset($params['limit']) || $params['limit'] == '') {
            $params['count'] = 10;
        } else {
            $params['count'] = $params['limit'];
        }
        if(!isset($params['startat']) || $params['startat'] == '') {
            $params['startat'] = 0;
        }
        /*if(!isset($params['category']) || $params['category'] == '') {
            "category" => "ΣΧΟΛΙΚΕΣ ΚΑΙ ΔΙΟΙΚΗΤΙΚΕΣ ΜΟΝΑΔΕΣ",
        }*/
        return $this->queryMM('unit_workers', $params);
    }

    protected function queryMM($resource, $params = array()) {
        $username = $this->container->getParameter('mmsch_username');
        $password = $this->container->getParameter('mmsch_password');
        $server = 'https://mm.sch.gr/api/'.$resource;

        $curl = curl_init ($server);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD,  $username.":".$password);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( $params ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);


        $data = curl_exec ($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($http_status == 200)
        {
            $decodedData = json_decode($data);
            if(!$decodedData || !isset($decodedData->data)) {
                throw new MMException('MMSCH Error: '.$data);
            }
            return $decodedData->data;
        }
        else
        {
            throw new MMException('MMSCH Error: '.$data);
        }
    }

    public function persistUnit(Unit $unit) {

        if($unit->getMmSyncId() != null) {
            $method = 'PUT';
            $extraParams = array('unit_id' => $unit->getMmSyncId());
        } else {
            $curUnit = $this->findUnitsBy(array('name' => $unit->getName()));
            if(isset($curUnit[0])) { // Check if already exists
                $unit->setMmSyncId($curUnit[0]->mm_id);
                $modifyDateTime = new \DateTime('now');
                $unit->setMmSyncLastUpdateDate($modifyDateTime->add(new \DateInterval('PT2M')));
                $this->container->get('doctrine')->getManager()->persist($unit);
                $this->container->get('doctrine')->getManager()->flush($unit);
                return;
            }
            $method = 'POST';
            $extraParams = array();
        }
        $lastUpdate = $unit->getUpdatedAt();
        $params = array_merge($extraParams, array(
                "mm_id" => $unit->getMmSyncId(),
                "registry_no" => $unit->getRegistryNo(),
                "name" => $unit->getName(),
                "source" => 'SUS',
                "category" => $unit->getCategory()->getName(),
                "suspended" => !$unit->isActive(),
                "state" => $unit->getState()->getName(),
                "education_level" => $unit->getUnitType() != null && $unit->getUnitType()->getEducationLevel() != null ? $unit->getUnitType()->getEducationLevel()->getName() : null,
                "special_name" => $unit->getSpecialName(),
                "region_edu_admin" => $unit->getRegionEduAdmin() != null ? $unit->getRegionEduAdmin()->getName() : null,
                "edu_admin" => $unit->getEduAdmin() != null ? $unit->getEduAdmin()->getName() : null,
                "implementation_entity" => $unit->getImplementationEntity() != null ? $unit->getImplementationEntity()->getImplementationEntityId() : null,
                //"transfer_area" => $unit-$unit->getImplementationEntity()>getTransferArea()->getId(),
                "municipality" => $unit->getMunicipality() != null ? $unit->getMunicipality()->getName() : null,
                "municipality_community" => $unit->getMunicipalityCommunity() != null ? $unit->getMunicipalityCommunity()->getName() : null,
                "prefecture" => $unit->getPrefecture() != null ? $unit->getPrefecture()->getName() : null,
                "unit_type" => $unit->getUnitType() != null ? $unit->getUnitType()->getName() : null,
                //"operation_shift" => $unit->getOperationShift()->getOperationShiftId(),
                "legal_character" => $unit->getLegalCharacter()->getName(),
                //"orientation_type" => $unit->getOrientationType()->getOrientationTypeId(),
                //"special_type" => $unit->getSpecialType()->getSpecialTypeId(),
                "postal_code" => $unit->getPostalCode(),
                //"area_team_number" => $unit->getAreaTeamNumber(),
                "last_update" => $lastUpdate instanceof \DateTime ? $lastUpdate->format('Y-m-d H:i:s') : null,
                "last_sync" => $lastUpdate instanceof \DateTime ? $lastUpdate->format('Y-m-d H:i:s') : null,
		        "email" => $unit->getEmail(),
                "fax_number" => $unit->getFaxNumber(),
                "street_address" => $unit->getStreetAddress(),
                "phone_number" => $unit->getPhoneNumber(),
                "tax_number" => $unit->getTaxNumber(),
                "tax_office" => $unit->getTaxOffice() != null ? $unit->getTaxOffice()->getName() : null,
                "comments" => $unit->getComments(),
                "latitude" => $unit->getLatitude(),
                "longitude" => $unit->getLongitude(),
                "positioning" => $unit->getPositioning(),
                //"fek" => '',
        ));

            //check if unit has allowed unit type to sync with mm 
            if ( !$this->allowedUnitTypesToMMSync( $unit->getUnitType()->getName()))
                return;
   
            $curl = curl_init("https://mm.sch.gr/api/units");
            $username = $this->container->getParameter('mmsch_username');
            $password = $this->container->getParameter('mmsch_password');
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD,  $username.":".$password);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( $params ));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $origData = curl_exec($curl);
            $data = json_decode($origData);
            if($data->status == 200) {
                if($method == 'POST') {
                    $unit->setMmSyncId($data->mm_id);
                }
                $modifyDateTime = new \DateTime('now');
                $unit->setMmSyncLastUpdateDate($modifyDateTime->add(new \DateInterval('PT2M')));
            } else {
                throw new MMException('Error adding unit: '.$origData);
            }
        
    }

    public function persistWorker(Workers $worker) {

        if($worker->getMmSyncId() != null) {
            $method = 'PUT';
            $extraParams = array('worker_id' => $worker->getMmSyncId());
        } else {
            $curWorker = $this->findWorkersBy(array('worker' => $worker->getLastname().' '.$worker->getFirstname(),  'searchtype' => 'EXACT'));
            if(isset($curWorker[0])) { // Check if already exists
                $worker->setMmSyncId($curWorker[0]->worker_id);
                $worker->setMmSyncLastUpdateDate(new \DateTime('now'));
                $this->container->get('doctrine')->getManager()->persist($worker);
                $this->container->get('doctrine')->getManager()->flush($worker);
                foreach($this->getWorkerUnits($worker) as $curUnit) {
                    $this->addUnitWorker($curUnit, $worker);
                }
                return;
            }
            $method = 'POST';
            $extraParams = array();
        }
        $params = array_merge($extraParams, array(
            'worker_id' => $worker->getWorkerId(),
            'registry_no' => $worker->getRegistryNo(),
            'lastname' => $worker->getLastname(),
            'firstname' => $worker->getFirstname(),
            'fathername' => $worker->getFathername(),
            'sex' => $worker->getSex(),
            'source' => 'SUS',
        ));

        $curl = curl_init("https://mm.sch.gr/api/workers");

        $username = $this->container->getParameter('mmsch_username');
        $password = $this->container->getParameter('mmsch_password');
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD,  $username.":".$password);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( $params ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $origData = curl_exec($curl);
        $data = json_decode($origData);
        if($data->status == 200) {
            if($method == 'POST') {
                $worker->setMmSyncId($data->worker_id);
            }
            $worker->setMmSyncLastUpdateDate(new \DateTime('now'));
            foreach($this->getWorkerUnits($worker) as $curUnit) {
                $this->addUnitWorker($curUnit, $worker);
            }
        } else {
            throw new MMException('Error adding unit: '.$origData);
        }
    }

    private function getWorkerUnits(Workers $worker) {
        $units = array();
        if($worker->getUnit() != null) {
            $units[] = $worker->getUnit();
        }
        foreach($worker->getResponsibleUnits() as $curUnit) {
            $units[] = $curUnit;
        }
        return $units;
    }

    private function addUnitWorker(Unit $unit, Workers $worker) {
        if($worker->getMmSyncId() == null || $unit->getMmSyncId() == null) {
            throw new MMException('Worker or unit is not synced: '.$worker->getMmSyncId().' '.$unit->getMmSyncId());
        }
        $extraParams = array();
        $curUnitWorker = $this->queryUnitWorkers(array('worker' => $worker->getLastname().' '.$worker->getFirstname(), 'unit' => $unit->getMmSyncId(), 'searchtype' => 'EXACT'));
        if(isset($curUnitWorker[0])) { // Check if already exists
            $method = 'PUT';
            $extraParams['unit_worker_id'] = $curUnitWorker[0]->unit_worker_id;
        } else {
            $method = 'POST';
        }
        $params = array_merge($extraParams, array(
            'worker' => $worker->getMmSyncId(),
            'mm_id' => $unit->getMmSyncId(),
            'worker_position' => $worker->getUnit() == $unit ? 'ΔΙΕΥΘΥΝΤΗΣ ΚΕΠΛΗΝΕΤ' : 'ΤΕΧΝΙΚΟΣ ΥΠΕΥΘΥΝΟΣ ΚΕΠΛΗΝΕΤ',
        ));

        $curl = curl_init("https://mm.sch.gr/api/unit_workers");

        $username = $this->container->getParameter('mmsch_username');
        $password = $this->container->getParameter('mmsch_password');
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD,  $username.":".$password);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( $params ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $origData = curl_exec($curl);
        $data = json_decode($origData);
        if($data->status == 200) {
            return true;
        } else {
            throw new MMException('Error adding unit: '.$origData);
        }
    }
}
?>
