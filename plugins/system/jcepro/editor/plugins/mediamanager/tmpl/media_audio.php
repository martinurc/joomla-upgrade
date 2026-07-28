<?php
/**
 * @package     JCE
 * @subpackage  Editor
 *
 * @copyright   Copyright (c) 2009-2024 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Language\Text;

?>
<div class="media_option audio">
    <h4><?php echo Text::_('WF_MEDIAMANAGER_AUDIO_OPTIONS'); ?></h4>

    <div class="uk-form-row uk-flex">
        <label for="audio_autoplay" class="uk-checkbox-label">
            <input type="checkbox" id="audio_autoplay" /><?php echo Text::_('WF_MEDIAMANAGER_LABEL_AUTOPLAY'); ?>
        </label>
        
        <label for="audio_controls" class="uk-checkbox-label">
            <input type="checkbox" id="audio_controls" checked="checked" /><?php echo Text::_('WF_MEDIAMANAGER_LABEL_CONTROLS'); ?>
        </label>
        
        <label for="audio_loop" class="uk-checkbox-label">
            <input type="checkbox" id="audio_loop" /><?php echo Text::_('WF_MEDIAMANAGER_LABEL_LOOP'); ?>
        </label>
        
        <label for="audio_muted" class="uk-checkbox-label">
            <input type="checkbox" id="audio_muted" /><?php echo Text::_('WF_MEDIAMANAGER_VIDEO_MUTE'); ?>
        </label>
    </div>

    <div class="uk-form-row uk-flex">
        <label for="audio_preload" class="uk-form-label uk-width-1-5"><?php echo Text::_('WF_MEDIAMANAGER_LABEL_PRELOAD'); ?></label>
        <div class="uk-form-controls uk-width-1-5">
            <select id="audio_preload">
                <option value=""><?php echo Text::_('WF_OPTION_AUTO'); ?></option>
                <option value="none"><?php echo Text::_('JNONE'); ?></option>
                <option value="metadata"><?php echo Text::_('WF_MEDIAMANAGER_LABEL_METADATA'); ?></option>
            </select>
        </div>
    </div>

    <div class="uk-form-row uk-flex">
        <label for="audio_source" class="uk-form-label uk-width-1-5"><?php echo Text::_('WF_MEDIAMANAGER_LABEL_SOURCE'); ?></label>
        <div class="uk-form-controls uk-width-4-5">
            <input type="text" name="audio_source[]" class="active" onclick="MediaManagerDialog.setSourceFocus(this);" />
        </div>
    </div>
    <div class="uk-form-row uk-flex">
        <label for="audio_source" class="uk-form-label uk-width-1-5"><?php echo Text::_('WF_MEDIAMANAGER_LABEL_SOURCE'); ?></label>
        <div class="uk-form-controls uk-width-4-5">
            <input type="text" name="audio_source[]" onclick="MediaManagerDialog.setSourceFocus(this);" />
        </div>
    </div>
    <div class="uk-form-row uk-flex">
        <label for="audio_attributes" class="uk-form-label uk-width-1-5"><?php echo Text::_('WF_LABEL_ATTRIBUTES'); ?></label>
        <div class="uk-form-controls uk-width-1-1 uk-width-small-4-5 uk-flex-wrap" id="audio_attributes">
            <div class="uk-form-row uk-repeatable uk-width-1-1">
                <div class="uk-form-controls uk-grid uk-grid-small uk-width-9-10">
                    <label class="uk-form-label uk-width-1-10"><?php echo Text::_('WF_LABEL_NAME'); ?></label>
                    <div class="uk-form-controls uk-width-4-10">
                        <input type="text" name="audio_attributes_name[]" />
                    </div>
                    <label class="uk-form-label uk-width-1-10"><?php echo Text::_('WF_LABEL_VALUE'); ?></label>
                    <div class="uk-form-controls uk-width-4-10">
                        <input type="text" name="audio_attributes_value[]" />
                    </div>
                </div>
                <div class="uk-form-controls uk-width-1-10 uk-margin-small-left">
                    <button class="uk-button uk-button-link uk-repeatable-create" aria-label="<?php echo Text::_('WF_LABEL_ADD'); ?>" title="<?php echo Text::_('WF_LABEL_ADD'); ?>"><i class="uk-icon-plus"></i></button>
                    <button class="uk-button uk-button-link uk-repeatable-delete" aria-label="<?php echo Text::_('WF_LABEL_REMOVE'); ?>" title="<?php echo Text::_('WF_LABEL_REMOVE'); ?>"><i class="uk-icon-trash"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>