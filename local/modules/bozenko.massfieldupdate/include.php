<?php

use Bitrix\Main\Loader;

$composerAutoload = $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

Loader::registerAutoLoadClasses('bozenko.massfieldupdate', [
    'Bozenko\MassFieldUpdate\FieldRegistry' => 'lib/fieldregistry.php',
    'Bozenko\MassFieldUpdate\Permission' => 'lib/permission.php',
    'Bozenko\MassFieldUpdate\InputParser' => 'lib/inputparser.php',
    'Bozenko\MassFieldUpdate\MassUpdateService' => 'lib/massupdateservice.php',
]);
