<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Layout variables
 *
 * @var RADConfig                $config
 * @var array                    $lists
 * @var Joomla\Registry\Registry $params
 * @var string                   $validFrom
 * @var string                   $validTo
 */
?>
	<div class="control-group">
		<label class="control-label">
			<?php echo Text::_('EB_DISCOUNT'); ?>
		</label>
		<div class="controls">
			<input class="form-control input-small d-inline-block" type="text" name="auto_coupon_discount" id="auto_coupon_discount" size="10"
			       maxlength="250"
			       value="<?php
			       echo $params->get('auto_coupon_discount'); ?>"/>&nbsp;&nbsp;<?php echo $lists['auto_coupon_coupon_type']; ?>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">
			<?php
			echo EventbookingHelperHtml::getFieldLabel(
				'auto_coupon_event_ids',
				Text::_('EB_AUTO_COUPON_EVENT_IDS'),
				Text::_('EB_AUTO_COUPON_EVENT_IDS_EXPLAIN')
			);
			?>
		</label>
		<div class="controls">
			<input class="form-control input-xxlarge" type="text" name="auto_coupon_event_ids" id="auto_coupon_event_ids"
			       maxlength="250"
			       value="<?php
			       echo $params->get('auto_coupon_event_ids'); ?>"/>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">
			<?php echo Text::_('EB_TIMES'); ?>
		</label>
		<div class="controls">
			<input class="form-control input-small" type="text" name="auto_coupon_times" id="auto_coupon_times" size="5"
			       maxlength="250"
			       value="<?php
			       echo $params->get('auto_coupon_times', 1); ?>"/>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">
			<?php echo Text::_('EB_VALID_FROM_DATE'); ?>
		</label>
		<div class="controls">
			<?php echo HTMLHelper::_('calendar', (int) $validFrom ? $validFrom : '', 'auto_coupon_valid_from', 'auto_coupon_valid_from'); ?>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">
			<?php echo Text::_('EB_VALID_TO_DATE'); ?>
		</label>
		<div class="controls">
			<?php echo HTMLHelper::_('calendar', (int) $validTo ? $validTo : '', 'auto_coupon_valid_to', 'auto_coupon_valid_to'); ?>
		</div>
	</div>
<?php

if (!$config->multiple_booking)
{
?>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_APPLY_TO'); ?>
		</div>
		<div class="controls">
			<?php
			echo $lists['auto_coupon_apply_to']; ?>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_ENABLE_FOR'); ?>
		</div>
		<div class="controls">
			<?php echo $lists['auto_coupon_enable_for']; ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_COUPON_MIN_NUMBER_REGISTRANTS'); ?>
		</div>
		<div class="controls">
			<input class="input-small form-control" type="number" name="auto_coupon_min_number_registrants"
			       id="auto_coupon_min_number_registrants" size="5" maxlength="250"
			       value="<?php
			       echo $params->get('auto_coupon_min_number_registrants'); ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_COUPON_MAX_NUMBER_REGISTRANTS'); ?>
		</div>
		<div class="controls">
			<input class="input-small form-control" type="number" name="auto_coupon_max_number_registrants"
			       id="auto_coupon_max_number_registrants" size="5" maxlength="250"
			       value="<?php
			       echo $params->get('auto_coupon_max_number_registrants'); ?>"/>
		</div>
	</div>
<?php
}
