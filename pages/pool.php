<?php

$table_name = 'rex_ymedia';
$_REQUEST['table_name'] = $table_name;

rex_extension::register(
    'YFORM_MANAGER_DATA_PAGE_HEADER',
    static function (rex_extension_point $ep) {
        if ($ep->getParam('yform')->table->getTableName() === $ep->getParam('table_name')) {
            return '';
        }
        return $ep->getSubject();
    },
    rex_extension::EARLY,
    ['table_name' => $table_name]
);

include rex_path::plugin('yform', 'manager', 'pages/data_edit.php');
