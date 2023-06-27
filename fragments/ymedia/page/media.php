<?php

/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

/** @var rex_ymedia $dataset */
$ymedia = $this->getVar('ymedia');
$editLink = $this->getVar('editLink');
$actionButtonViews = $this->getVar('actionButtonViews');
$editLink = str_replace('___id___', $ymedia->getId(), $editLink);
$actionButtonViews = str_replace('___id___', $ymedia->getId(), $actionButtonViews);

$fragment = new rex_fragment();
$fragment->setVar('buttons', $actionButtonViews, false);
$buttons = $fragment->parse('yform/manager/action_buttons.php');

echo '
    <div class="card" style="grid-row-end: span 9; display:flex; flex-direction: column;margin-bottom:20px; ">
            <a href="'.$editLink.'">
                <figure class="card-media"><img src="'.$ymedia->getMediaManagerImageUrl().'" style="display: block; max-width: 100%" /></figure>
            </a>
            <header class="card-header">
                <p class="kicker">'.rex_escape($ymedia->getValue('create_datetime')).'</p>
            </header>
            <div class="card-content">
                <div class="content">'.rex_escape($ymedia->getTitle()).'</div>
            </div>
            <footer class="card-action" style="margin-top: auto; padding-top: 20px;">
                <div>'.$buttons.'</div>
            </footer>
    </div>';
