<?php
namespace SUS\AdminBundle\Admin\Helpdesk;

use SUS\AdminBundle\Admin\ActivateServiceRequestAdmin as BaseActivateServiceRequestAdmin;

class ActivateServiceRequestAdmin extends BaseActivateServiceRequestAdmin
{
    protected $baseRouteName = 'admin_lms_activateservicerequest_user';
    protected $baseRoutePattern = 'activateservicerequest_user';
}