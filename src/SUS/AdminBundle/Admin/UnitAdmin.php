<?php
namespace SUS\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use SUS\UserBundle\Loader\YamlUserLoader;

class UnitAdmin extends Admin
{
    protected $datagridValues = array(
        '_sort_order' => 'ASC', // Descendant ordering (default = 'ASC')
        '_sort_by' => 'unitId' // name of the ordered field (default = the model id
    );
    
    private $securityContext = null;
    protected $kernel,$container;
    
    public function setKernel($kernel) {
        $this->kernel = $kernel;
    }
    
    public function setContainer($container) {
        $this->container = $container;
    }

    public function setSecurityContext($securityContext) {
        $this->securityContext = $securityContext;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->remove('acl')
            ->remove('delete') // Deletes are disabled because we don't know how to handle it in MM
            ->remove('remove')
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $user = $this->securityContext->getToken()->getUser();

        // Here we set the fields of the ShowMapper variable, $showMapper (but this can be called anything)
        $showMapper

            /*
             * The default option is to just display the value as text (for boolean this will be 1 or 0)
             */

            ->with('Γενικά Στοιχεία')
            ->add('name', null, array('label' => 'Ονομασία'))
            ->add('mmSyncId',  array('label' => 'Κωδικός ΜΜ'))
            ->add('registryNo', null, array('label' => 'Κωδικός ΥΠΑΙΠΘ'))
            ->add('specialName', null, array('label' => 'Ειδική Ονομασία'))
            ->add('unitType', null, array('label' => 'Τύπος'))
            ->add('foundationDate', null, array('label' => 'Έτος Ίδρυσης'))
            ->add('state', null, array( 'label' => 'Κατάσταση'))
            ->add('legalCharacter', null, array('label' => 'Νομικός Χαρακτήρας'))
            ->add('regionEduAdmin', null, array('label' => 'Περιφέρειακή Διεύθυνση'))
            ->add('category', null, array('label' => 'Κατηγορία'))
            //->add('implementationEntity', null, array('label' => 'Φορέας Υλοποίησης'))
            //->add('manager', null, array('label' => 'Υπεύθυνος'))
            //->add('responsibles', null, array('label' => 'Τεχνικοί Υπεύθυνοι'))
            ->add('comments', null, array('label' => 'Σχόλια'))
            ->end()

            ->with('Στοιχεία Τοποθεσίας')
            ->add('streetAddress', null, array('label' => 'Οδός, Αριθμός'))
            ->add('postalCode', 'text', array('label' => 'Ταχυδρομικός Κώδικας'))
            ->add('municipality', null, array('label' => 'Δήμος ΟΤΑ'))
            ->add('municipalityCommunity', null, array('label' => 'Δημοτική Ενότητα'))
            ->add('prefecture', null, array('label' => 'Νομός'))
            ->add('positioning', null, array('label' => 'Κτηριακή Θέση'))
            ->add('eduAdmin', null, array('label' => 'Διεύθυνση Εκπαίδευσης'))
            ->end()

            ->with('Στοιχεία Επικοινωνίας')
            ->add('manager.firstName', null, array('label' => 'Όνομα Υπευθύνου'))
            ->add('manager.lastName', null, array('label' => 'Επώνυμο Υπευθύνου'))
            ->add('faxNumber', null, array('label' => 'Αριθμός FAX'))
            ->add('phoneNumber', null, array('label' => 'Τηλέφωνο Επικοινωνίας'))
            ->add('email', null, array('label' => 'E-mail'))
            ->add('website', null, array('label' => 'Website'))
            ->add('mapUrl', 'url', array('label' => 'Χάρτης'))
            ->end()
        ;

        if ($user->hasRole('ROLE_USER4')) {
            $showMapper
                ->with('Φορολογικά Στοιχεία')
                ->add('taxNumber', null, array('label' => 'Αριθμός Φορολογικού Μητρώου (ΑΦΜ)'))
                ->add('taxOffice', null, array('label' => 'Δ.Ο.Υ.'))
                ->end();
        }
    }

