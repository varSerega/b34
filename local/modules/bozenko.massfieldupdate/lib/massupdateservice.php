<?php

namespace Bozenko\MassFieldUpdate;

final class MassUpdateService
{
    public static function update(string $entity, array $ids, string $field, string $value, int $iblockId = 0): array
    {
        if (!isset(FieldRegistry::getEntities()[$entity])) {
            throw new \RuntimeException('Неизвестный тип сущности.');
        }

        $availableFields = FieldRegistry::getFields($entity, $iblockId);
        if (!isset($availableFields[$field])) {
            throw new \RuntimeException('Выбранное поле недоступно для этой сущности.');
        }
        if (!FieldRegistry::isAllowed($field) || !Permission::canUpdate($entity, $field, $iblockId)) {
            throw new \RuntimeException('Нет права на изменение выбранного поля.');
        }

        $result = ['updated' => [], 'not_found' => [], 'skipped' => [], 'errors' => []];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) {
                continue;
            }

            try {
                $updated = $entity === 'product'
                    ? self::updateProduct($id, $field, $value, $iblockId)
                    : self::updateCrm($entity, $id, $field, $value);

                if ($updated === null) {
                    $result['not_found'][] = $id;
                } elseif ($updated === false) {
                    $result['skipped'][] = $id;
                } else {
                    $result['updated'][] = $id;
                }
            } catch (\Throwable $exception) {
                $result['errors'][] = ['id' => $id, 'message' => $exception->getMessage()];
            }
        }

        return $result;
    }

    private static function updateCrm(string $entity, int $id, string $field, string $value): ?bool
    {
        $classes = [
            'lead' => ['class' => 'CCrmLead', 'module' => 'crm'],
            'deal' => ['class' => 'CCrmDeal', 'module' => 'crm'],
            'contact' => ['class' => 'CCrmContact', 'module' => 'crm'],
            'company' => ['class' => 'CCrmCompany', 'module' => 'crm'],
        ];
        if (!isset($classes[$entity]) || !\Bitrix\Main\Loader::includeModule('crm')) {
            throw new \RuntimeException('CRM недоступен.');
        }

        $class = $classes[$entity]['class'];
        $item = new $class(false);
        $fields = [$field => self::normalizeValue($value, $field)];
        if (!$item->Update($id, $fields, true, true, ['CURRENT_USER' => true])) {
            if (method_exists($item, 'GetLastError')) {
                throw new \RuntimeException((string)$item->GetLastError());
            }
            throw new \RuntimeException('CRM не принял изменение.');
        }

        return true;
    }

    private static function updateProduct(int $id, string $field, string $value, int $iblockId): ?bool
    {
        if ($iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
            throw new \RuntimeException('Не выбран инфоблок товаров.');
        }

        $element = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'ID' => $id], false, false, ['ID'])->Fetch();
        if (!$element) {
            return null;
        }

        if (strpos($field, 'PROPERTY_') === 0) {
            $code = substr($field, 9);
            if (!\CIBlockElement::SetPropertyValuesEx($id, $iblockId, [$code => $value])) {
                throw new \RuntimeException('Не удалось изменить свойство товара.');
            }
            return true;
        }

        $update = [$field => self::normalizeValue($value, $field)];
        if (!\CIBlockElement::Update($id, $update)) {
            global $APPLICATION;
            $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;
            $message = is_object($exception) ? $exception->GetString() : '';
            throw new \RuntimeException($message ?: 'Не удалось изменить товар.');
        }

        return true;
    }

    private static function normalizeValue(string $value, string $field)
    {
        if (in_array(strtoupper($field), ['SORT', 'PRICE', 'OPPORTUNITY', 'QUANTITY'], true) && is_numeric($value)) {
            return $value + 0;
        }

        if (strtoupper($field) === 'ACTIVE') {
            return in_array(strtoupper(trim($value)), ['Y', 'YES', '1', 'ДА'], true) ? 'Y' : 'N';
        }

        return $value;
    }
}
