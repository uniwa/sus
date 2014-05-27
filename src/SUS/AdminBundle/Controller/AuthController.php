<?php

namespace SUS\AdminBundle\Controller;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AuthController extends Controller {
    public function debugAction() {
        $request = $this->getRequest();
        $session = $request->getSession();

        if($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        }
        else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        var_dump($error); die();

        //return $this->render('Lille3TestBundle:Default:security.html.twig',array('error'=>$error));
    }
}