<?php

// -------------- Defaults
$subpage = rex_be_controller::getCurrentPagePart(2);

// -------------- Header
$subline = rex_be_controller::getPageObject('ymedia')->getSubpages();

echo rex_view::title(rex_i18n::msg('ymedia_title'), $subline);

// -------------- Include Page
rex_be_controller::includeCurrentPageSubPath();
