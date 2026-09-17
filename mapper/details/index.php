<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Картограф");

$map = CIBlockElement::GetList(null, [
  'IBLOCK_ID' => 1182,
  'ID' => $_GET['id'],
], null, null, [
  'ID',
  'PROPERTY_MAP',
  'PROPERTY_BUILD',
  'PROPERTY_FLOOR',
  'PROPERTY_ENTRANCE',
  'PROPERTY_SECTION',
])->Fetch();

$image = CFile::GetByID($map['PROPERTY_MAP_VALUE'])->Fetch();
[$image_width, $image_height,, $image_size] = getimagesize(
  $_SERVER["DOCUMENT_ROOT"] . $image['SRC']
);

$maps_contents = file_get_contents('https://bx.sskuban.ru/restapi/map/?id=' . $_GET['id']);
$maps = json_decode($maps_contents, true)['maps'];
?>

<style>
  .mapper {
    position: relative;
    border: 1px solid lightgrey;
    overflow: auto;
  }

  .mapper--image {
    position: relative;
    z-index: 1;
  }

  .point {
    width: 1px;
    height: 1px;
    position: absolute;
    z-index: 2;
  }

  .map-object {
    transform: translate(-50%, -50%);
    position: absolute;
    overflow: hidden;
    line-height: 1;
    font-size: .8em;
    cursor: pointer;
  }

  .map-object--wrap {
    text-align: center;
    box-shadow: 0 0 4px rgba(0, 0, 0, .2);
    border-radius: 4px;
    background: white;
  }

  .map-object--title {
    padding: 1px 2px;
  }

  .map-object--info {
    padding: 1px 2px;
  }

  .map-object--polygon {
    position: relative;
  }

  .--small {
    transform: scale(0.8);
  }

  .map-object--number {
    position: absolute;
    transform: translate(-50%, -50%) rotate(-90deg);
    top: 64%;
    left: 45%;
    width: 101%;
    height: 25%;
    border-radius: 4px;
    background: white;
    font-size: 10px;
    text-align: center;
    font-weight: bold;
  }
</style>

<div class="container">
  <form action="action.php" method="post">
    <input type="hidden" name="id" value="<?= $map['ID'] ?>">

    <?php foreach ($maps as $map) { ?>
      <div class="bg-white my-3 p-3 rounded shadow-sm">
        <div class="mapper js-mapper">
          <div class="mapper--image">
            <img
              class="js-mapper--image"
              src="<?= $map['image']['SRC'] ?>"
              alt=""
              style="width: <?= $map['image']['WIDTH'] ?>px; height: <?= $map['image']['HEIGHT'] ?>px;">
          </div>
          <?php foreach ($map['products'] as $product) {
            $map_coord_0 = false;
            if ($product['map_coordinates'] && is_array($product['map_coordinates']) && count($product['map_coordinates']) > 0 && $product['map_coordinates'][0]) $map_coord_0 = $product['map_coordinates'][0];

            $map_coord_1 = false;
            if ($product['map_coordinates'] && is_array($product['map_coordinates']) && count($product['map_coordinates']) > 0 && $product['map_coordinates'][1]) $map_coord_1 = $product['map_coordinates'][1];

            $map_coord_2 = false;
            if ($product['map_coordinates'] && is_array($product['map_coordinates']) && count($product['map_coordinates']) > 0 && $product['map_coordinates'][2]) $map_coord_2 = $product['map_coordinates'][2];
          ?>
            <div
              class="point js-draggable"
              style="top: <?= $map['image']['HEIGHT'] / 100 * $map_coord_0 ?: 10 ?>px; left: <?= $map['image']['WIDTH'] / 100 * $map_coord_1 ?: 10 ?>px;">
              <input
                type="hidden"
                class="js-input-coordinates"
                name="products[<?= $product['ID'] ?>]"
                value="<?= $product['PROPERTY_MAP_COORDINATES_VALUE'] ?>">

              <input
                type="hidden"
                class="js-input-rotate"
                name="products_rotate[<?= $product['ID'] ?>]"
                value="<?= $map_coord_2 ?>">

              <?php if ($product['PROPERTY_PROPERTY_TYPE_ENUM_ID'] == 1437) { ?>
                <div class="map-object js-rotate" data-rotate="<?= $map_coord_2 ?>" style="transform: translate(-50%, -50%) rotate(<?= $map_coord_2 ?>deg)">

                  <div class="map-object--polygon <? if ($map['ID'] == 1557400) { ?>--small<? } ?>">
                    <img src="/appssk/assets/images/auto.svg" alt="" style="width: 20px;">

                    <div class="map-object--number bg-warning">
                      <?= $product['PROPERTY_NOMER_VALUE'] ?>
                    </div>
                  </div>
                </div>
              <?php } else { ?>
                <div class="map-object">
                  <div class="map-object--wrap">
                    <div class="map-object--title bg-warning">
                      <small style="font-size: .6em">№</small>&nbsp;<?= $product['PROPERTY_NOMER_VALUE'] ?>
                    </div>

                    <div class="map-object--info">
                      <?= $product['PROPERTY_S_OBSHAYA_VALUE'] ?>
                    </div>
                  </div>
                </div>
              <?php } ?>
            </div>
          <?php } ?>
        </div>
      </div>
    <?php } ?>

    <div class="bg-white my-3 p-3 rounded shadow-sm">
      <div class="row">
        <div class="col"></div>

        <div class="col-auto">
          <button class="btn btn-primary" type="submit">Сохранить</button>
        </div>
      </div>
    </div>
  </form>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>