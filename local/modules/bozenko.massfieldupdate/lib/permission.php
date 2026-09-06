<?php

namespace Bozenko\MassFieldUpdate;

use Bitrix\Main\Config\Option;

final class Permission
{
    public const OPTION = 'field_permissions';

    public static function isAdmin(): bool
    {
        global $USER;

        return is_object($USER) && $USER->IsAdmin();
    }

    public static function getMap(): array
    {
        $value = Option::get('bozenko.massfieldupdate', self::OPTION, '{}');
        $map = json_decode($value, true);

        return is_array($map) ? $map : [];
    }

    public static function saveMap(array $map): void
    {
        Option::set('bozenko.massfieldupdate', self::OPTION, json_encode($map, JSON_UNESCAPED_UNICODE));
    }

    public static function canUpdate(string $entity, string $field, int $iblockId = 0): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        global $USER;
        $groups = is_object($USER) ? $USER->GetUserGroupArray() : [];
        $key = $entity === 'product' ? $entity.'.'.$iblockId.'.'.$field : $entity.'.'.$field;
        $allowed = self::getMap()[$key] ?? [];

        foreach ($groups as $groupId) {
            if (in_array((string)$groupId, array_map('strval', (array)$allowed), true)) {
                return true;
            }
        }

        return false;
    }
}
