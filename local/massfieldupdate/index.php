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
        $field = preg_replace('/[^A-Za-z0-9_]/', '', (string)$request->getPost('field'));
        $iblockId = (int)$request->getPost('iblock_id');
        $ids = InputParser::idsFromText((string)$request->getPost('ids'));
        if (!empty($_FILES['id_file']['name'])) {
            $ids = array_values(array_unique(array_merge($ids, InputParser::idsFromFile($_FILES['id_file']))));
        }
        if (!$ids) {
            throw new RuntimeException('Укажите хотя бы один корректный ID.');
        }
        if (!FieldRegistry::isAllowed($field) || !Permission::canUpdate($entity, $field, $iblockId)) {
            throw new RuntimeException('У вас нет права на изменение выбранного поля.');
        }

        $response['result'] = MassUpdateService::update(
            $entity,
            $ids,
            $field,
            (string)$request->getPost('value'),
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
        <div class="mfu-row">
            <label for="mfu-field">Поле</label>
            <select name="field" id="mfu-field">
                <option value="">Выберите поле</option>
                <?php foreach ($fields as $field): ?>
                    <option value="<?=htmlspecialcharsbx($field['name'])?>"><?=htmlspecialcharsbx($field['title'])?> (<?=htmlspecialcharsbx($field['name'])?>)</option>
                <?php endforeach; ?>
            </select>
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
        <div class="mfu-row">
            <label for="mfu-value">Новое значение</label>
            <textarea name="value" id="mfu-value" rows="4"></textarea>
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
    var field = BX('mfu-field');
    var row = BX('mfu-iblock-row');
    var fieldsUrl = '<?=CUtil::JSEscape($APPLICATION->GetCurPage())?>';

    function loadFields() {
        row.style.display = entity.value === 'product' ? '' : 'none';
        var params = new URLSearchParams({ajax: 'fields', entity: entity.value, iblock_id: iblock.value});
        fetch(fieldsUrl + '?' + params.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function(response) { return response.json(); })
            .then(function(data) {
                field.innerHTML = '<option value="">Выберите поле</option>';
                (data.fields || []).forEach(function(item) {
                    field.insertAdjacentHTML('beforeend', '<option value="' + BX.util.htmlspecialchars(item.name) + '">' + BX.util.htmlspecialchars(item.title) + ' (' + BX.util.htmlspecialchars(item.name) + ')</option>');
                });
            });
    }

    entity.addEventListener('change', loadFields);
    iblock.addEventListener('change', loadFields);
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (!field.value) {
            alert('Выберите поле.');
            return;
        }
        var popup = new BX.PopupWindow('mfu-confirm', null, {
            content: BX.create('div', {text: 'Внести выбранное изменение во все указанные сущности?'}),
            buttons: [new BX.PopupWindowButton({text: 'Изменить', className: 'popup-window-button-accept', events: {click: function() {
                popup.close();
                var data = new FormData(form);
                data.append('ajax', 'Y');
                fetch(fieldsUrl, {method: 'POST', body: data})
                    .then(function(response) { return response.json(); })
                    .then(function(result) {
                        var content = result.success ? formatResult(result.result) : BX.util.htmlspecialchars(result.error || 'Неизвестная ошибка');
                        new BX.PopupWindow('mfu-result-popup', null, {content: BX.create('div', {html: content}), buttons: [BX.PopupWindowButton.createOkButton('Закрыть')]}).show();
                    });
            }}}), BX.PopupWindowButton.createCancelButton('Отмена')]
        });
        popup.show();
    });

    function formatResult(result) {
        return '<b>Изменения внесены</b><br>' +
            'Изменено: ' + result.updated.length + '<br>' +
            'Не найдено: ' + result.not_found.length + '<br>' +
            'Пропущено: ' + result.skipped.length + '<br>' +
            'Ошибок: ' + result.errors.length;
    }
}());
</script>
<?php require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php'; ?>
