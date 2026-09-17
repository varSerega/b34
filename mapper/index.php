<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$title = "Карты";
$APPLICATION->SetTitle($title);
$APPLICATION->SetPageProperty(
  'top-bg',
  'bg-secondary',
);

// Литеры
$result_maps = CIBlockElement::GetList(
  [
    'PROPERTY_BUILD' => 'DESC',
    'PROPERTY_FLOOR' => 'DESC',
    'PROPERTY_ENTRANCE' => 'DESC',
    'NAME' => 'DESC',
  ],
  [
    'IBLOCK_ID' => 1182,
  ],
  null,
  ['nPageSize' => 999],
  [
    'ID',
    'NAME',
    'PROPERTY_BUILD',
    'PROPERTY_FLOOR',
    'PROPERTY_ENTRANCE',
    'PROPERTY_SECTION',
  ]
);
$maps = [];

while ($element = $result_maps->Fetch()) {
  $build = CIBlockElement::GetByID($element['PROPERTY_BUILD_VALUE'])->Fetch();
  $element['build'] = $build;
  $maps[] = $element;
}

?>
<section class="container">
  <h1 class="mb-3"><?= $title ?></h1>

  <?php foreach ($maps as $element) { ?>
    <div class="bg-white my-3 py-3 px-3 rounded shadow-sm">
      <div class="row">
        <div class="col-auto">
          <a
            href="details/?id=<?= $element['ID'] ?>"
            class="btn btn-warning btn-sm">
            Подробно
          </a>
        </div>

        <div class="col">
          <strong>
            <a
              href="details/?id=<?= $element['ID'] ?>"
              class="text-decoration-none text-body">
              <?= $element['NAME'] ?>,
              Строение <?= $element['build']['NAME'] ?>,
              Этаж <?= $element['PROPERTY_FLOOR_VALUE'] ?>,
              Подъезд <?= $element['PROPERTY_ENTRANCE_VALUE'] ?>.
              <? if ($element['PROPERTY_SECTION_VALUE']) { ?>
                Секция <?= $element['PROPERTY_SECTION_VALUE'] ?>.
              <? } ?>
            </a>
          </strong>
        </div>
      </div>
    </div>
  <?php } ?>
</section>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>