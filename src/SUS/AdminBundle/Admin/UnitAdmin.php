<?php
namespace SUS\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class UnitAdmin extends Admin
{
    protected $datagridValues = array(
        '_sort_order' => 'ASC', // Descendant ordering (default = 'ASC')
        '_sort_by' => 'unitId' // name of the ordered field (default = the model id
    );

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->remove('acl')
            ->remove('delete') // Deletes are disabled because we don't know how to handle it in MM
            ->remove('remove')
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
     *
     * @return void
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'string')
            ->add('unit.mmId', 'string')
            ->add('unit.name')
            ->add('unit.categoryName')
            ->add('unit.fy')
            ->add('unit.state')
            ->add('activatedAt', 'date')
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
     *
     * @return void
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->with('Γενικά Στοιχεία')
                ->add('name', null, array('required' => true, 'label' => 'Ονομασία'))
                ->add('registryNo', null, array('label' => 'Κωδικός ΥΠΑΙΠΘ'))
                ->add('specialName', null, array('label' => ' Ειδική Ονομασία'))
                ->add('unitType', null, array('empty_value'=> '-', 'required' => true,'label' => 'Τύπος'))
                //->add('foundationDate', 'genemu_jquerydate', array('required' => false, 'widget' => 'single_text'))
                ->add('foundationDate', null, array('label' => 'Έτος Ίδρυσης'))
                ->add('state', null, array('empty_value'=> '-', 'required' => true, 'label' => 'Κατάσταση'))
                ->add('legalCharacter', null, array('empty_value'=> '-', 'required' => true, 'label' => 'Νομικός Χαρακτήρας'))
                ->add('category', null, array('empty_value'=> '-', 'required' => true,'label' => 'Κατηγορία'))
                ->add('implementationEntity', null, array('empty_value'=> '-', 'required' => true, 'label' => 'Φορέας Υλοποίησης'))
                ->add('manager', null, array('label' => 'Υπεύθυνος', 'required' => false))
                ->add('responsibles', null, array('label' => 'Τεχνικοί Υπεύθυνοι', 'required' => false))
                ->add('comments', null, array('label' => 'Σχόλια'))
            ->end()

            ->with('Στοιχεία Τοποθεσίας')
                ->add('streetAddress', null, array('label' => 'Οδός, Αριθμός'))
                ->add('postalCode', null, array('label' => 'Ταχυδρομικός Κώδικας'))
                ->add('municipality', null, array('label' => 'Δήμος ΟΤΑ'))
               // ->add('municipalityCommunity', null, array('label' => 'Δημοτική Ενότητα'))
		->add('prefecture', null, array('label' => 'Νομός'))
                ->add('positioning', null, array('label' => 'Κτηριακή Θέση'))
                ->add('eduAdmin', null, array('label' => 'Διεύθυνση Εκπαίδευσης'))
                ->add('regionEduAdmin', null, array('label' => 'Περιφέρεια'))
                ->add('latlng', 'oh_google_maps', array('label' => 'Αναζήτηση Συντεταγμένων', 'required' => false, 'include_jquery' => false,))
            ->end()

            ->with('Στοιχεία Επικοινωνίας')
                ->add('faxNumber', null, array('label' => 'Αριθμός FAX'))
                ->add('phoneNumber', null, array('label' => 'Τηλέφωνο Επικοινωνίας'))
                ->add('email', null, array('label' => 'E-mail'))
                ->add('website', null, array('label' => 'Website'))
            ->end()

            ->with('Φορολογικά Στοιχεία')
                ->add('taxNumber', null, array('label' => 'Αριθμός Φορολογικού Μητρώου (ΑΦΜ)'))
                ->add('taxOffice', null, array('label' => 'Δ.Ο.Υ.'))
            ->end()
        ;
    }

    /**
     * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
     *
     * @return void
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('_action', 'actions', array(
                'actions' => array(
                    //'show' => array(),
                    'edit' => array(),
            )))
            ->add('unitId', 'string')
            ->add('mmSyncId', 'string')
            ->add('name', 'string', array('label' => 'Ονομασία'))
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
        $datagridMapper
            ->add('unitId', null, array())
            ->add('mmSyncId',null,array())
            ->add('name', null, array())
            ->add('category', null, array())
            ->add('unitType', null, array())
            ->add('manager', null, array('label' => 'Υπεύθυνος'))
        ;
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
}
