<?php

if (class_exists('Bitrix\\Main\\Loader')) {
    \Bitrix\Main\Loader::registerAutoLoadClasses('bozenko.massfieldupdate', [
    'Bozenko\MassFieldUpdate\FieldRegistry' => 'lib/fieldregistry.php',
    'Bozenko\MassFieldUpdate\Permission' => 'lib/permission.php',
    'Bozenko\MassFieldUpdate\InputParser' => 'lib/inputparser.php',
    'Bozenko\MassFieldUpdate\MassUpdateService' => 'lib/massupdateservice.php',
    ]);
}
