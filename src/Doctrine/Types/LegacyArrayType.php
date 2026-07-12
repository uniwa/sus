<?php

declare(strict_types=1);

namespace App\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Re-implementation of the PHP-serialized "array" DBAL type that was removed in doctrine/dbal 4.
 *
 * The legacy `Users.roles` column contains PHP-serialized arrays written by FOSUserBundle
 * (e.g. `a:1:{i:0;s:16:"ROLE_SUPER_ADMIN";}`) and carries the historical `(DC2Type:array)`
 * column comment. The database is read-only, so the data cannot be migrated to JSON — this
 * type reproduces the exact serialize()/unserialize() semantics of DBAL 2/3.
 *
 * NOTE: dbal 4 no longer auto-appends `(DC2Type:...)` comments; the entity mapping must
 * declare `options: ['comment' => '(DC2Type:array)']` explicitly to match the real schema.
 */
final class LegacyArrayType extends Type
{
    public const NAME = 'array';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return serialize($value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        if ($value === '') {
            return [];
        }

        $result = @unserialize($value, ['allowed_classes' => false]);
        if ($result === false && $value !== serialize(false)) {
            throw ValueNotConvertible::new($value, self::NAME, 'could not unserialize value');
        }

        return $result;
    }
}
