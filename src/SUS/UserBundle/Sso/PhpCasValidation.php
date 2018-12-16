<?php

namespace SUS\UserBundle\Sso;

use BeSimple\SsoAuthBundle\Sso\AbstractValidation;
use BeSimple\SsoAuthBundle\Sso\ValidationInterface;
use Buzz\Message\Response;

/**
 * @author: Jean-François Simon <contact@jfsimon.fr>
 */
class PhpCasValidation extends AbstractValidation implements ValidationInterface
{
    /**
     * {@inheritdoc}
     */
    protected function validateResponse(Response $response)
    {
        $allowedUsernames = array(
            'sprekas',
            'krantzos',
            'sgialpa',
            'elenipapapa',
            'dandrits',
            'vagelopoulos',
            'apekos',
            'ktsiolis',
            'ubichrys',
            'dnikoudis',
            'tpanou'
        );
        \phpCAS::client(SAML_VERSION_1_1,"sso.sch.gr",443,'',false);
        \phpCAS::setNoCasServerValidation();
        \phpCAS::handleLogoutRequests(array("sso.sch.gr"));
        \phpCAS::setNoClearTicketsFromUrl();
        $success = true;
        if(!\phpCAS::checkAuthentication()) {
            $success = false;
        }

        if(!in_array(\phpCAS::getUser(), $allowedUsernames)) {
            $success = false;
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
