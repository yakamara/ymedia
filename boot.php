<?php

/**
 * Media Addon.
 *
 * @author jan[dot]kristinus[at]redaxo[dot]de Jan Kristinus
 */

$addon = rex_addon::get('ymedia');

rex_yform_manager_dataset::setModelClass('rex_ymedia', rex_ymedia::class);
rex_yform_manager_dataset::setModelClass('rex_ymedia_tag', rex_ymedia_tag::class);

rex_yform_manager_table::setTableLayout('rex_ymedia', 'ymedia/page/layout.php');

rex_extension::register('PACKAGES_INCLUDED', function (rex_extension_point $ep) {
    rex_yform::addTemplatePath($this->getPath('ytemplates'));
});

rex_extension::register(['MEDIA_IS_PERMITTED'], static function (rex_extension_point $ep) {

    // TODO
    echo "MEDIA_IS_PERMITTED in Ymedia/boot.php";
    dump($ep);

    $ycom_ignore = $ep->getParam('ycom_ignore');
    $subject = $ep->getSubject();
    if ($ycom_ignore) {
        return $subject;
    }
    if (!$subject) {
        return false;
    }
    $rex_media = $ep->getParam('element');
    return \rex_ycom_media_auth::checkFrontendPerm($rex_media);
});

rex_extension::register(['MEDIA_MANAGER_BEFORE_SEND'], static function (rex_extension_point $ep) {
    /** @var rex_media_manager $mm */
    $mm = $ep->getSubject();
    $originalMediaPath = dirname($mm->getMedia()->getSourcePath());
    if (trim(rex_ymedia::getPath(), '/') == trim($originalMediaPath, '/')) {
        /** @var rex_ymedia|null $YMedia */
        $YMedia = rex_ymedia::getByFilename($mm->getMedia()->getMediaFilename());
        if ($YMedia && $YMedia->checkPerm()) {
            $ep->setParam('ycom_ignore', true);
        }
    }
}, rex_extension::EARLY);

// TODO:
// Abfangen ob ein Medieum ersetzt wird oder ähnliches .. dann Cache vom Media Mamager löschen
//
// rex_extension::register('MEDIA_UPDATED', [rex_media_manager::class, 'mediaUpdated']);
// rex_extension::register('MEDIA_DELETED', [rex_media_manager::class, 'mediaUpdated']);
// rex_extension::register('MEDIA_IS_IN_USE', [rex_media_manager::class, 'mediaIsInUse']);
