<?php

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/bozenko.massfieldupdate/include.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bozenko\MassFieldUpdate\FieldRegistry;
use Bozenko\MassFieldUpdate\Permission;

if (!Permission::isAdmin()) {
    require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
    ShowError('Доступ разрешён только администраторам.');
    require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
    exit;
}

Loader::includeModule('iblock');
$request = Context::getCurrent()->getRequest();
$iblocks = [];
if (Loader::includeModule('iblock')) {
    $cursor = CIBlock::GetList(['NAME' => 'ASC'], ['ACTIVE' => 'Y']);
    while ($iblock = $cursor->Fetch()) {
        $iblocks[(int)$iblock['ID']] = $iblock['NAME'];
    }
}
$groups = [];
$groupCursor = CGroup::GetList('c_sort', 'asc', ['ACTIVE' => 'Y']);
while ($group = $groupCursor->Fetch()) {
    $groups[(int)$group['ID']] = $group['NAME'];
}

if ($request->isPost() && check_bitrix_sessid()) {
    $permissions = [];
    foreach ((array)$request->getPost('permissions') as $key => $groupIds) {
        $key = preg_replace('/[^a-z0-9_.-]/i', '', (string)$key);
        $permissions[$key] = array_values(array_filter(array_map('intval', (array)$groupIds)));
    }
    Permission::saveMap($permissions);
    LocalRedirect($APPLICATION->GetCurPageParam('saved=Y', []));
}

$permissionMap = Permission::getMap();
$fieldSets = [];
foreach (FieldRegistry::getEntities() as $entity => $title) {
    if ($entity === 'product') {
        foreach ($iblocks as $iblockId => $iblockName) {
            foreach (FieldRegistry::getFields($entity, $iblockId) as $field) {
                $fieldSets[$entity.'.'.$iblockId.'.'.$field['name']] = $title.' / '. $iblockName.' / '.$field['title'];
            }
        }
    } else {
        foreach (FieldRegistry::getFields($entity) as $field) {
            $fieldSets[$entity.'.'.$field['name']] = $title.' / '.$field['title'];
        }
    }
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
$APPLICATION->SetTitle('Права массового изменения полей');
?>
<style>
    .mfu-permissions { border-collapse: collapse; width: 100%; }
    .mfu-permissions th, .mfu-permissions td { border-bottom: 1px solid #e5e5e5; padding: 9px; text-align: left; vertical-align: top; }
    .mfu-permissions select { min-width: 260px; }
</style>
<?php if ($request->get('saved') === 'Y'): ?><div class="ui-alert ui-alert-success"><span class="ui-alert-message">Настройки сохранены.</span></div><?php endif; ?>
<form method="post">
    <?=bitrix_sessid_post()?>
    <table class="mfu-permissions">
        <thead><tr><th>Поле</th><th>Группы с правом изменения</th></tr></thead>
        <tbody>
        <?php foreach ($fieldSets as $key => $title): ?>
            <?php $selected = $permissionMap[$key] ?? []; ?>
            <tr>
                <td><?=htmlspecialcharsbx($title)?><br><small><?=htmlspecialcharsbx($key)?></small></td>
                <td>
                    <select name="permissions[<?=htmlspecialcharsbx($key)?>][]" multiple size="4">
                        <?php foreach ($groups as $groupId => $groupName): ?>
                            <option value="<?=$groupId?>"<?=in_array($groupId, array_map('intval', (array)$selected), true) ? ' selected' : ''?>><?=htmlspecialcharsbx($groupName)?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <br><button class="ui-btn ui-btn-success" type="submit">Сохранить</button>
</form>
<?php require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php'; ?>
