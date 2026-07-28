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
use Joomla\Registry\Registry;

$format = 'Y-m-d';

EventbookingHelper::normalizeNullDateTimeData($this->item, ['recurring_end_date']);

if (empty($this->item->interval))
{
	$this->item->interval = 1;
}

$showOnData = [
	'fieldtype' => ['List', 'Checkboxes', 'Radio'],
];
?>
<fieldset class="form-horizontal options-form eb-event-recurring-settings">
	<legend class="adminform"><?php echo Text::_('EB_RECURRING_SETTINGS'); ?></legend>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_REPEAT_TYPE'); ?>
		</div>
		<div class="controls">
			<?php echo $this->lists['recurring_type']; ?>
		</div>
	</div>
	<?php
	$showOnData = [
		'recurring_type' => ['1', '2', '3', '4'],
	];
	?>
	<div class="control-group" data-showon='<?php echo EventbookingHelperHtml::renderShowOn($showOnData); ?>'>
		<div class="control-label">
			<?php echo Text::_('EB_INTERVAL'); ?>
		</div>
		<div class="controls">
			<input type="number" name="recurring_frequency" id="recurring_frequency" size="5" class="input-mini form-control" value="<?php echo $this->item->recurring_frequency; ?>"/>
		</div>
	</div>
	<div class="control-group" data-showon='<?php echo EventbookingHelperHtml::renderShowOn(['recurring_type' => '2']); ?>'>
		<div class="control-label">
			<strong><?php echo Text::_('EB_ON'); ?></strong>
		</div>
		<div class="controls">
			<?php
			if (strlen($this->item->weekdays))
			{
				$weekDays   = explode(',', $this->item->weekdays);
			}
			else
			{
				$weekDays = [];
			}

			$daysOfWeek = [0 => 'SUN', 1 => 'MON', 2 => 'TUE', 3 => 'WED', 4 => 'THU', 5 => 'FRI', 6 => 'SAT'];

			foreach ($daysOfWeek as $key => $value)
			{
			?>
				<input type="checkbox" class="clearfloat form-check-input"
					   value="<?php echo $key; ?>"
					   name="weekdays[]" <?php if (in_array($key, $weekDays)) echo ' checked'; ?> /> <?php echo Text::_($value); ?>&nbsp;&nbsp;
			<?php
			}
			?>
		</div>
	</div>
	<div class="control-group" data-showon='<?php echo EventbookingHelperHtml::renderShowOn(['recurring_type' => '3']); ?>'>
		<div class="control-label">
			<?php echo Text::_('EB_ON'); ?>
		</div>
		<div class="controls">
			<input type="text" name="monthdays" id="monthdays"
				   class="input-medium form-control" size="10"
				   value="<?php echo $this->item->monthdays; ?>" /> days in month (For Example 10,15,19)
		</div>
	</div>
	<div class="control-group" data-showon='<?php echo EventbookingHelperHtml::renderShowOn(['recurring_type' => '4']); ?>'>
		<div class="control-label">
			<?php echo Text::_('EB_ON'); ?>
		</div>
		<div class="controls">
			<?php
			$params     = new Registry($this->item->params);
			$options    = [];
			$options[]  = HTMLHelper::_('select.option', 'first', Text::_('EB_FIRST'));
			$options[]  = HTMLHelper::_('select.option', 'second', Text::_('EB_SECOND'));
			$options[]  = HTMLHelper::_('select.option', 'third', Text::_('EB_THIRD'));
			$options[]  = HTMLHelper::_('select.option', 'fourth', Text::_('EB_FOURTH'));
			$options[]  = HTMLHelper::_('select.option', 'fifth', Text::_('EB_FIFTH'));
			$options[]  = HTMLHelper::_('select.option', 'last', Text::_('EB_LAST'));

			$daysOfWeek = [
				'Sun' => Text::_('SUNDAY'),
				'Mon' => Text::_('MONDAY'),
				'Tue' => Text::_('TUESDAY'),
				'Wed' => Text::_('WEDNESDAY'),
				'Thu' => Text::_('THURSDAY'),
				'Fri' => Text::_('FRIDAY'),
				'Sat' => Text::_('SATURDAY'),
			];

			echo HTMLHelper::_('select.genericlist', $options, 'week_in_month', ' class="form-select input-medium" ', 'value', 'text', $params->get('week_in_month', 'first'));
			echo HTMLHelper::_('select.genericlist', $daysOfWeek, 'day_of_week', ' class="form-select input-medium" ', 'value', 'text', $params->get('day_of_week', 'Sun'));
			?>
			of the month
		</div>
	</div>
	<div class="control-group" data-showon='<?php echo EventbookingHelperHtml::renderShowOn($showOnData); ?>'>
		<div class="control-label">
			<?php echo Text::_('EB_REPEAT_UNTIL'); ?>
		</div>
		<div class="controls">
			<?php echo HTMLHelper::_('calendar', $this->item->recurring_end_date, 'recurring_end_date', 'recurring_end_date', $this->datePickerFormat, ['class' => 'input-small form-control']); ?>
		</div>
	</div>
	<div class="control-group" data-showon='<?php echo EventbookingHelperHtml::renderShowOn($showOnData); ?>'>
		<div class="control-label">
			<?php echo Text::_('EB_REPEAT_COUNT'); ?>
		</div>
		<div class="controls">
			<input type="number" name="recurring_occurrencies" size="5" class="input-small form-control" value="<?php echo $this->item->recurring_occurrencies; ?>" />
		</div>
	</div>
	<?php
	if ($this->item->id)
	{
	?>
		<div class="control-group" data-showon='<?php echo EventbookingHelperHtml::renderShowOn($showOnData); ?>'>
			<div class="control-label">
				<?php echo Text::_('EB_UPDATE_CHILD_EVENT'); ?>
			</div>
			<div class="controls">
				<input type="checkbox" name="update_children_event" value="1"
				class="form-control form-check-input" />
			</div>
		</div>
	<?php
	}
	?>
</fieldset>
