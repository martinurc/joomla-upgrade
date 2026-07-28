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
 * @var EventbookingTableRegistrant $rowMember
 * @var EventbookingTableEvent      $rowEvent
 * @var RADConfig                   $config
 * @var stdClass                    $rowLocation
 * @var RADForm                     $memberForm
 */
?>
<table class="os_table" width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td class="title_cell">
			<?php echo  Text::_('EB_EVENT') ?>
		</td>
		<td class="field_cell">
			<?php echo $rowEvent->title ; ?>
		</td>
	</tr>
	<?php
	if ($config->show_event_date)
	{
	?>
		<tr>
			<td class="title_cell">
				<?php echo  Text::_('EB_EVENT_DATE') ?>
			</td>
			<td class="field_cell">
				<?php
				if ($rowEvent->event_date == EB_TBC_DATE)
				{
					echo Text::_('EB_TBC');
				}
				else
				{
					if (strpos($rowEvent->event_date, '00:00:00') !== false)
					{
						$dateFormat = $config->date_format;
					}
					else
					{
						$dateFormat = $config->event_date_format;
					}

					echo HTMLHelper::_('date', $rowEvent->event_date, $dateFormat, null) ;
				}
				?>
			</td>
		</tr>
	<?php
		if ((int) $rowEvent->event_end_date)
		{
			if (strpos($rowEvent->event_end_date, '00:00:00') !== false)
			{
				$dateFormat = $config->date_format;
			}
			else
			{
				$dateFormat = $config->event_date_format;
			}
		?>
			<tr>
				<td class="title_cell">
					<?php echo  Text::_('EB_EVENT_END_DATE') ?>
				</td>
				<td class="field_cell">
					<?php echo HTMLHelper::_('date', $rowEvent->event_end_date, $dateFormat, null); ?>
				</td>
			</tr>
		<?php
		}
	}

	if ($config->show_event_location_in_email && $rowLocation)
	{
		$location = $rowLocation ;
		$locationInformation = [];

		if ($location->address)
		{
			$locationInformation[] = $location->address;
		}
		?>
		<tr>
			<td class="title_cell">
				<?php echo  Text::_('EB_LOCATION') ?>
			</td>
			<td class="field_cell">
				<?php echo $location->name . ' (' . implode(', ', $locationInformation) . ')' ; ?>
			</td>
		</tr>
	<?php
	}

	$ticketType =  null;

	if ($rowEvent->has_multiple_ticket_types)
	{
		$ticketType = EventbookingHelperRegistration::getGroupMemberTicketTypeData($rowMember->id);

		if ($ticketType)
		{
			$memberForm->handleFieldsDependOnTicketTypes([$ticketType->id]);
		}
	}

	if ($ticketType)
	{
	?>
		<tr>
			<td class="title_cell">
				<?php echo Text::_('EB_TICKET_TYPE'); ?>
			</td>
			<td class="field_cell">
				<?php echo Text::_($ticketType->title); ?>
			</td>
		</tr>
	<?php
	}

	$fields = $memberForm->getFields();

	foreach ($fields as $field)
	{
		if ($field->hideOnDisplay || $field->row->hide_on_email)
		{
			continue;
		}

		echo $field->getOutput(false);
	}
	?>
</table>
