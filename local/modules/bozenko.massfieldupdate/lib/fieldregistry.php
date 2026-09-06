<?php

namespace Bozenko\MassFieldUpdate;

final class FieldRegistry
{
    private const CRM_CLASSES = [
        'lead' => 'CCrmLead',
        'deal' => 'CCrmDeal',
        'contact' => 'CCrmContact',
        'company' => 'CCrmCompany',
    ];

    public static function getEntities(): array
    {
        return [
            'lead' => 'Лиды',
            'deal' => 'Сделки',
            'contact' => 'Контакты',
            'company' => 'Компании',
            'product' => 'Товары',
        ];
    }

    public static function getFields(string $entity, int $iblockId = 0): array
    {
        if (isset(self::CRM_CLASSES[$entity])) {
            return self::crmFields(self::CRM_CLASSES[$entity]);
        }

        if ($entity === 'product' && $iblockId > 0) {
            return self::productFields($iblockId);
        }

        return [];
    }

    public static function isAllowed(string $field): bool
    {
        return !preg_match('/^(ID|ENTITY_ID|OWNER_ID|PRODUCT_ID|IBLOCK_ID)$/i', $field);
    }

    private static function crmFields(string $class): array
    {
        if (!class_exists($class) || !method_exists($class, 'GetFields')) {
            return [];
        }

        $titles = [
            'TITLE' => 'Название',
            'NAME' => 'Имя',
            'LAST_NAME' => 'Фамилия',
            'SECOND_NAME' => 'Отчество',
            'BIRTHDATE' => 'Дата рождения',
            'POST' => 'Должность',
            'COMPANY_TITLE' => 'Название компании',
            'SOURCE_ID' => 'Источник',
            'SOURCE_DESCRIPTION' => 'Описание источника',
            'STATUS_ID' => 'Статус',
            'STATUS_DESCRIPTION' => 'Описание статуса',
            'COMMENTS' => 'Комментарий',
            'OPPORTUNITY' => 'Сумма',
            'CURRENCY_ID' => 'Валюта',
            'ASSIGNED_BY_ID' => 'Ответственный',
            'CREATED_BY_ID' => 'Кем создано',
            'MODIFY_BY_ID' => 'Кем изменено',
            'DATE_CREATE' => 'Дата создания',
            'DATE_MODIFY' => 'Дата изменения',
            'PHONE' => 'Телефон',
            'EMAIL' => 'Электронная почта',
            'WEB' => 'Сайт',
            'IM' => 'Мессенджер',
            'OPENED' => 'Доступно для всех',
            'CLOSED' => 'Закрыто',
            'TYPE_ID' => 'Тип',
            'CATEGORY_ID' => 'Направление',
            'STAGE_ID' => 'Стадия',
            'BEGINDATE' => 'Дата начала',
            'CLOSEDATE' => 'Дата завершения',
            'PROBABILITY' => 'Вероятность',
            'TAX_VALUE' => 'Налог',
            'LOCATION_ID' => 'Местоположение',
            'UF_CRM_TASK' => 'Задача',
        ];
        $result = [];
        foreach ((array)$class::GetFields() as $name => $info) {
            if (!self::isAllowed((string)$name)) {
                continue;
            }

            $result[(string)$name] = [
                'name' => (string)$name,
                'title' => $titles[$name] ?? (string)($info['TITLE'] ?? $name),
                'type' => strtolower((string)($info['TYPE'] ?? 'string')),
            ];
        }

        return $result;
    }

    private static function productFields(int $iblockId): array
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return [];
        }

        $fields = [
            'NAME' => ['name' => 'NAME', 'title' => 'Название', 'type' => 'string'],
            'CODE' => ['name' => 'CODE', 'title' => 'Символьный код', 'type' => 'string'],
            'SORT' => ['name' => 'SORT', 'title' => 'Сортировка', 'type' => 'number'],
            'PREVIEW_TEXT' => ['name' => 'PREVIEW_TEXT', 'title' => 'Описание для списка', 'type' => 'text'],
            'DETAIL_TEXT' => ['name' => 'DETAIL_TEXT', 'title' => 'Детальное описание', 'type' => 'text'],
            'ACTIVE' => ['name' => 'ACTIVE', 'title' => 'Активность', 'type' => 'list'],
        ];

        $properties = \CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $iblockId]);
        while ($property = $properties->Fetch()) {
            $code = (string)($property['CODE'] ?: $property['ID']);
            $key = 'PROPERTY_'.$code;
            $fields[$key] = [
                'name' => $key,
                'title' => 'Свойство: '.(string)$property['NAME'],
                'type' => strtolower((string)$property['PROPERTY_TYPE']),
                'property_code' => $code,
                'multiple' => $property['MULTIPLE'] === 'Y',
            ];
        }

        return $fields;
    }
}
