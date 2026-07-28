<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * Layout variables
 *
 * @var EventbookingTableEvent $row
 * @var \Joomla\CMS\Form\Form  $form
 */


if ($row->id)
{
	$params = new Registry($row->params);
	$params->def('update_data_from_main_event', $this->params->get('default_update_data_from_main_event_checkbox_status', 1));

	if ($params->get('update_data_from_main_event'))
	{
		$checked = ' checked="checked"';
	}
	else
	{
		$checked = '';
	}
	?>
	<div class="row-fluid">
		<label class="checkbox">
			<input type="checkbox" name="update_data_from_main_event" value="1"<?php echo $checked; ?>/>
			<strong><?php echo Text::_('EB_UPDATE_DATE_FROM_MAIN_EVENT'); ?></strong>
		</label>
	</div>
	<?php
}
?>
<div class="row-fluid eb-additional-dates-container">
	<?php
	foreach ($form->getFieldset() as $field)
	{
		echo $field->input;
	}
	?>
</div>


