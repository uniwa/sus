<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

/**
 * Reconciles the Doctrine-computed schema with legacy quirks of the real (read-only) database
 * so `doctrine:schema:update --dump-sql` stays empty. NEVER lets any DDL be applied — the DB
 * is read-only by decree (WORKPLAN.md).
 *
 * Quirks handled (docs/port-inventory/entities.md §2.15/§2.16, drift checklist §5):
 *
 *  1. `unit_types.category_id` is mapped as a ManyToOne to UnitCategory (behavior parity with
 *     the old app), but the real table has NO foreign key and NO index on that column.
 *     Doctrine's SchemaTool would therefore always propose `ALTER TABLE unit_types ADD
 *     CONSTRAINT ...` plus an index. This listener removes both from the generated schema.
 *
 *  2. `worker_specializations` really has BOTH the unique key `name_UNIQUE` and a redundant
 *     plain index `name_idx` on `name`. SchemaTool silently drops any index that is "fulfilled
 *     by" a unique constraint on the same columns, so the declared `#[ORM\Index('name_idx')]`
 *     never reaches the computed schema and --dump-sql proposes `DROP INDEX name_idx`.
 *     This listener puts it back.
 */
#[AsDoctrineListener(event: ToolEvents::postGenerateSchema)]
final class LegacySchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if ($schema->hasTable('unit_types')) {
            $table = $schema->getTable('unit_types');

            foreach ($table->getForeignKeys() as $foreignKey) {
                if (array_map('strtolower', $foreignKey->getLocalColumns()) === ['category_id']) {
                    $table->removeForeignKey($foreignKey->getName());
                }
            }

            foreach ($table->getIndexes() as $index) {
                if ($index->isPrimary()) {
                    continue;
                }
                if (array_map('strtolower', $index->getColumns()) === ['category_id']) {
                    $table->dropIndex($index->getName());
                }
            }
        }

        if ($schema->hasTable('worker_specializations')) {
            $table = $schema->getTable('worker_specializations');

            if (!$table->hasIndex('name_idx')) {
                $table->addIndex(['name'], 'name_idx');
            }
        }
    }
}
