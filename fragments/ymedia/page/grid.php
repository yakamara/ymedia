<?php

/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

/** @var rex_yform_manager_query $query */
$query = $this->getVar('query');
/** @var rex_yform_manager_table $table */
$table = $this->getVar('table');
/** @var array $actionButtons */
$actionButtons = $this->getVar('actionButtons');
$rex_link_vars = $this->getVar('rex_link_vars');
$rex_yform_manager_opener = $this->getVar('rex_yform_manager_opener');
$rex_yform_manager_popup = $this->getVar('rex_yform_manager_popup');
$popup = $this->getVar('popup');
/** @var callable $hasDataPageFunctions */
$hasDataPageFunctions = $this->getVar('hasDataPageFunctions');

$actionButtonsViews = [];
$editLink = '';
foreach ($actionButtons as $buttonKey => $buttonParams) {
    $a = [];
    $a['href'] = rex_url::backendController(array_merge($rex_link_vars, $buttonParams['params']), false); // http_build_query($buttonParams['params']);
    if ('view' == $buttonKey || 'edit' == $buttonKey) {
        $editLink = $a['href'];
    }
    $a = array_merge($a, $buttonParams['attributes'] ?? []);
    $actionButtonsViews[$buttonKey] = '<a '.rex_string::buildAttributes($a).'>'.$buttonParams['content'].'</a>';
}

$items = [];
foreach ($query->find() as $ymedia) {
    $fragment = new rex_fragment();
    $fragment->setVar('ymedia', $ymedia, false);
    $fragment->setVar('editLink', $editLink);
    $fragment->setVar('actionButtonViews', $actionButtonsViews, false);
    $items[] = $fragment->parse('ymedia/page/media.php');
}

if ($hasDataPageFunctions('add') && $this->table->isGranted('EDIT', rex::getUser())) {
    echo '<a class="rex-link-expanded" href="index.php?' . http_build_query(array_merge(['func' => 'add'], $rex_link_vars)) . '"' . rex::getAccesskey(rex_i18n::msg('add'), 'add') . '><i class="rex-icon rex-icon-add"></i></a>';
}

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('yform_tabledata_overview'));
$fragment->setVar('options', implode('', $this->getVar('panelOptions')), false);
$fragment->setVar('body', '
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; grid-gap: 10px;">
                ' . implode('', $items) . '
        </div>', false);
echo $fragment->parse('core/page/section.php');
