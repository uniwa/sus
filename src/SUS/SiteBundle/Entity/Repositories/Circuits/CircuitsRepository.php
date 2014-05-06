<?php

namespace SUS\SiteBundle\Entity\Repositories\Circuits;

use SUS\SiteBundle\Entity\Repositories\BaseRepository;

use Doctrine\ORM\QueryBuilder;

class CircuitsRepository extends BaseRepository
{
    public function findCircuits($filters = array()) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('c');
        $qb->from($this->_entityName, 'c');

        $this->addFilters($qb, $filters);

        return $qb->getQuery()->getResult();
    }

    protected function addFilters(QueryBuilder &$qb, array $filters) {
    }
}