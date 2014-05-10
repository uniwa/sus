<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WorkerPositions
 *
 * @ORM\Table(name="worker_positions")
 * @ORM\Entity
 */
class WorkerPositions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="worker_position_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $workerPositionId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;


}
