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
        $entities = [];
        if (\Bitrix\Main\Loader::includeModule('crm')) {
            foreach ([
                'lead' => ['Лиды', 'CCrmLead'],
                'deal' => ['Сделки', 'CCrmDeal'],
                'contact' => ['Контакты', 'CCrmContact'],
                'company' => ['Компании', 'CCrmCompany'],
            ] as $entity => [$title, $class]) {
                if (class_exists($class) && method_exists($class, 'GetFields')) {
                    $entities[$entity] = $title;
                }
            }
        }

        if (\Bitrix\Main\Loader::includeModule('iblock')
            && \Bitrix\Main\Loader::includeModule('catalog')
            && class_exists('Bitrix\\Catalog\\ProductTable')
        ) {
            $entities['product'] = 'Товары';
        }

        return $entities;
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
        return !preg_match('/^(ID|ENTITY_ID|OWNER_ID|PRODUCT_ID|IBLOCK_ID|IBLOCK_ELEMENT_ID|AVAILABLE|TIMESTAMP_X|QUANTITY_RESERVED|BUNDLE)$/i', $field);
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
        $relations = [
            'ASSIGNED_BY_ID' => 'user',
            'CREATED_BY_ID' => 'user',
            'MODIFY_BY_ID' => 'user',
            'MOVED_BY_ID' => 'user',
            'COMPANY_ID' => 'company',
            'CONTACT_ID' => 'contact',
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
                'relation' => $relations[$name] ?? '',
            ];
        }

        return $result;
    }

    private static function productFields(int $iblockId): array
    {
        if ($iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('catalog')) {
            return [];
        }

        $titles = [
            'QUANTITY' => 'Количество',
            'WEIGHT' => 'Вес',
            'VAT_ID' => 'Ставка НДС',
            'VAT_INCLUDED' => 'НДС включён в цену',
            'QUANTITY_TRACE' => 'Уменьшать количество при покупке',
            'CAN_BUY_ZERO' => 'Разрешить покупку при отсутствии',
            'NEGATIVE_AMOUNT_TRACE' => 'Разрешить отрицательный остаток',
            'SUBSCRIBE' => 'Разрешить подписку при отсутствии',
            'PURCHASING_PRICE' => 'Закупочная цена',
            'PURCHASING_CURRENCY' => 'Валюта закупочной цены',
            'MEASURE' => 'Единица измерения',
            'TYPE' => 'Тип товара',
            'BARCODE_MULTI' => 'Несколько штрихкодов',
        ];

        $fields = [];
        foreach (\Bitrix\Catalog\ProductTable::getEntity()->getFields() as $name => $field) {
            if (!($field instanceof \Bitrix\Main\ORM\Fields\ScalarField)) {
                continue;
            }
            if (!self::isAllowed((string)$name)) {
                continue;
            }

            $fields[(string)$name] = [
                'name' => (string)$name,
                'title' => $titles[$name] ?? (string)$field->getTitle(),
                'type' => self::fieldType($field),
            ];
        }

        return $fields;
    }

    private static function fieldType($field): string
    {
        switch (strtolower((string)$field->getDataType())) {
            case 'boolean':
                return 'bool';
            case 'integer':
            case 'float':
                return 'number';
            case 'datetime':
                return 'datetime';
            case 'date':
                return 'date';
            default:
                return 'string';
        }
    }
}
