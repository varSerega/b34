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
Loader::includeModule('catalog');

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
    .mfu-progress-track { background: #e5e7eb; border-radius: 5px; height: 10px; margin: 12px 0 8px; overflow: hidden; width: 320px; }
    .mfu-progress-bar { background: #2fc6f6; border-radius: 5px; height: 100%; transition: width .2s ease; width: 0; }
    .mfu-progress-text { min-width: 320px; }
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
                        option.dataset.fieldType = item.type || 'string';
                        select.appendChild(option);
                    });
                    renderValueInput(select.closest('.mfu-field-row'));
                });
            });
    }

    function renderValueInput(fieldRow) {
        if (!fieldRow) {
            return;
        }

        var select = fieldRow.querySelector('.mfu-field-select');
        var currentInput = fieldRow.querySelector('[name$="[value]"]');
        var fieldType = select && select.options[select.selectedIndex]
            ? select.options[select.selectedIndex].dataset.fieldType || 'string'
            : 'string';
        var input;

        if (fieldType === 'bool' || fieldType === 'boolean') {
            input = document.createElement('select');
            input.innerHTML = '<option value="Y">Да</option><option value="N">Нет</option>';
        } else if (/date.*time|datetime/i.test(fieldType)) {
            input = document.createElement('input');
            input.type = 'datetime-local';
        } else if (/^date$/i.test(fieldType)) {
            input = document.createElement('input');
            input.type = 'date';
        } else if (/int|number|double|float|price|quantity/i.test(fieldType)) {
            input = document.createElement('input');
            input.type = 'number';
            input.step = fieldType === 'int' || /integer/i.test(fieldType) ? '1' : 'any';
        } else if (/crm_|user|employee|responsible|company|contact/i.test(fieldType)) {
            input = document.createElement('input');
            input.type = 'number';
            input.step = '1';
            input.placeholder = 'Введите ID';
        } else {
            input = document.createElement('textarea');
            input.rows = 2;
            input.placeholder = 'Новое значение';
        }

        input.name = select.name.replace('[field]', '[value]');
        input.className = 'mfu-value-input';
        if (currentInput) {
            input.value = currentInput.value;
            currentInput.replaceWith(input);
        } else {
            fieldRow.insertBefore(input, fieldRow.querySelector('.mfu-remove-field'));
        }
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

    fieldsContainer.addEventListener('change', function(event) {
        if (event.target.classList.contains('mfu-field-select')) {
            renderValueInput(event.target.closest('.mfu-field-row'));
        }
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
        var progressContent = BX.create('div', {children: [
            BX.create('div', {attrs: {className: 'mfu-progress-text'}, text: 'Изменяем данные, подождите...'}),
            BX.create('div', {attrs: {className: 'mfu-progress-track'}, children: [
                BX.create('div', {attrs: {className: 'mfu-progress-bar'}})
            ]})
        ]});
        var popup = new BX.PopupWindow('mfu-confirm', null, {
            content: BX.create('div', {text: 'Внести выбранное изменение во все указанные сущности?'}),
            buttons: [new BX.PopupWindowButton({text: 'Изменить', className: 'popup-window-button-accept', events: {click: function() {
                popup.close();
                progressPopup = new BX.PopupWindow('mfu-progress', null, {
                    content: progressContent,
                    closeIcon: false,
                    closeByEsc: false,
                    buttons: []
                });
                progressPopup.show();
                runUpdates(progressContent)
                    .then(function(result) {
                        progressPopup.close();
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

    function runUpdates(progressContent) {
        var ids = parseIds();
        var batches = [];
        var batchSize = 100;
        var result = {updated: [], not_found: [], skipped: [], errors: []};

        if (ids.length) {
            for (var offset = 0; offset < ids.length; offset += batchSize) {
                batches.push(ids.slice(offset, offset + batchSize));
            }
        } else {
            batches.push(null);
        }

        var batchIndex = 0;
        function sendNextBatch() {
            if (batchIndex >= batches.length) {
                return Promise.resolve({result: result});
            }

            var batch = batches[batchIndex++];
            var data = new FormData(form);
            data.delete('ajax');
            data.append('ajax', 'Y');
            if (batch) {
                data.delete('ids');
                data.delete('id_file');
                data.append('ids', batch.join(','));
                setProgress(progressContent, (batchIndex - 1) * batchSize, ids.length, result.updated.length);
            } else {
                progressContent.querySelector('.mfu-progress-text').textContent = 'Обрабатываем файл с ID...';
            }

            return fetch(fieldsUrl, {method: 'POST', body: data})
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Сервер вернул ошибку ' + response.status + '.');
                    }
                    return response.json();
                })
                .then(function(response) {
                    if (!response.success) {
                        throw new Error(response.error || 'Неизвестная ошибка');
                    }
                    result.updated = result.updated.concat(response.result.updated || []);
                    result.not_found = result.not_found.concat(response.result.not_found || []);
                    result.skipped = result.skipped.concat(response.result.skipped || []);
                    result.errors = result.errors.concat(response.result.errors || []);
                    if (batch) {
                        setProgress(progressContent, Math.min(batchIndex * batchSize, ids.length), ids.length, result.updated.length);
                    }
                    return sendNextBatch();
                });
        }

        return sendNextBatch();
    }

    function setProgress(progressContent, processed, total, updated) {
        var percentage = total ? Math.round(processed / total * 100) : 0;
        progressContent.querySelector('.mfu-progress-bar').style.width = percentage + '%';
        progressContent.querySelector('.mfu-progress-text').textContent = 'Обработано: ' + processed + ' из ' + total + '. Изменено: ' + updated;
    }

    function parseIds() {
        var input = form.querySelector('[name="ids"]');
        var unique = {};
        if (!input || !input.value.trim()) {
            return [];
        }

        input.value.trim().split(/[,;\s]+/).forEach(function(value) {
            if (/^\d+$/.test(value) && Number(value) > 0) {
                unique[value] = Number(value);
            }
        });

        return Object.keys(unique).map(function(value) { return unique[value]; });
    }

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

    loadFields();
}());
</script>
<?php require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php'; ?>
