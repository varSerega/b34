<?php

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/local/modules/bozenko.massfieldupdate/include.php';

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bozenko\MassFieldUpdate\FieldRegistry;
use Bozenko\MassFieldUpdate\InputParser;
use Bozenko\MassFieldUpdate\MassUpdateService;
use Bozenko\MassFieldUpdate\Permission;

Loader::includeModule('crm');
Loader::includeModule('iblock');

$request = Context::getCurrent()->getRequest();
$entity = preg_replace('/[^a-z]/', '', (string)$request->get('entity')) ?: 'lead';
$iblockId = (int)$request->get('iblock_id');

if (!$request->isPost() && $request->get('ajax') === 'fields') {
    header('Content-Type: application/json; charset=UTF-8');
    $fields = array_filter(
        FieldRegistry::getFields($entity, $iblockId),
        static function (array $field) use ($entity, $iblockId): bool {
            return Permission::canUpdate($entity, $field['name'], $iblockId);
        }
    );
    echo json_encode(['fields' => array_values($fields)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($request->isPost() && $request->get('ajax') === 'Y') {
    header('Content-Type: application/json; charset=UTF-8');
    $response = ['success' => false];

    try {
        if (!check_bitrix_sessid()) {
            throw new RuntimeException('Сессия истекла. Обновите страницу.');
        }

        $entity = preg_replace('/[^a-z]/', '', (string)$request->getPost('entity'));
        $iblockId = (int)$request->getPost('iblock_id');
        $fieldValues = [];
        foreach ((array)$request->getPost('fields') as $row) {
            $field = preg_replace('/[^A-Za-z0-9_]/', '', (string)($row['field'] ?? ''));
            if ($field !== '') {
                $fieldValues[$field] = (string)($row['value'] ?? '');
            }
        }
        $ids = InputParser::idsFromText((string)$request->getPost('ids'));
        if (!empty($_FILES['id_file']['name'])) {
            $ids = array_values(array_unique(array_merge($ids, InputParser::idsFromFile($_FILES['id_file']))));
        }
        if (!$ids) {
            throw new RuntimeException('Укажите хотя бы один корректный ID.');
        }
        $response['result'] = MassUpdateService::update(
            $entity,
            $ids,
            $fieldValues,
            $iblockId
        );
        $response['success'] = true;
    } catch (Throwable $exception) {
        $response['error'] = $exception->getMessage();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$fields = FieldRegistry::getFields($entity, $iblockId);
$fields = array_filter($fields, static function (array $field) use ($entity, $iblockId): bool {
            return Permission::canUpdate($entity, $field['name'], $iblockId);
});
$iblocks = [];
if (Loader::includeModule('iblock')) {
    $cursor = CIBlock::GetList(['NAME' => 'ASC'], ['ACTIVE' => 'Y']);
    while ($iblock = $cursor->Fetch()) {
        $iblocks[(int)$iblock['ID']] = $iblock['NAME'];
    }
}

$APPLICATION->SetTitle('Массовое изменение полей');
?>
<style>
    .mfu-form { max-width: 760px; }
    .mfu-row { margin: 0 0 18px; }
    .mfu-row label { display: block; font-weight: 600; margin: 0 0 6px; }
    .mfu-row input[type=text], .mfu-row textarea, .mfu-row select { box-sizing: border-box; min-height: 38px; padding: 8px 10px; width: 100%; }
    .mfu-field-row { align-items: flex-start; display: flex; gap: 8px; margin-bottom: 10px; }
    .mfu-field-row select { flex: 1; min-height: 38px; padding: 8px 10px; }
    .mfu-field-row textarea { flex: 1; min-height: 38px; padding: 8px 10px; }
    .mfu-help { color: #7f8c8d; font-size: 12px; margin-top: 5px; }
    .mfu-result { line-height: 1.6; }
</style>
<div class="mfu-form">
    <form id="mfu-form" enctype="multipart/form-data">
        <?=bitrix_sessid_post()?>
        <div class="mfu-row">
            <label for="mfu-entity">Сущность</label>
            <select name="entity" id="mfu-entity">
                <?php foreach (FieldRegistry::getEntities() as $key => $title): ?>
                    <option value="<?=htmlspecialcharsbx($key)?>"<?=$key === $entity ? ' selected' : ''?>><?=htmlspecialcharsbx($title)?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mfu-row" id="mfu-iblock-row"<?=$entity === 'product' ? '' : ' style="display:none"'?>>
            <label for="mfu-iblock">Инфоблок товаров</label>
            <select name="iblock_id" id="mfu-iblock">
                <option value="0">Выберите инфоблок</option>
                <?php foreach ($iblocks as $id => $title): ?>
                    <option value="<?=$id?>"<?=$id === $iblockId ? ' selected' : ''?>><?=htmlspecialcharsbx($title)?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mfu-row" id="mfu-fields">
            <label>Поля и значения</label>
            <div class="mfu-field-row">
                <select name="fields[0][field]" class="mfu-field-select">
                    <option value="">Выберите поле</option>
                    <?php foreach ($fields as $field): ?>
                        <option value="<?=htmlspecialcharsbx($field['name'])?>"><?=htmlspecialcharsbx($field['title'])?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="fields[0][value]" rows="2" placeholder="Новое значение"></textarea>
                <button type="button" class="ui-btn ui-btn-light-border mfu-remove-field">Удалить</button>
            </div>
            <button type="button" class="ui-btn ui-btn-light-border" id="mfu-add-field">Добавить поле</button>
        </div>
        <div class="mfu-row">
            <label for="mfu-ids">ID сущностей</label>
            <textarea name="ids" id="mfu-ids" rows="4" placeholder="Например: 12, 25, 31"></textarea>
            <div class="mfu-help">Можно указать ID через запятую, пробел или точку с запятой.</div>
        </div>
        <div class="mfu-row">
            <label for="mfu-file">Или файл с ID</label>
            <input type="file" name="id_file" id="mfu-file" accept=".csv,.txt,.xlsx">
            <div class="mfu-help">Поддерживаются CSV, TXT и XLSX. Используется первый столбец.</div>
        </div>
        <button type="submit" class="ui-btn ui-btn-success">Изменить</button>
    </form>
    <div id="mfu-result" class="mfu-result" style="display:none"></div>
</div>
<script>
(function() {
    var form = BX('mfu-form');
    var entity = BX('mfu-entity');
    var iblock = BX('mfu-iblock');
    var fieldsContainer = BX('mfu-fields');
    var addFieldButton = BX('mfu-add-field');
    var row = BX('mfu-iblock-row');
    var fieldsUrl = '<?=CUtil::JSEscape($APPLICATION->GetCurPage())?>';

    function loadFields() {
        row.style.display = entity.value === 'product' ? '' : 'none';
        var params = new URLSearchParams({ajax: 'fields', entity: entity.value, iblock_id: iblock.value});
        fetch(fieldsUrl + '?' + params.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function(response) { return response.json(); })
            .then(function(data) {
                fieldsContainer.querySelectorAll('.mfu-field-select').forEach(function(select) {
                    select.innerHTML = '<option value="">Выберите поле</option>';
                    (data.fields || []).forEach(function(item) {
                        var option = document.createElement('option');
                        option.value = item.name;
                        option.textContent = item.title;
                        select.appendChild(option);
                    });
                });
            });
    }

    addFieldButton.addEventListener('click', function() {
        var index = fieldsContainer.querySelectorAll('.mfu-field-row').length;
        var row = document.createElement('div');
        row.className = 'mfu-field-row';
        row.innerHTML = '<select name="fields[' + index + '][field]" class="mfu-field-select"><option value="">Выберите поле</option></select>' +
            '<textarea name="fields[' + index + '][value]" rows="2" placeholder="Новое значение"></textarea>' +
            '<button type="button" class="ui-btn ui-btn-light-border mfu-remove-field">Удалить</button>';
        fieldsContainer.insertBefore(row, addFieldButton);
        loadFields();
    });

    fieldsContainer.addEventListener('click', function(event) {
        if (event.target.classList.contains('mfu-remove-field')) {
            var rows = fieldsContainer.querySelectorAll('.mfu-field-row');
            if (rows.length > 1) {
                event.target.parentNode.remove();
            }
        }
    });

    entity.addEventListener('change', loadFields);
    iblock.addEventListener('change', loadFields);
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        var invalidField = Array.prototype.some.call(fieldsContainer.querySelectorAll('.mfu-field-select'), function(select) {
            return !select.value;
        });
        if (invalidField) {
            alert('Выберите хотя бы одно поле.');
            return;
        }
        var progressPopup;
        var popup = new BX.PopupWindow('mfu-confirm', null, {
            content: BX.create('div', {text: 'Внести выбранное изменение во все указанные сущности?'}),
            buttons: [new BX.PopupWindowButton({text: 'Изменить', className: 'popup-window-button-accept', events: {click: function() {
                popup.close();
                progressPopup = new BX.PopupWindow('mfu-progress', null, {
                    content: BX.create('div', {text: 'Изменяем данные, подождите...'}),
                    closeIcon: false,
                    closeByEsc: false,
                    buttons: []
                });
                progressPopup.show();
                var data = new FormData(form);
                data.append('ajax', 'Y');
                fetch(fieldsUrl, {method: 'POST', body: data})
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Сервер вернул ошибку ' + response.status + '.');
                        }
                        return response.json();
                    })
                    .then(function(result) {
                        progressPopup.close();
                        if (!result.success) {
                            throw new Error(result.error || 'Неизвестная ошибка');
                        }
                        var resultPopup = new BX.PopupWindow('mfu-result-popup', null, {
                            content: BX.create('div', {html: '<b>Данные успешно изменены</b><br>' + formatResult(result.result)}),
                            buttons: [new BX.PopupWindowButton({
                                text: 'Закрыть',
                                className: 'popup-window-button-accept',
                                events: {click: function() { resultPopup.close(); }}
                            })]
                        });
                        resultPopup.show();
                        clearForm();
                    })
                    .catch(function(error) {
                        if (progressPopup) {
                            progressPopup.close();
                        }
                        alert(error.message || 'Не удалось выполнить изменение.');
                    });
            }}}), new BX.PopupWindowButton({
                text: 'Отмена',
                className: 'popup-window-button-link-cancel',
                events: {click: function() { popup.close(); }}
            })]
        });
        popup.show();
    });

    function formatResult(result) {
        return 'Изменено сущностей: ' + result.updated.length + '<br>' +
            'Не найдено: ' + result.not_found.length + '<br>' +
            'Пропущено: ' + result.skipped.length + '<br>' +
            'Ошибок: ' + result.errors.length;
    }

    function clearForm() {
        form.reset();
        fieldsContainer.querySelectorAll('.mfu-field-row').forEach(function(fieldRow, index) {
            if (index > 0) {
                fieldRow.remove();
            }
        });
        loadFields();
    }
}());
</script>
<?php require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php'; ?>
