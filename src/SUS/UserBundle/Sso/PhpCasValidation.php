<?php

namespace SUS\UserBundle\Sso;

use BeSimple\SsoAuthBundle\Sso\AbstractValidation;
use BeSimple\SsoAuthBundle\Sso\ValidationInterface;
use Buzz\Message\Response;
//use SUS\UserBundle\Loader\YamlUserLoader;


/**
 * @author: Jean-François Simon <contact@jfsimon.fr>
 */
class PhpCasValidation extends AbstractValidation implements ValidationInterface
{
    protected $kernel;
    private $container;

    public function setKernel($kernel) {
        $this->kernel = $kernel;
    }

    public function setContainer($container) {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */    
    protected function validateResponse(Response $response)
    {
        //        $allowedUsernames = array(
        //            'krantzos',
        //            'sgialpa',
        //            'elenipapapa',
        //            'vagelopoulos',
        //            'apekos',
        //            'ktsiolis',
        //            'dnikoudis',
        //        );

        //        if(!in_array(\phpCAS::getUser(), $allowedUsernames)) {
        //            $success = false;
        //        }
        
        \phpCAS::client(SAML_VERSION_1_1,"sso.sch.gr",443,'',false);
        \phpCAS::setNoCasServerValidation();
        \phpCAS::handleLogoutRequests(array("sso.sch.gr"));
        \phpCAS::setNoClearTicketsFromUrl();

        $success = true;
        if(!\phpCAS::checkAuthentication()) {
            $success = false; 
        }

        $username = \phpCAS::getUser();

        //check user uid with roles.yml file
        //check for USER1,USER2,USER4
        $path = $this->kernel->locateResource('@SUSUserBundle').'/Resources/config/roles.yml';
        $userRoles = new YamlUserLoader();
        $roles = $userRoles->load($path);
        if (array_key_exists($username,$roles)){
            $success = true;
        }

//        //check for USER3
        if (array_key_exists($username, $roles)!=true){
            $mm_id = $this->container->get('security.user.permissions')->checkPrincipal($username);
            if ($mm_id != null) $success = true;
        }

        if ($success) {
            $this->username = \phpCAS::getUser();
            $this->attributes = \phpCAS::getAttributes();
        } else {
            $this->error = error_get_last();
        }
            
        return $success;
    }
}
