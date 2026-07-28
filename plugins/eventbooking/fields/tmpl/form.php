<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

/**
 * Layout variables
 *
 * @var EventbookingTableEvent $row
 * @var array                  $rowFields
 * @var array                  $selectedFieldIds
 */

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$spanClass       = $bootstrapHelper->getClassMapping('span3');
$numberColumns   = 4;
$count           = 0;
$numberFields    = count($rowFields);
?>
<div class="<?php echo $rowFluid; ?>">
	<?php
	foreach ($rowFields as $rowField)
	{
		$count++;
		$attributes = [];

		if ($rowField->event_id == -1)
		{
			$attributes[] = 'disabled';
			$attributes[] = 'checked';
		}
		else
		{
			if (in_array($rowField->id, $selectedFieldIds))
			{
				$attributes[] = 'checked';
			}
			elseif (!empty($rowField->eventIds) && $rowField->eventIds[0] < 0)
			{
				$negativeEventId = -1 * $row->id;

				if ($row->id == 0 || !in_array($negativeEventId, $rowField->eventIds))
				{
					$attributes[] = 'disabled';
					$attributes[] = 'checked';
				}
			}
		}
		?>
		<div class="<?php echo $spanClass; ?>">
			<label class="checkbox">
				<input type="checkbox" class="form-check-input" value="<?php echo $rowField->id ?>"
				       name="registration_form_fields[]"<?php if (count($attributes)) {echo ' ' . implode(' ', $attributes);} ?>>
				<?php echo '[' . $rowField->id . '] - ' . $rowField->title; ?>
			</label>
		</div>
		<?php
		if ($count % $numberColumns == 0 && $count < $numberFields)
		{
		?>
		</div>
		<div class="clearfix row-fluid">
		<?php
		}
	}
?>
</div>

