<?php

/**
 * yform.
 *
 * @author jan.kristinus[at]redaxo[dot]org Jan Kristinus
 * @author <a href="http://www.yakamara.de">www.yakamara.de</a>
 */

class rex_yform_value_media extends rex_yform_value_abstract
{
    /** @var array|null */
    private $media_file;
    /** @var string|null */
    private $media;
    /** @var array|null */
    private $media_configuration;

    public function enterObject()
    {
        // TODO:
        // Multiupload ermöglichen - Allgemeine Werte werden entsprechend dupliziert

        // Dateien werden ersetzt - originalname kann beibehalten werden

        // Extensions allow und dissallow festlegbar

        // nichts über session

        // ZIP Analyse - wegen extensions

        // EP um z.B. Virenscanner einzuschleusen

        // Upload via PHP ermöglichen

        // Download in List und per PHP einbauen

        // Werte der Datei speichern

        // size, originalname

        $config = $this->getElement('config');
        if (!is_callable($config)) {
            $config = (array) json_decode($config, true);
        } else {
            $config = (array) call_user_func($config);
        }

        $this->media_configuration = self::media_getConfiguration($config);

        if (!isset($this->media_configuration['path'])) {
            throw new Exception('YForm field:media - Path in Configuration is missing');
        }

        $paths = explode(DIRECTORY_SEPARATOR, $this->media_configuration['path']);

        switch ($paths[0]) {
            case '[frontend]':
                $paths[0] = rex_path::frontend();
                break;
            case '[data]':
                $paths[0] = rex_path::data();
                break;
        }

        $this->media_configuration['path'] = implode(DIRECTORY_SEPARATOR, $paths);

        if (DIRECTORY_SEPARATOR != substr($this->media_configuration['path'], -1)) {
            $this->media_configuration['path'] .= DIRECTORY_SEPARATOR;
        }

        if (!is_dir($this->media_configuration['path'])) {
            throw new Exception('YForm field:media - Path `'.$this->media_configuration['path'].'` not found');
        }

        if (!is_string($this->getValue())) {
            $this->setValue('');
        }

        if ('' != $this->getValue()) {
            $filenameNormalized = rex_string::normalize($this->getValue(), '_', '.-@');
            if (file_exists($this->media_configuration['path'].'/'.$filenameNormalized)) {
                $this->media = $filenameNormalized;
            }
        }

        if (!$this->media) {
            $this->setValue('');
        }

        $errors = [];
        $normalizedFILES = self::media_getNormalizedFILES();

        if ($this->isEditable()) {
            $fileFieldName = $this->getFieldName('file');

            // FILE UPLOAD via Formukaar -> _FILES
            if (isset($normalizedFILES[$fileFieldName]) && '' != $normalizedFILES[$fileFieldName]['name']) {
                $file = $normalizedFILES[$fileFieldName];

                // upload errors
                if (UPLOAD_ERR_OK !== $file['error']) {
                    // copied from https://www.php.net/manual/de/features.file-upload.errors.php
                    switch ($file['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            $system_message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $system_message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $system_message = 'The uploaded file was only partially uploaded';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $system_message = 'No file was uploaded';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $system_message = 'Missing a temporary folder';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $system_message = 'Failed to write file to disk';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $system_message = 'File upload stopped by extension';
                            break;
                        default:
                            $system_message = 'Unknown upload error';
                            break;
                    }
                    if ($this->params['debug']) {
                        dump($system_message);
                    }
                    $errors[] = $this->media_configuration['messages']['system_error'];
                    unset($file);
                }

                // extensions
                if (isset($file['name'])) {
                    $error_extensions = self::media_checkExtensions($file, $this->media_configuration);
                    if (0 < count($error_extensions)) {
                        $errors = $errors + $error_extensions;
                        unset($file);
                    }
                }

                // callback.
                if (isset($file['name'])) {
                    $errors_callbacks = [];
                    foreach ($this->media_configuration['callback'] as $callback) {
                        $errors_callback = call_user_func(
                            $callback,
                            [
                                'file' => $file,
                                'configuration' => $this->media_configuration,
                            ]
                        );
                        if (0 < count($errors_callback)) {
                            $errors_callbacks = $errors_callbacks + $errors_callback;
                        }
                    }
                    if (0 < count($errors_callbacks)) {
                        $errors = $errors + $errors_callbacks;
                        unset($file);
                    }
                }

                // sizes
                if (isset($file)) {
                    if ('' != $this->getElement('sizes') && $file['size'] > $this->media_configuration['sizes']['max']) {
                        $errors[] = $this->media_configuration['messages']['max_error'];
                        unset($file);
                    } elseif ('' != $this->getElement('sizes') && $file['size'] < $this->media_configuration['sizes']['min']) {
                        $errors[] = $this->media_configuration['messages']['min_error'];
                        unset($file);
                    }
                }

                // file is ok
                if (isset($file)) {
                    $this->media_file = $file;
                }
            }
            // TODO: FILE UPLOAD via URL
            // TODO: File Upload via PHP und Dateizuweisung (local, Internet URL)
        }

        if ($this->params['send'] && 1 == $this->getElement('required') && !$this->media && !$this->media_file) {
            $errors[] = $this->media_configuration['messages']['empty_error'];
        }

        if ($this->params['send'] && count($errors) > 0) {
            $this->params['warning'][$this->getId()] = $this->params['error_class'];
            $this->params['warning_messages'][$this->getId()] = implode(', ', $errors);
        }

        if ($this->needsOutput() && $this->isViewable()) {
            if ($this->isEditable()) {
                $templates = ['value.media.tpl.php', 'value.text.tpl.php'];
            } else {
                $templates = ['value.media-view.tpl.php', 'value.view.tpl.php'];
            }
            $this->params['form_output'][$this->getId()] = $this->parse($templates, [
                'value' => $this->getValue(),
                'file' => $this->media_file,
                'error_messages' => $this->media_configuration['messages'],
            ]);
        }

        return $this;
    }

    public function preAction(): void
    {
        if ($this->media && 1 == $this->params['this']->getFieldValue($this->getName(), [$this->getId(), 'delete'])) {
            rex_file::delete($this->media_configuration['path'].$this->media);
            unset($this->media);
            $this->setValue('');
        }

        if ($this->media_file) {
            $this->media_file['real_name'] = self::media_findPossibleFilename($this->media_file['name'], $this->media_configuration['path']);

            if ($this->media) {
                rex_file::delete($this->media_configuration['path'].$this->media);
                unset($this->media);
                $this->setValue('');
            }
            $this->setValue($this->media_file['real_name']);
            $this->media = $this->media_file['real_name'];

            $srcFile = $this->media_file['tmp_name'];
            $dstFile = $this->media_configuration['path'].$this->media_file['real_name'];
            if (!rex_file::move($srcFile, $dstFile)) {
                throw new rex_api_exception(rex_i18n::msg('pool_file_movefailed'));
            }
            @chmod($dstFile, rex::getFilePerm());
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        $this->params['value_pool']['email'][$this->getName().'_folder'] = $this->media_configuration['path'];
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }

        if ('' != $this->getValue()) {
            $this->params['value_pool']['files'][$this->getName()] = [$this->getValue(), $this->media_configuration['path'].$this->getValue()];
        }

        parent::preAction();
    }

    public static function media_findPossibleFilename(string $filename, string $path): string
    {
        // ----- neuer filename und extension holen
        $newMediaName = rex_string::normalize($filename, '_', '.-@');

        if ('.' === $newMediaName[0]) {
            $newMediaName[0] = '_';
        }

        if ($pos = strrpos($newMediaName, '.')) {
            $newMediaBaseName = substr($newMediaName, 0, strlen($newMediaName) - (strlen($newMediaName) - $pos));
            $newMediaExtension = substr($newMediaName, $pos, strlen($newMediaName) - $pos);
        } else {
            $newMediaBaseName = $newMediaName;
            $newMediaExtension = '';
        }

        // ---- ext checken - alle scriptendungen rausfiltern
        // TODO
        // if (!self::isAllowedExtension($newMediaName)) {
        //     // make sure we dont add a 2nd file-extension to the file,
        //     // because some webspaces execute files like file.php.txt as a php script
        //     $newMediaBaseName .= str_replace('.', '_', $newMediaExtension);
        //     $newMediaExtension = '.txt';
        // }

        $newMediaName = $newMediaBaseName . $newMediaExtension;

        $cnt = 0;
        while (is_file($path.$newMediaName)) {
            ++$cnt;
            $newMediaName = $newMediaBaseName . '_' . $cnt . $newMediaExtension;
        }

        return $newMediaName;
    }

    public static function media_getConfiguration(array $configuration = []): array
    {
        // TODO
        // es muss eine korrekture Configuraziom rauskommen + initial eine bereinigte
        // Backend translate, FE nicht

        if (!isset($configuration['sizes']) || !is_array($configuration['sizes'])) {
            $configuration['sizes'] = [];
        }

        if (!isset($configuration['sizes']['min']) || !is_int($configuration['sizes']['min'])) {
            $configuration['sizes']['min'] = 0;
        }
        if (!isset($configuration['sizes']['max']) || !is_int($configuration['sizes']['max'])) {
            $configuration['sizes']['max'] = 50 * (1024 * 1024); // Megabytes;
        }

        if (!isset($configuration['allowed_extensions']) || !is_array($configuration['allowed_extensions'])) {
            $configuration['allowed_extensions'] = ['*']; // array an erlaubten EXTexplode(',', str_replace('.', '', ));
        }

        if (!isset($configuration['disallowed_extensions']) || !is_array($configuration['disallowed_extensions'])) {
            $configuration['disallowed_extensions'] = ['exe'];
        }

        if (!isset($configuration['check']) || !is_array($configuration['check'])) {
            $configuration['check'] = []; // "multiple_extensions","zip_archive"
        }

        if (!isset($configuration['callback']) || !is_array($configuration['callback'])) {
            $configuration['callback'] = [];
        }

        if (!isset($configuration['messages']) || !is_array($configuration['messages'])) {
            $configuration['messages'] = [];
        }

        foreach([
            'min_error',
            'max_error',
            'type_error',
            'empty_error',
            'system_error',
            'type_multiple_error',
            'zip-type_error',
            'type_zip_error',
            'delete_file'
        ] as $type) {
            if (!isset($configuration['messages'][$type]) || !is_string($configuration['messages'][$type])) {
                $configuration['messages'][$type] = rex_i18n::translate($type.'-msg');
            }
        }

        if (!isset($configuration['messages']['extension_zip_type_error']) || !is_string($configuration['messages']['extension_zip_type_error'])) {
            $configuration['messages']['extension_zip_type_error'] = rex_i18n::translate('extension_zip_type_error-msg {0}');
        }

        return $configuration;
    }

    /**
     * @return string[]
     */
    public static function media_getNormalizedFILES(): array
    {
        $recursiveKeyFromArray = static function ($items, $keyName, $elements, $k) use (&$recursiveKeyFromArray) {
            if (!is_array($items)) {
                $elements[$keyName][$k] = $items;
            } else {
                foreach ($items as $i_k => $i_v) {
                    $elements = $recursiveKeyFromArray($i_v, $keyName.'['.$i_k.']', $elements, $k);
                }
            }
            return $elements;
        };

        $_FILES_normalized = [];
        foreach ($_FILES as $key => $file) {
            if (isset($file['name']) && is_array($file['name'])) {
                foreach (['name', 'full_path', 'type', 'tmp_name', 'error', 'size'] as $fileAttributek) {
                    foreach ($file[$fileAttributek] ?? [] as $k => $e) {
                        $_FILES_normalized = $recursiveKeyFromArray($e, $key.'['.$k.']', $_FILES_normalized, $fileAttributek);
                    }
                }
            } else {
                $_FILES_normalized[$key] = $file;
            }
        }
        return $_FILES_normalized;
    }

    public static function media_checkExtensions(array $file, array $configuration): array
    {
        $Filename = $file['name'];

        $errors = [];
        $ext = mb_strtolower(pathinfo($Filename, PATHINFO_EXTENSION));
        $configuration['allowed_extensions'] = array_map(static function ($a) {
            return mb_strtolower($a);
        }, $configuration['allowed_extensions']);

        if (
            (!in_array('*', $configuration['allowed_extensions'])) &&
            (!in_array($ext, $configuration['allowed_extensions']))
        ) {
            $errors[] = $configuration['messages']['type_error'] ?? 'extension-type-error';
        }

        if (
            isset($configuration['check']) &&
            in_array('multiple_extensions', $configuration['check']) &&
            0 < count(array_intersect(explode('.', $Filename), $configuration['disallowed_extensions']))
        ) {
            $errors[] = $configuration['messages']['type_multiple_error'] ?? 'multiple-extension-type-error: '.implode(', ', array_intersect(explode('.', $Filename), $configuration['disallowed_extensions']));
        }

        if (
            isset($configuration['check']) &&
            in_array('zip_archive', $configuration['check'])
        ) {
            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name'])) {
                $zip = new ZipArchive();
                if ('zip' == $ext) {
                    if ($zip->open($file['tmp_name'])) {
                        $zip_error_files = [];

                        for ($i = 0; $i < $zip->numFiles; ++$i) {
                            $iZipFileName = $zip->getNameIndex($i);
                            $i_ext = mb_strtolower(pathinfo($iZipFileName, PATHINFO_EXTENSION));

                            if (
                                (!in_array('*', $configuration['allowed_extensions'])) &&
                                (!in_array($i_ext, $configuration['allowed_extensions']))
                            ) {
                                if (1 < count($errors)) {
                                    $errors[] = ' ... ';
                                    break;
                                }

                                $zip_error_files[] = $iZipFileName;
                            }
                        }

                        if (3 < count($zip_error_files)) {
                            $amount_files = count($zip_error_files);
                            $zip_error_files = array_chunk($zip_error_files, 3)[0];
                            $zip_error_files[] = ' ... ['.($amount_files - count($zip_error_files)).'] ';
                        }

                        if (0 < count($zip_error_files)) {
                            $temp_msg = $configuration['messages']['zip-type_error'] ?? 'extension-zip-type-error: {0}';
                            $errors[] = str_replace('{0}', implode(', ', $zip_error_files), $temp_msg);
                        }
                    }
                }
            } else {
                $errors[] = $configuration['messages']['type_zip_error'] ?? 'zip-type-error';
            }
        }

        return $errors;
    }

    public function getDescription(): string
    {
        return 'media|name|label|';
    }

    public function getDefinitions(): array
    {
        return [
            'type' => 'value',
            'name' => 'media',
            'values' => [
                'name' => ['type' => 'name',      'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
                'config' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_media_config'), 'notice' => rex_i18n::msg('yform_values_media_config_notice')],
                'required' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_media_required')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('yform_values_media_description'),
            'db_type' => ['text'],
            'multi_edit' => false,
        ];
    }

    public static function getSearchField($params)
    {
        rex_yform_value_text::getSearchField($params);
    }

    public static function getSearchFilter($params)
    {
        return rex_yform_value_text::getSearchFilter($params);
    }

    public static function getListValue($params)
    {
        /** @var rex_yform_list $list */
        $list = $params['list'];
        // dump($params['list']->getCurrentItem());
        return rex_escape($params['subject']);
    }
}
