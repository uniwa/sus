<?php

declare(strict_types=1);

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * MySQL/MariaDB YEAR column type (`units.foundation_date` is `year(4)`).
 *
 * DBAL has no portable YEAR type; the old app used `columnDefinition="YEAR"`. A dedicated
 * type lets schema introspection understand the existing `year(4)` column (via
 * `mapping_types: { year: year }`) so `doctrine:schema:update --dump-sql` stays clean.
 * Values are handled as plain strings (e.g. "1985"), as the old app did.
 */
final class YearType extends Type
{
    public const NAME = 'year';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'YEAR';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
