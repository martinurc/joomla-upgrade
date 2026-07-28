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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Layout variables
 * -----------------
 * @var   EventbookingTableEvent $item
 * @var   RADConfig              $config
 * @var   boolean                $showLocation
 * @var   stdClass               $location
 * @var   boolean                $isMultipleDate
 * @var   int                    $Itemid
 */

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
?>
<table class="<?php echo $bootstrapHelper->getClassMapping('table table-bordered table-striped'); ?>">
	<tbody>
	<?php
	if (!$isMultipleDate)
	{
	?>
		<tr class="eb-event-property">
			<td style="width: 30%;" class="eb-event-property-label">
				<?php echo Text::_('EB_EVENT_DATE') ?>
			</td>
			<td class="eb-event-property-value">
				<?php
				if ($item->event_date == EB_TBC_DATE)
				{
					echo Text::_('EB_TBC');
				}
				else
				{
					echo EventbookingHelperFormatter::getFormattedDatetime($item->event_date);
				}
				?>
			</td>
		</tr>

		<?php
		if ($config->get('show_event_end_date', '1') && (int) $item->event_end_date)
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_EVENT_END_DATE'); ?>
				</td>
				<td class="eb-event-property-value">
					<?php echo EventbookingHelperFormatter::getFormattedDatetime($item->event_end_date); ?>
				</td>
			</tr>
		<?php
		}

		if ($config->get('show_registration_start_date', '1') && (int) $item->registration_start_date)
		{
			$registrationStartDate = Factory::getDate($item->registration_start_date, Factory::getApplication()->get('offset'));
			$currentDate           = Factory::getDate('now', Factory::getApplication()->get('offset'));

			if ($registrationStartDate > $currentDate)
			{
				?>
					<tr class="eb-event-property">
						<td class="eb-event-property-label">
							<?php echo Text::_('EB_REGISTRATION_START_DATE'); ?>
						</td>
						<td class="eb-event-property-value">
							<?php echo EventbookingHelperFormatter::getFormattedDatetime($item->registration_start_date); ?>
						</td>
					</tr>
	            <?php
			}
		}

		if ($config->get('show_cut_off_date', '1') && (int) $item->cut_off_date)
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_CUT_OFF_DATE'); ?>
				</td>
				<td class="eb-event-property-value">
					<?php echo EventbookingHelperFormatter::getFormattedDatetime($item->cut_off_date); ?>
				</td>
			</tr>
		<?php
		}

		if ($config->get('show_cancel_before_date', '0') && (int) $item->cancel_before_date)
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_CANCEL_BEFORE_DATE'); ?>
				</td>
				<td class="eb-event-property-value">
					<?php echo EventbookingHelperFormatter::getFormattedDatetime($item->cancel_before_date); ?>
				</td>
			</tr>
		<?php
		}

		if ($config->show_capacity == 1 || ($config->show_capacity == 2 && $item->event_capacity))
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_CAPACITY'); ?>
				</td>
				<td class="eb-event-property-value">
					<?php
					if ($item->event_capacity)
					{
						echo $item->event_capacity;
					}
					else
					{
						echo Text::_('EB_UNLIMITED');
					}
					?>
				</td>
			</tr>
		<?php
		}

		if ($config->show_registered && $item->registration_type != 3)
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_REGISTERED'); ?>
				</td>
				<td class="eb-event-property-value">
					<?php
					echo $item->total_registrants . ' ';

					if ($config->show_list_of_registrants
						&& $item->total_registrants > 0
						&& EventbookingHelper::callOverridableHelperMethod('Acl', 'canViewRegistrantList', [$item->id]))
					{
					?>
						<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=registrantlist&id=' . $item->id . '&tmpl=component'); ?>"
						   class="eb-colorbox-register-lists"><span class="view_list"><?php echo Text::_('EB_VIEW_LIST'); ?></span></a>
					<?php
					}
					?>
				</td>
			</tr>
		<?php
		}

		if ($config->show_available_place && $item->event_capacity && $item->registration_type != 3)
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_AVAILABLE_PLACE'); ?>
				</td>
				<td class="eb-event-property-value">
					<?php echo max($item->event_capacity - $item->total_registrants, 0); ?>
				</td>
			</tr>
		<?php
		}

		if ($config->show_waiting_list && $item->registration_type != 3
			&& $item->event_capacity > 0 && ($item->event_capacity <= $item->total_registrants))
		{
			$numberWaitingList = EventbookingHelperRegistration::countNumberWaitingList($item);
		}
		else
		{
			$numberWaitingList = 0;
		}

		if ($numberWaitingList > 0)
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_WAITING_LIST'); ?>
				</td>
				<td class="eb-event-property-value">
					<?php
					$numberWaitingList = EventbookingHelperRegistration::countNumberWaitingList($item);
					echo $numberWaitingList . ' ';

					if ($config->show_list_of_waiting_list
						&& $numberWaitingList > 0
						&& EventbookingHelper::callOverridableHelperMethod('Acl', 'canViewRegistrantList', [$item->id]))
					{
					?>
						<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=registrantlist&id=' . $item->id . '&registrant_type=3&tmpl=component'); ?>"
						   class="eb-colorbox-register-lists"><span class="view_list"><?php echo Text::_('EB_VIEW_LIST'); ?></span></a>
					<?php
					}
					?>
				</td>
			</tr>
		<?php
		}
	}

	// Whether we should show price for this event
	if ($item->price_text || $item->individual_price > 0 || $config->show_price_for_free_event)
	{
		if ($config->show_discounted_price && ($item->individual_price != $item->discounted_price))
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_ORIGINAL_PRICE'); ?>
				</td>
				<td class="eb-event-property-value eb_price">
					<?php
					if ($item->individual_price > 0)
					{
						echo EventbookingHelper::formatCurrency($item->individual_price, $config, $item->currency_symbol);
					}
					else
					{
						echo '<span class="eb_free">' . Text::_('EB_FREE') . '</span>';
					}
					?>
				</td>
			</tr>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_DISCOUNTED_PRICE'); ?>
				</td>
				<td class="eb-event-property-value eb_price">
					<?php
					if ($item->discounted_price > 0)
					{
						echo EventbookingHelper::formatCurrency($item->discounted_price, $config, $item->currency_symbol);

						if ($item->early_bird_discount_amount > 0 && (int) $item->early_bird_discount_date)
						{
							echo ' <em>' . Text::sprintf('EB_UNTIL_DATE', HTMLHelper::_('date', $item->early_bird_discount_date, $config->date_format . ' '. $config->get('event_time_format', 'g:i a'), null)) . '</em>';
						}
					}
					else
					{
						echo '<span class="eb_free">' . Text::_('EB_FREE') . '</span>';
					}
					?>
				</td>
			</tr>
		<?php
		}
		else
		{
		?>
			<tr class="eb-event-property">
				<td class="eb-event-property-label">
					<?php echo Text::_('EB_INDIVIDUAL_PRICE'); ?>
				</td>
				<td class="eb-event-property-value eb_price">
					<?php
					echo $item->priceDisplay;
					?>
				</td>
			</tr>
		<?php
		}
	}

	if ($item->fixed_group_price > 0)
	{
	?>
		<tr class="eb-event-property">
			<td class="eb-event-property-label">
				<?php echo Text::_('EB_FIXED_GROUP_PRICE'); ?>
			</td>
			<td class="eb-event-property-value eb_price">
				<?php echo EventbookingHelper::formatCurrency($item->fixed_group_price, $config, $item->currency_symbol); ?>
			</td>
		</tr>
	<?php
	}

	if ($item->late_fee_amount > 0)
	{
	?>
		<tr class="eb-event-property">
			<td class="eb-event-property-label">
				<?php echo Text::_('EB_LATE_FEE'); ?>
			</td>
			<td class="eb-event-property-value">
				<?php
					if ($item->late_fee_type == 1)
					{
						// Late Fee by percent
						echo $item->late_fee_amount . '%';
					}
					else
					{
						echo EventbookingHelper::formatCurrency($item->late_fee_amount, $config, $item->currency_symbol);
					}

					echo '<em> ' . Text::sprintf('EB_FROM_DATE', HTMLHelper::_('date', $item->late_fee_date, $config->date_format . ' H:i', null)) . '</em>';
				?>
			</td>
		</tr>
	<?php
	}

	if ($config->show_event_creator)
	{
	?>
		<tr class="eb-event-property">
			<td class="eb-event-property-label">
				<?php echo Text::_('EB_CREATED_BY'); ?>
			</td>
			<td class="eb-event-property-value">
				<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=search&created_by=' . $item->created_by . '&Itemid=' . $Itemid); ?>"><?php echo $item->creator_name; ?></a>
			</td>
		</tr>
	<?php
	}

	if (isset($item->paramData))
	{
		echo EventbookingHelperHtml::loadCommonLayout('common/event_fields.php', ['item' => $item]);
	}

	if ($showLocation && $location)
	{
	?>
		<tr class="eb-event-property">
			<td class="eb-event-property-label">
				<?php echo Text::_('EB_LOCATION'); ?>
			</td>
			<td class="eb-event-property-value">
				<?php
					echo EventbookingHelperHtml::loadCommonLayout('elements/location.php', ['location' => $location, 'Itemid' => $Itemid]);
				?>
			</td>
		</tr>
	<?php
	}

	if ($config->show_event_categories)
	{
	?>
		<tr class="eb-event-property">
			<td class="eb-event-property-label">
				<?php echo Text::_('EB_CATEGORIES'); ?>
			</td>
			<td class="eb-event-property-value">
				<?php echo EventbookingHelperHtml::loadCommonLayout('elements/categories.php', ['categories' => $item->categories, 'Itemid' => $Itemid]); ?>
			</td>
		</tr>
	<?php
	}

	if ($item->attachment && !empty($config->show_attachment_in_frontend))
	{
	?>
	<tr class="eb-event-property">
		<td class="eb-event-property-label">
			<?php echo Text::_('EB_ATTACHMENT'); ?>
		</td>
		<td class="eb-event-property-value">
			<?php echo EventbookingHelperHtml::loadCommonLayout('elements/attachments.php', ['attachments' => explode('|', $item->attachment)]); ?>
		</td>
	</tr>
	<?php
	}
	?>
	</tbody>
</table>
