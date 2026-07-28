<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (EventbookingHelper::isJoomla4())
{
	$inputClasses = 'input-small form-control';
}
else
{
	$inputClasses = 'input-mini form-control';
}

Factory::getApplication()->getDocument()->addScriptOptions('groupRateInputClasses', $inputClasses);
?>
<fieldset class="form-horizontal options-form eb-event-group-rates">
	<legend class="adminform"><?php echo Text::_('EB_GROUP_REGISTRATION_RATES'); ?></legend>
	<table class="adminlist" id="price_list" width="100%">
		<tr>
			<th width="50%" class="eb-left-align">
				<?php echo Text::_('EB_REGISTRANT_NUMBER'); ?>
			</th>
			<th class="eb-left-align">
				<?php echo Text::_('EB_RATE'); ?>
			</th>
		</tr>
		<?php
		$n = max(count($this->prices), 3);

		for ($i = 0; $i < $n; $i++)
		{
			if (isset($this->prices[$i]))
			{
				$price            = $this->prices[$i];
				$registrantNumber = $price->registrant_number;
				$price            = $price->price;
			}
			else
			{
				$registrantNumber = null;
				$price            = null;
			}
		?>
			<tr>
				<td class="eb-left-align">
					<input type="text" class="<?php echo $inputClasses; ?>" name="registrant_number[]" size="10" value="<?php echo $registrantNumber; ?>"/>
				</td>
				<td class="eb-left-align">
					<input type="text" class="<?php echo $inputClasses; ?>" name="price[]" size="10" value="<?php echo $price; ?>"/>
				</td>
			</tr>
		<?php
		}
		?>
		<tr>
			<td colspan="3">
				<input type="button" class="btn btn-secondary" id="addGroupRateButton" value="<?php echo Text::_('EB_ADD'); ?>" />&nbsp;
				<input type="button" class="btn btn-secondary" id="removeGroupRateButton" value="<?php echo Text::_('EB_REMOVE'); ?>" />
			</td>
		</tr>
	</table>
</fieldset>