    /**
     * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
     *
     * @return void
     */
    protected function configureFormFields(FormMapper $formMapper)
    {

//         //SOLUTION to restricts unitType  
//        //check user uid with roles.yml file
//        //check for USER1,USER2,USER4
//        $user = $this->securityContext->getToken()->getUser();
//        $username = $user->getUsername();
//        $path = $this->kernel->locateResource('@SUSUserBundle').'/Resources/config/roles.yml';
//        $userRoles = new YamlUserLoader();   
//        $roles = $userRoles->load($path);
//         if (array_key_exists($username,$roles)){
//            $unitTypeName = $roles[$username]['unit_types'];
//         }else{
//             $unitTypeName = null ;
//         }
//         
//        $em = $this->modelManager->getEntityManager('SUS\SiteBundle\Entity\UnitTypes');
//        $qb = $em->createQueryBuilder();
//        $qb = $qb->add('select', 'ut')
//                 ->add('from', 'SUS\SiteBundle\Entity\UnitTypes ut')
//                 ->where('ut.name IN (:unitTypeName)')
//                 ->setParameter('unitTypeName', $unitTypeName);
//
//        $query = $qb->getQuery();
//        $arrayTypes = $query->getArrayResult();
//        //$arrayTypeTest = new \Doctrine\Common\Collections\ArrayCollection($arrayTypes);
// 
//        foreach ($arrayTypes as $art) {
//            $arrayType[] = array( $art['name'] );
//        }
        $user = $this->securityContext->getToken()->getUser();

        $formMapper
            ->with('Γενικά Στοιχεία')
                ->add('name', null, array('required' => true, 'label' => 'Ονομασία'))
                ->add('registryNo', null, array('label' => 'Κωδικός ΥΠΑΙΠΘ'))
                ->add('specialName', null, array('label' => ' Ειδική Ονομασία'))
                //->add('unitType', 'choice', array('choices'=>$arrayType, 'required' => true,'label' => 'Τύπος'))
                ->add('unitType', null, array('empty_value'=> '-', 'required' => true,'label' => 'Τύπος'))
                //->add('foundationDate', 'genemu_jquerydate', array('required' => false, 'widget' => 'single_text'))
                ->add('foundationDate', null, array('label' => 'Έτος Ίδρυσης'))
                ->add('state', null, array('empty_value'=> '-', 'required' => true, 'label' => 'Κατάσταση'))
                ->add('legalCharacter', null, array('empty_value'=> '-', 'required' => true, 'label' => 'Νομικός Χαρακτήρας'))
               // ->add('category', null, array('empty_value'=> '-', 'required' => true,'label' => 'Κατηγορία'))
               // ->add('implementationEntity', null, array('empty_value'=> '-', 'required' => true, 'label' => 'Φορέας Υλοποίησης'))
                ->add('manager.firstName', 'text', array('label' => 'Όνομα Υπευθύνου', 'required' => false))
                ->add('manager.lastName', 'text', array('label' => 'Επώνυμο Υπευθύνου', 'required' => false))
                //->add('responsibles', null, array('label' => 'Τεχνικοί Υπεύθυνοι', 'required' => false))
                ->add('comments', null, array('label' => 'Σχόλια'))
                //->add('source', 'choice', array('label' => 'Πρωτογενής Πηγή','choices' => array('aegeanDB' => 'AegeanDB', 'mySchool' => 'MySchool', 'minEdu' => 'MinEdu'),'preferred_choices' => array('aegeanDB')))
            ->end()

            ->with('Στοιχεία Τοποθεσίας')
                ->add('streetAddress', null, array('label' => 'Οδός, Αριθμός'))
                ->add('postalCode', null, array('label' => 'Ταχυδρομικός Κώδικας'))
                ->add('municipality', null, array('label' => 'Δήμος ΟΤΑ'))
                ->add('municipalityCommunity', null, array('label' => 'Δημοτική Ενότητα'))
                ->add('prefecture', null, array('label' => 'Νομός'))
                ->add('positioning', null, array('label' => 'Κτηριακή Θέση'))
                ->add('eduAdmin', null, array('label' => 'Διεύθυνση Εκπαίδευσης'))
                ->add('regionEduAdmin', null, array('label' => 'Περιφέρεια'))
                ->add('latlng', 'oh_google_maps', array(
                    'label' => 'Αναζήτηση Συντεταγμένων',
                    'required' => false,
                    'include_jquery' => false,
                    'lat_options' => array(
                        'label' => 'Latitude',
                        'required' => false,
                        'empty_data' => 0
                    ),
                    'lng_options' => array(
                        'label' => 'Longitude',
                        'required' => false,
                        'empty_data' => 0
                    ),
                    'default_lat' => 37.984042,
                    'default_lng' => 23.728179,
                ))
            ->end()

            ->with('Στοιχεία Επικοινωνίας')
                ->add('faxNumber', null, array('label' => 'Αριθμός FAX'))
                ->add('phoneNumber', null, array('label' => 'Τηλέφωνο Επικοινωνίας'))
                ->add('email', null, array('label' => 'E-mail'))
                ->add('website', null, array('label' => 'Website'))
            ->end()
            ;

        if ($user->hasRole('ROLE_USER4')) {
            $formMapper
                ->with('Φορολογικά Στοιχεία')
                ->add('taxNumber', null, array('label' => 'Αριθμός Φορολογικού Μητρώου (ΑΦΜ)'))
                ->add('taxOffice', null, array('label' => 'Δ.Ο.Υ.'))
                ->end();
        }
    }
    
