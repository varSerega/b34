<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';
require_once($_SERVER["DOCUMENT_ROOT"] . "/appssk/crm_events_listener/agency.chess.event.handler.php");

CModule::IncludeModule('iblock');

if (CIBlock::GetPermission(1182) >= 'W') {
  $products = $_POST['products'];
  $products_rotate = $_POST['products_rotate'];

  foreach ($products as $product_id => $coordinates) {
    CIBlockElement::SetPropertyValues(
      $product_id,
      14,
      $coordinates . ';' . $products_rotate[$product_id],
      11807
    );

    // Обновляем данные агентской шахматки
    iBlockWebHookAfterEvent_ApiChess('ONCRMPRODUCTUPDATE', $product_id);
  }

  $element = new CIBlockElement;
}

header('Location: ..');
