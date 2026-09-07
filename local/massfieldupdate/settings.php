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

if ($request->get('ajax') === 'groups') {
    header('Content-Type: application/json; charset=UTF-8');
    $q = trim((string)$request->get('q'));
    $filter = ['ACTIVE' => 'Y'];
    if ($q !== '') {
        $filter['%NAME'] = $q;
    }
    $res = CGroup::GetList('name', 'asc', $filter);
    $found = [];
    while ($group = $res->Fetch()) {
        $found[] = ['id' => (int)$group['ID'], 'name' => (string)$group['NAME']];
    }
    echo json_encode(['groups' => array_slice($found, 0, 50)], JSON_UNESCAPED_UNICODE);
    exit;
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
$entitySections = [];
foreach (FieldRegistry::getEntities() as $entity => $title) {
    $fields = [];
    if ($entity === 'product') {
        foreach ($iblocks as $iblockId => $iblockName) {
            foreach (FieldRegistry::getFields($entity, $iblockId) as $field) {
                $fields[$entity.'.'.$iblockId.'.'.$field['name']] = $iblockName.' / '.$field['title'];
            }
        }
    } else {
        foreach (FieldRegistry::getFields($entity) as $field) {
            $fields[$entity.'.'.$field['name']] = $field['title'];
        }
    }
    $entitySections[$entity] = ['title' => $title, 'fields' => $fields];
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
$APPLICATION->SetTitle('Права массового изменения полей');
?>
<style>
    .mfu-permissions { border-collapse: collapse; width: 100%; }
    .mfu-permissions th, .mfu-permissions td { border-bottom: 1px solid #e5e5e5; padding: 9px; text-align: left; vertical-align: top; }
    .mfu-entity-section { background: #fff; border: 1px solid #dfe4e8; border-radius: 6px; margin-bottom: 12px; }
    .mfu-entity-section summary { cursor: pointer; font-size: 16px; font-weight: 600; padding: 14px 16px; }
    .mfu-entity-section summary span { color: #8b949e; font-size: 13px; font-weight: 400; }
    .mfu-entity-section[open] summary { border-bottom: 1px solid #dfe4e8; }
    .mfu-entity-section .mfu-permissions { margin: 0; }
    .mfu-empty { color: #7f8c8d; padding: 0 16px 16px; }
    .mfu-group-picker { position: relative; }
    .mfu-group-search { box-sizing: border-box; width: 100%; min-height: 36px; padding: 7px 10px; }
    .mfu-group-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .mfu-group-tag { background: #eef2f4; border-radius: 16px; padding: 5px 10px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; }
    .mfu-group-tag .mfu-group-remove { cursor: pointer; color: #888; font-weight: 700; }
    .mfu-group-dropdown { position: absolute; z-index: 20; background: #fff; border: 1px solid #dfe4e8; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,.12); max-height: 220px; overflow: auto; width: 100%; margin-top: 4px; }
    .mfu-group-option { padding: 8px 10px; cursor: pointer; }
    .mfu-group-option:hover { background: #f1f5f7; }
</style>
<?php if ($request->get('saved') === 'Y'): ?><div class="ui-alert ui-alert-success"><span class="ui-alert-message">Настройки сохранены.</span></div><?php endif; ?>
<form method="post">
    <?=bitrix_sessid_post()?>
    <?php foreach ($entitySections as $entity => $section): ?>
        <details class="mfu-entity-section"<?=$entity === 'deal' ? ' open' : ''?>>
            <summary><?=htmlspecialcharsbx($section['title'])?> <span>(<?=count($section['fields'])?> полей)</span></summary>
            <?php if (!$section['fields']): ?>
                <p class="mfu-empty">Для этой сущности поля не найдены.</p>
            <?php else: ?>
                <table class="mfu-permissions">
                    <thead><tr><th>Поле</th><th>Группы с правом изменения</th></tr></thead>
                    <tbody>
                    <?php foreach ($section['fields'] as $key => $fieldTitle): ?>
                        <?php $selected = $permissionMap[$key] ?? []; ?>
                        <tr>
                            <td><?=htmlspecialcharsbx($fieldTitle)?><br><small><?=htmlspecialcharsbx($key)?></small></td>
                            <td>
                                <div class="mfu-group-picker" data-key="<?=htmlspecialcharsbx($key)?>">
                                    <input type="search" class="mfu-group-search" placeholder="Найти группу..." autocomplete="off">
                                    <div class="mfu-group-tags">
                                        <?php foreach ((array)$selected as $gid): $gid = (int)$gid; if ($gid <= 0) continue; ?>
                                            <span class="mfu-group-tag" data-id="<?=$gid?>"><?=htmlspecialcharsbx($groups[$gid] ?? 'Группа '.$gid)?><span class="mfu-group-remove" title="Удалить">×</span></span>
                                            <input type="hidden" name="permissions[<?=htmlspecialcharsbx($key)?>][]" value="<?=$gid?>">
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mfu-group-dropdown" style="display:none"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </details>
    <?php endforeach; ?>
    <br><button class="ui-btn ui-btn-success" type="submit">Сохранить</button>
</form>
<script>
(function() {
    var settingsUrl = '<?=CUtil::JSEscape($APPLICATION->GetCurPage())?>';
    var timer;

    document.addEventListener('input', function(event) {
        if (!event.target.classList || !event.target.classList.contains('mfu-group-search')) {
            return;
        }
        var picker = event.target.closest('.mfu-group-picker');
        var dropdown = picker.querySelector('.mfu-group-dropdown');
        var q = event.target.value.trim();
        clearTimeout(timer);
        if (q === '') {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            return;
        }
        timer = setTimeout(function() {
            fetch(settingsUrl + '?ajax=groups&q=' + encodeURIComponent(q))
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    dropdown.innerHTML = '';
                    (data.groups || []).forEach(function(group) {
                        var option = document.createElement('div');
                        option.className = 'mfu-group-option';
                        option.setAttribute('data-id', group.id);
                        option.textContent = group.name;
                        dropdown.appendChild(option);
                    });
                    dropdown.style.display = dropdown.children.length ? 'block' : 'none';
                });
        }, 250);
    });

    document.addEventListener('click', function(event) {
        var target = event.target;
        var picker = target.closest('.mfu-group-picker');

        if (target.classList && target.classList.contains('mfu-group-option') && picker) {
            var tags = picker.querySelector('.mfu-group-tags');
            var id = target.getAttribute('data-id');
            var name = target.textContent;
            if (!picker.querySelector('.mfu-group-tag[data-id="' + id + '"]')) {
                var tag = document.createElement('span');
                tag.className = 'mfu-group-tag';
                tag.setAttribute('data-id', id);
                tag.appendChild(document.createTextNode(name));
                var remove = document.createElement('span');
                remove.className = 'mfu-group-remove';
                remove.title = 'Удалить';
                remove.textContent = '\u00d7';
                tag.appendChild(remove);
                tags.appendChild(tag);

                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'permissions[' + picker.getAttribute('data-key') + '][]';
                hidden.value = id;
                tags.appendChild(hidden);
            }
            picker.querySelector('.mfu-group-search').value = '';
            picker.querySelector('.mfu-group-dropdown').style.display = 'none';
            return;
        }

        if (target.classList && target.classList.contains('mfu-group-remove') && picker) {
            var tag = target.closest('.mfu-group-tag');
            var id = tag.getAttribute('data-id');
            picker.querySelectorAll('input[type=hidden][value="' + id + '"]').forEach(function(input) {
                input.remove();
            });
            tag.remove();
            return;
        }

        if (!picker) {
            document.querySelectorAll('.mfu-group-dropdown').forEach(function(dropdown) {
                dropdown.style.display = 'none';
            });
        }
    });
}());
</script>
<?php require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php'; ?>