    /**
     * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
     *
     * @return void
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
                
            ->add('_action', 'actions', array( 'actions' => array(  'show' => array(),
                                                                    'edit' => array(),
                                                                 )))
            ->add('unitId', 'string')
            ->add('mmSyncId', 'string')
            ->addIdentifier('name', 'string', array('label' => 'Ονομασία'))
            //->add('name', 'string', array('label' => 'Ονομασία'))
            ->add('state.name', 'string', array('label' => 'Κατάσταση'))
            ->add('manager', 'string', array('label' => 'Υπεύθυνος'))
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
     *
     * @return void
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $user = $this->securityContext->getToken()->getUser();
        if ($user->hasRole('ROLE_USER4')) {
            $datagridMapper
                ->add('unitId', null, array())
                ->add('mmSyncId', null, array())
                ->add('name', null, array())
                ->add('category', null, array())
                ->add('unitType', null, array())
                ->add('manager', null, array('label' => 'Υπεύθυνος'))
                ->add(
                    'full_text',
                    'doctrine_orm_callback',
                    array('callback' => array($this, 'getFullTextFilter'), 'field_type' => 'text')
                );
        }
    }
    
    public function getFullTextFilter($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return;
        }

        // Use `andWhere` instead of `where` to prevent overriding existing `where` conditions
        $queryBuilder->andWhere($queryBuilder->expr()->orX(
            $queryBuilder->expr()->like($alias.'.name', $queryBuilder->expr()->literal('%' . $value['value'] . '%')),
            $queryBuilder->expr()->like($alias.'.mmSyncId', $queryBuilder->expr()->literal('%' . $value['value'] . '%'))
        ));

        return true;
    }
    
    /*public function getExportFields()
    {
        return array(
            'id',
            'name',
            'categoryName',
            'fy',
            'activatedAt',
        );
    }*/

    public function getBatchActions()
    {
        return array();
    }
    
    public function preUpdate($object)
    {
        $this->prePersist($object);
    }

    public function prePersist($object)
    {
        
        if ($object->getUnitType() != null){
            $category = $object->getUnitType()->getCategoryId();           
            $fCategory = $this->container->get('doctrine')->getManager()->getRepository('SUS\SiteBundle\Entity\UnitCategory')->find($category);            
            $object->setCategory($fCategory);
        }

    }
    
    
    public function createQuery($context = 'list')
    {
       
        //get username(uid)
        $user = $this->securityContext->getToken()->getUser();
        $username = $user->getUsername();
        //$username = 'ieknafpl';
        
        //check user uid with roles.yml file
        //check for USER1,USER2,USER4
        $path = $this->kernel->locateResource('@SUSUserBundle').'/Resources/config/roles.yml';
        $userRoles = new YamlUserLoader();   
        $roles = $userRoles->load($path);
        
        //return units based on user permissions of Unit Type and Legal Character
        if (array_key_exists($username,$roles)){
            $legalCharacterName = $roles[$username]['legal_character'];
            $unitTypeName = $roles[$username]['unit_types'];
            if ($roles[$username]['name'] === 'Administrator' && empty($unitTypeName)) {
                $unitTypeName[0] = 'all';
            }
            $proxyQuery = parent::createQuery($context);
            $proxyQuery->join($proxyQuery->getRootAlias().'.unitType', 'ut');
            $proxyQuery->join($proxyQuery->getRootAlias().'.legalCharacter', 'lc');
            if ($unitTypeName[0] != 'all' ) $proxyQuery->andWhere('ut.name IN (:unitTypeName)');
            $proxyQuery->andWhere('lc.name IN (:legalCharacterName)');
            if ($unitTypeName[0] != 'all' ) $proxyQuery->setParameter('unitTypeName', $unitTypeName);
            $proxyQuery->setParameter('legalCharacterName', $legalCharacterName);
        } else {
            //check for USER3
            $mm_id = $this->container->get('security.user.permissions')->checkPrincipal($username);
            //$mm_id = '1019474';
            
                //return unit based on mm_id
                if ($mm_id != null){
                    $proxyQuery = parent::createQuery($context);        
                    $proxyQuery->Where($proxyQuery->getRootAlias().'.mmSyncId = :mm_id');
                    $proxyQuery->setParameter('mm_id', $mm_id);
                } else {
                    throw new \Exception("Δεν βρέθηκε η μονάδα με βάση το uid του χρήστη");
                }        
        }
       
        return $proxyQuery;
    }
    
}
