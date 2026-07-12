<?php
namespace SUS\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\View\View;
use SUS\SiteBundle\Entity\Unit;

class UnitApiController extends Controller
{
	/**
 	* @Route("/units_extra/{mmid}", name="get_unit_extra")
 	* @Method("GET")
 	*/
	public function getUnitExtraAction($mmid)
	{
    	   try {
        	// Ensure $id is numeric
        	if (!is_numeric($mmid)) {
            		$view = View::create(['error' => 'Invalid unit ID'], 400);
            		$view->setFormat('json');
            		return $this->get('fos_rest.view_handler')->handle($view);
        	}

        	// Fetch unit
//        	$unit = $this->getDoctrine()->getRepository(Unit::class)->find($id);

        // Search by mmId instead of unitId
        $unit = $this->getDoctrine()->getRepository(Unit::class)->findOneBy(['registryNo' => $mmid]);

        	if (!$unit) {
            		$view = View::create(['error' => 'Unit not found'], 404);
            		$view->setFormat('json');
            		return $this->get('fos_rest.view_handler')->handle($view);
        	}

//var_dump($unit->getMmId());
//exit;
          	 // Return unit data
        	$data = [
            		'id'   => $unit->getUnitId(),
                        'mmid' => $unit->getMmId(),
            		'name' => $unit->getName(),
            		'registryno' => $unit->getRegistryNo() ? : '',
            		'website' => $unit->getWebsite()
        	];

        	$view = View::create($data, 200);
        	$view->setFormat('json');
        	return $this->get('fos_rest.view_handler')->handle($view);

    	  } catch (\Exception $e) {
        	// Catch any unexpected errors
        	$view = View::create([
            		'error' => 'Internal server error',
            		'message' => $e->getMessage()
       	 	], 500);
        	$view->setFormat('json');
        	return $this->get('fos_rest.view_handler')->handle($view);
          }
}

}
