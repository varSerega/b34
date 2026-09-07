<?php

namespace Bozenko\MassFieldUpdate;

final class MassUpdateService
{
    public static function update(string $entity, array $ids, array $fieldValues, int $iblockId = 0): array
    {
        if (!isset(FieldRegistry::getEntities()[$entity])) {
            throw new \RuntimeException('Неизвестный тип сущности.');
        }

        $availableFields = FieldRegistry::getFields($entity, $iblockId);
        if (!$fieldValues) {
            throw new \RuntimeException('Выберите хотя бы одно поле.');
        }
        foreach ($fieldValues as $field => $value) {
            if (!isset($availableFields[$field])) {
                throw new \RuntimeException('Выбранное поле недоступно для этой сущности.');
            }
            if (!FieldRegistry::isAllowed($field) || !Permission::canUpdate($entity, $field, $iblockId)) {
                throw new \RuntimeException('Нет права на изменение поля: '.$availableFields[$field]['title'].'.');
            }
        }

        $result = ['updated' => [], 'not_found' => [], 'skipped' => [], 'errors' => []];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) {
                continue;
            }

            try {
                $hasUpdate = false;
                foreach ($fieldValues as $field => $value) {
                    $updated = $entity === 'product'
                        ? self::updateProduct($id, $field, (string)$value, $iblockId)
                        : self::updateCrm($entity, $id, $field, (string)$value);

                    if ($updated === null) {
                        $result['not_found'][] = $id;
                        break;
                    }
                    if ($updated === false) {
                        $result['skipped'][] = $id;
                        break;
                    }
                    $hasUpdate = true;
                }
                if ($hasUpdate && !in_array($id, $result['updated'], true)) {
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
        if ($iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('catalog')) {
            throw new \RuntimeException('Не выбран инфоблок товаров.');
        }

        $product = \CCatalogProduct::GetByID($id);
        if (!$product) {
            return null;
        }

        $availableFields = FieldRegistry::getFields('product', $iblockId);
        if (!isset($availableFields[$field])) {
            throw new \RuntimeException('Выбранное поле не относится к товарному каталогу.');
        }

        if (!\CCatalogProduct::Update($id, [$field => self::normalizeValue($value, $field)])) {
            throw new \RuntimeException('Не удалось изменить поле товарного каталога.');
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
