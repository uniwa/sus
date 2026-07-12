<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\MMSyncableEntity;
use App\Entity\Unit;
use App\Service\MMService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Pushes every saved/removed Unit/Worker to the MM API during the Doctrine flush.
 * Port of old `SUS\SiteBundle\Extension\MMSyncableListener` (`sus.mmsyncable.listener`).
 *
 * The old listener gated on `kernel.environment == 'prod'`; the port replaces that with the
 * explicit MMSCH_SYNC_ENABLED env flag (true only in prod) — safer and testable, per
 * docs/port-inventory/services.md §10.
 *
 * Behavior preserved: a failed MM HTTP call throws MMException mid-flush and aborts the save.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::preRemove)]
class MMSyncableListener
{
    public function __construct(
        private readonly MMService $mmService,
        #[Autowire(env: 'bool:MMSCH_SYNC_ENABLED')]
        private readonly bool $syncEnabled,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->syncEnabled) {
            return;
        }

        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        foreach ($entities as $entity) {
            if (!$entity instanceof MMSyncableEntity) {
                continue;
            }

            $this->mmService->persistMM($entity);
            $em->persist($entity);
            // Recompute so the mmSyncId/mmSyncLastUpdateDate changes written by persistMM()
            // land in this same flush.
            $meta = $em->getClassMetadata($entity::class);
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        }
    }

    /**
     * Push the entity as soft-deleted so MM sees it as suspended, then restore deletedAt.
     *
     * NOTE: in the old app this method could never run — its `EventArgs` type-hint resolved to
     * a non-existent class (the `use` statement was commented out), so any prod entity remove
     * would have fataled. Ported with correct typing; flagged in the inventory (§10/§12.5) for
     * behavioral confirmation. The deletedAt swap only applies to Unit — the real `workers`
     * table has no deletedAt column, so Worker is pushed as-is.
     */
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof MMSyncableEntity) {
            return;
        }
        if (!$this->syncEnabled) {
            return;
        }

        if ($entity instanceof Unit) {
            $oldDeletedAt = $entity->getDeletedAt();
            $entity->setDeletedAt(new \DateTime('now'));
            $this->mmService->persistMM($entity);
            $entity->setDeletedAt($oldDeletedAt);
        } else {
            $this->mmService->persistMM($entity);
        }
    }
}
