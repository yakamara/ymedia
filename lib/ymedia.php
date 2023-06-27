<?php

class rex_ymedia extends rex_yform_manager_dataset
{
    /**
     * @return null|mixed|rex_yform_manager_dataset|rex_ymedia
     */
    public static function getByFilename(string $filename)
    {
        $filenameNormalized = rex_string::normalize($filename, '_', '.-@');
        return self::query()->where('media', $filename)->findOne();
    }

    public static function getFrontendPath(string $file = ''): string
    {
        return rex_url::frontend('ymedia/'.$file);
    }

    public static function getPath(string $file = ''): string
    {
        return rex_path::frontend('ymedia/'.$file);
    }

    public function getTitle()
    {
        return $this->getValue('title');
    }

    public function getFilename()
    {
        return $this->getValue('media');
    }

    public function getMediaManagerImageUrl()
    {
        return '/index.php?rex_media_type=rex_ymedia_preview&rex_media_file='.$this->getFilename();

        // return rex_url::frontend('ymedia/'.$this->getFilename());
    }

    public static function getMediaConfiguration(): array
    {
        $configuration = [];
        $configuration['path'] = self::getPath();
        return $configuration;
    }

    /**
     * @return bool|string
     */
    public static function mediaIsInUse(string $filename)
    {
        // TODO:

        $sql = rex_sql::factory();

        // FIXME move structure stuff into structure addon
        $values = [];
        for ($i = 1; $i < 21; ++$i) {
            $values[] = 'value' . $i . ' REGEXP ' . $sql->escape('(^|[^[:alnum:]+_-])'.$filename);
        }

        $files = [];
        $filelists = [];
        $escapedFilename = $sql->escape($filename);
        for ($i = 1; $i < 11; ++$i) {
            $files[] = 'media' . $i . ' = ' . $escapedFilename;
            $filelists[] = 'FIND_IN_SET(' . $escapedFilename . ', medialist' . $i . ')';
        }

        $where = '';
        $where .= implode(' OR ', $files) . ' OR ';
        $where .= implode(' OR ', $filelists) . ' OR ';
        $where .= implode(' OR ', $values);
        $query = 'SELECT DISTINCT article_id, clang_id FROM ' . rex::getTablePrefix() . 'article_slice WHERE ' . $where;

        $warning = [];
        $res = $sql->getArray($query);
        if ($sql->getRows() > 0) {
            $warning[0] = rex_i18n::msg('pool_file_in_use_articles') . '<ul>';
            foreach ($res as $artArr) {
                $aid = (int) $artArr['article_id'];
                $clang = (int) $artArr['clang_id'];
                $ooa = rex_article::get($aid, $clang);
                $name = ($ooa) ? $ooa->getName() : '';
                $warning[0] .= '<li><a href="javascript:openPage(\'' . rex_url::backendPage('content', ['article_id' => $aid, 'mode' => 'edit', 'clang' => $clang]) . '\')">' . $name . '</a></li>';
            }
            $warning[0] .= '</ul>';
        }

        // ----- EXTENSION POINT
        $warning = rex_extension::registerPoint(new rex_extension_point('MEDIA_IS_IN_USE', $warning, [
            'filename' => $filename,
        ]));

        if (!empty($warning)) {
            return '<br /><br />' . implode('', $warning);
        }

        return false;
    }

    public function checkPerm()
    {
        // TODO:
        // - admins
        // ....
        return true;
    }

    public static function checkPermByFilename($filename)
    {
        $ymedia = rex_ymedia::getByFilename($filename);
        // TODO: Permission einbauen
        return ($ymedia && $ymedia->checkPerm());
    }
}
