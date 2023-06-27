<?php

/**
 * @var rex_yform_value_media $this
 * @psalm-scope-this rex_yform_value_media
 */

$filename = $filename ?? '';
$error_messages = $error_messages ?? [];

$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class = $this->getElement('required') ? 'form-is-required ' : '';

$class_group = trim('form-group  ' . $class . $this->getWarningClass());
$class_control = trim('form-control');

?>
<div class="<?php echo $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <div class="input-group">
        <input type="text" id="<?php echo $this->getFieldId() ?>" name="<?php echo $this->getFieldName() ?>" value="<?php echo rex_escape($this->getValue()); ?>" />
        <input class="<?php echo $class_control ?>" id="<?php echo $this->getFieldId('file') ?>" type="file" accept="<?php echo $this->getElement('types') ?>" name="<?php echo $this->getFieldName('file') ?>" />
        <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="const file = document.getElementById('<?= $this->getFieldId() ?>'); file.value = '';">&times;</button></span>
    </div>
    <?php echo $notice ?>
</div>

<?php
    if ('' != $this->getValue()) {
        $label = htmlspecialchars($this->getValue());
        echo '
            <div class="checkbox" id="' . $this->getHTMLId('checkbox') . '">
                <label>
                    <input type="checkbox" data-media_value="'.rex_escape($this->getValue()).'" id="' .  $this->getFieldId('delete') . '" value="1" onchange="alert(this.checked); if(this.checked) { document.querySelector(\'#'.$this->getFieldId().'\').value = \'\';alert(1); } else { document.querySelector(\'#'.$this->getFieldId().'\').value = this.dataset.media_value;alert(0); };"/>
                    ' . ($error_messages['delete_file'] ?? 'delete-file-msg') . ' "' . $label . '"
                </label>
            </div>';
    }
?>
