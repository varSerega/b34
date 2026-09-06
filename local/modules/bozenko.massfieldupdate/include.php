<?php

if (class_exists('Bitrix\\Main\\Loader')) {
    \Bitrix\Main\Loader::registerAutoLoadClasses(null, [
        'Bozenko\MassFieldUpdate\FieldRegistry' => __DIR__.'/lib/fieldregistry.php',
        'Bozenko\MassFieldUpdate\Permission' => __DIR__.'/lib/permission.php',
        'Bozenko\MassFieldUpdate\InputParser' => __DIR__.'/lib/inputparser.php',
        'Bozenko\MassFieldUpdate\MassUpdateService' => __DIR__.'/lib/massupdateservice.php',
    ]);
}
