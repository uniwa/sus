<?php

namespace SUS\SiteBundle\Entity\Requests;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class RemoveCircuitRequest extends ExistingCircuitRequest
{
}