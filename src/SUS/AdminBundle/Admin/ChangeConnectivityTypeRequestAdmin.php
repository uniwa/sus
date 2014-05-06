<?php
namespace SUS\AdminBundle\Admin;

use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;

class ChangeConnectivityTypeRequestAdmin extends ExistingCircuitRequestAdmin
{
    protected function configureListFields(ListMapper $listMapper)
    {
        parent::configureListFields($listMapper);
        $listMapper
            ->add('newConnectivityType.name', 'trans')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        parent::configureDatagridFilters($datagridMapper);
        $datagridMapper
            ->add('newConnectivityType', null, array(), null, array('query_builder' => $this->getServiceConnectivityTypes()))
        ;
    }

    protected function getServiceConnectivityTypes() {
        $ctRepository = $this->getModelManager()->getEntityManager('SUS\SiteBundle\Entity\Circuits\ConnectivityType')->getRepository('SUS\SiteBundle\Entity\Circuits\ConnectivityType');
        return $ctRepository->getConnectivityTypesQb(array('isService' => true));
    }
}