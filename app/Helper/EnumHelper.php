<?php

namespace App\Helper;

class EnumHelper
{
    public static function getValuesAsArray (string $entity): array
    {
        return array_column($entity::cases(), 'value');
    }
}
