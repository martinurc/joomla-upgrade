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

$hiddenPhoneClass = $this->bootstrapHelper->getClassMapping('hidden-phone');
$cols = 0;

if ($this->state->registrant_type == 3)
{
	$pageHeading = Text::_('EB_WAITING_LIST');
}
else
{
	$pageHeading = Text::_('EB_REGISTRANT_LIST');
}

$pageHeading = str_replace('[EVENT_TITLE]', $this->event->title, $pageHeading);

if (!$this->input->getInt('hmvc_call'))
{
?>
<div id="eb-registrants-list-page" class="eb-container">
	<h1 class="eb_title"><?php echo $pageHeading; ?></h1>
<?php
}

if (count($this->items))
{
	$showNumberRegistrants = false;

	foreach($this->items as $item)
	{
		if ($item->number_registrants > 1)
		{
			$showNumberRegistrants = true;
			break;
		}
	}

	if ($this->config->get('public_registrants_list_show_number_registrants', '') !== '')
	{
		$showNumberRegistrants = (bool) $this->config->get('public_registrants_list_show_number_registrants');
	}

	$showTickets = $this->config->get('public_registrants_list_show_ticket_types') && count($this->tickets);
?>
	<table class="eb-registrants-list-table <?php echo $this->bootstrapHelper->getClassMapping('table table-striped table-bordered table-condensed'); ?> eb-responsive-table">
	<thead>
		<tr>
            <?php
				if ($this->config->get('public_registrants_list_show_num', 1))
				{
					$cols++;
				?>
                    <th width="5" class="<?php echo $hiddenPhoneClass; ?> eb-rl-num-column">
		                <?php echo Text::_('NUM'); ?>
                    </th>
                <?php
				}

				if (in_array('first_name', $this->showOnRegistrantsListFieldNames))
				{
					$cols++;
				?>
					<th class="eb-rl-first-name-column">
						<?php echo Text::_('EB_FIRST_NAME'); ?>
					</th>
				<?php
				}

                if (in_array('last_name', $this->showOnRegistrantsListFieldNames))
				{
					$cols++;
				?>
					<th class="eb-rl-last-name-column">
						<?php echo Text::_('EB_LAST_NAME'); ?>
					</th>
				<?php
				}

				if ($showNumberRegistrants)
				{
					$cols++;
				?>
					<th class="eb-rl-number-registrants-column">
						<?php echo Text::_('EB_REGISTRANTS'); ?>
					</th>
				<?php
				}

				if ($showTickets)
				{
					$cols++;
				?>
					<th class="eb-rl-tickets-column">
						<?php echo Text::_('EB_TICKETS'); ?>
					</th>
				<?php
				}

				if ($this->config->get('public_registrants_list_show_register_date', 1))
				{
					$cols++;
				?>
					<th class="eb-rl-register-date-column">
						<?php echo Text::_('EB_REGISTRATION_DATE'); ?>
					</th>
				<?php
				}

				if ($this->displayCustomField)
				{
					foreach($this->fields as $fieldId)
					{
						$cols++;
					?>
						<th class="eb-rl-field<?php echo $fieldId; ?>-column">
							<?php echo $this->fieldTitles[$fieldId] ; ?>
						</th>
					<?php
					}
				}

				if ($this->config->get('public_registrants_list_show_registration_status', 0))
				{
					$cols++;
				?>
					<th class="eb-rl-registration-status-column">
						<?php echo Text::_('EB_REGISTRATION_STATUS'); ?>
					</th>
				<?php
				}
			?>
		</tr>
	</thead>
		<tfoot>
			<tr>
				<?php
				if ($this->pagination->total > $this->pagination->limit)
				{
				?>
					<td colspan="<?php echo $cols; ?>">
						<div class="pagination"><?php echo $this->pagination->getPagesLinks();?></div>
					</td>
				<?php
				}
				?>
			</tr>
		</tfoot>
	<tbody>
	<?php
	for ($i = 0, $n = count($this->items); $i < $n; $i++)
	{
		$row = $this->items[$i];
		?>
		<tr>
            <?php
			if ($this->config->get('public_registrants_list_show_num', 1))
			{
			?>
                <td class="<?php echo $hiddenPhoneClass; ?>">
		            <?php echo $this->pagination->getRowOffset($i); ?>
                </td>
            <?php
			}

            if (in_array('first_name', $this->showOnRegistrantsListFieldNames))
            {
			?>
	            <td class="tdno<?php echo $i; ?>" data-content="<?php echo Text::_('EB_FIRST_NAME'); ?>">
		            <?php echo $row->first_name ?>
	            </td>
		    <?php
            }

            if (in_array('last_name', $this->showOnRegistrantsListFieldNames))
            {
			?>
	            <td class="tdno<?php echo $i; ?>" data-content="<?php echo Text::_('EB_LAST_NAME'); ?>">
		            <?php echo $row->last_name ; ?>
	            </td>
		    <?php
            }

			if ($showNumberRegistrants)
			{
			?>
				<td class="tdno<?php echo $i; ?>" data-content="<?php echo Text::_('EB_REGISTRANTS'); ?>">
					<?php echo $row->number_registrants ; ?>
				</td>
			<?php
			}

			if ($showTickets)
			{
				$ticketsOutput = [];

				if (!empty($this->tickets[$row->id]))
				{
					$tickets = $this->tickets[$row->id];

					foreach ($this->ticketTypes as $ticketType)
					{
						if (!empty($tickets[$ticketType->id]))
						{
							$ticketsOutput[] = Text::_($ticketType->title) . ': ' . $tickets[$ticketType->id];
						}
					}
				}

				$ticketsOutput = implode('<br />', $ticketsOutput);
			?>
				<td class="tdno<?php echo $i; ?>" data-content="<?php echo Text::_('EB_TICKETS'); ?>">
					<?php echo $ticketsOutput; ?>
				</td>
			<?php
			}

			if ($this->config->get('public_registrants_list_show_register_date', 1))
			{
			?>
				<td class="tdno<?php echo $i; ?>" data-content="<?php echo Text::_('EB_REGISTRATION_DATE'); ?>">
					<?php echo HTMLHelper::_('date', $row->register_date, $this->config->date_format) ; ?>
				</td>
			<?php
			}

			if ($this->displayCustomField)
			{
				foreach($this->fields as $fieldId)
				{
					if (isset($this->fieldValues[$row->id][$fieldId]))
					{
						$fieldValue = $this->fieldValues[$row->id][$fieldId];
					}
					else
					{
						$fieldValue = '';
					}
				?>
					<td class="tdno<?php echo $i; ?>" data-content="<?php echo $this->fieldTitles[$fieldId]; ?>">
						<?php echo $fieldValue ?>
					</td>
				<?php
				}
			}

			if ($this->config->get('public_registrants_list_show_registration_status', 0))
			{
			?>
				<td class="tdno<?php echo $i; ?>" data-content="<?php echo Text::_('EB_REGISTRATION_STATUS'); ?>">
					<?php
						switch ($row->published)
						{
							case 0 :
								echo Text::_('EB_PENDING');
								break ;
							case 1 :
								echo Text::_('EB_PAID');
								break ;
							case 2 :
								echo Text::_('EB_CANCELLED');
								break;
							case 4:
								echo Text::_('EB_WAITING_LIST_CANCELLED');
								break;
							case 3:
								echo Text::_('EB_WAITING_LIST');
								break;
						}
					?>
				</td>
			<?php
			}
		?>
		</tr>
	<?php
	}
	?>
	</tbody>
</table>
<?php
}
else
{
?>
	<div class="eb-message"><?php echo Text::_('EB_NO_REGISTRATION_RECORDS');?></div>
<?php
}

if (!$this->input->getInt('hmvc_call'))
{
?>
	</div>
<?php
}
