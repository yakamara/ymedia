<?php

rex_yform_manager_table::deleteCache();

$content = rex_file::get(rex_path::addon('ycom', 'install/tablesets/ymedia.json'));
if (is_string($content) && '' != $content) {
    rex_yform_manager_table_api::importTablesets($content);
}

$content = rex_file::get(rex_path::addon('ycom', 'install/tablesets/ymedia_tag.json'));
if (is_string($content) && '' != $content) {
    rex_yform_manager_table_api::importTablesets($content);
}

$content = rex_file::get(rex_path::addon('ycom', 'install/tablesets/ymedia_category.json'));
if (is_string($content) && '' != $content) {
    rex_yform_manager_table_api::importTablesets($content);
}


