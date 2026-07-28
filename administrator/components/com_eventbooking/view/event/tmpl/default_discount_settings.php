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

EventbookingHelper::normalizeNullDateTimeData($this->item, ['early_bird_discount_date', 'late_fee_date']);
?>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('discount_groups', Text::_('EB_MEMBER_DISCOUNT_GROUPS'), Text::_('EB_MEMBER_DISCOUNT_GROUPS_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<?php echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['discount_groups']); ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('discount_amounts', Text::_('EB_MEMBER_DISCOUNT'), Text::_('EB_MEMBER_DISCOUNT_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<input type="text" name="discount_amounts" id="discount_amounts" class="input-medium form-control d-inline-block" size="5"
			   value="<?php echo $this->item->discount_amounts; ?>" />&nbsp;&nbsp;<?php echo $this->lists['discount_type']; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('early_bird_discount_amount', Text::_('EB_EARLY_BIRD_DISCOUNT'), Text::_('EB_EARLY_BIRD_DISCOUNT_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<input type="number" step="0.01" name="early_bird_discount_amount" id="early_bird_discount_amount" class="input-medium form-control d-inline-block"
			   size="5"
			   value="<?php echo $this->item->early_bird_discount_amount; ?>"/>&nbsp;&nbsp;<?php echo $this->lists['early_bird_discount_type']; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('early_bird_discount_date', Text::_('EB_EARLY_BIRD_DISCOUNT_DATE'), Text::_('EB_EARLY_BIRD_DISCOUNT_DATE_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<?php echo HTMLHelper::_('calendar', $this->item->early_bird_discount_date, 'early_bird_discount_date', 'early_bird_discount_date', $this->datePickerFormat . ' %H:%M', ['class' => 'input-medium', 'showTime' => true]); ?>
	</div>
</div>

<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('late_fee_amount', Text::_('EB_LATE_FEE'), Text::_('EB_LATE_FEE_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<input type="number" step="0.01" name="late_fee_amount" id="late_fee_amount" class="input-medium form-control d-inline-block" size="5"
			   value="<?php echo $this->item->late_fee_amount; ?>"/>&nbsp;&nbsp;<?php echo $this->lists['late_fee_type']; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('late_fee_date', Text::_('EB_LATE_FEE_DATE'), Text::_('EB_LATE_FEE_DATE_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<?php echo HTMLHelper::_('calendar', $this->item->late_fee_date, 'late_fee_date', 'late_fee_date', $this->datePickerFormat . ' %H:%M', ['class' => 'input-medium', 'showTime' => true]); ?>
	</div>
</div>
