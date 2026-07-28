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

/**
 * Layout variables
 *
 * @var EventbookingTableRegistrant $row
 * @var EventbookingTableEvent      $rowEvent
 * @var RADConfig                   $config
 * @var stdClass                    $rowLocation
 * @var RADForm                     $form
 * @var array                       $ticketTypes
 * @var string                      $last4Digits
 * @var string                      $autoCouponCode
 */

$controlGroupClass  = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass  = $bootstrapHelper->getClassMapping('control-label');
$controlsClass      = $bootstrapHelper->getClassMapping('controls');
$formFormHorizontal = $bootstrapHelper->getClassMapping('form form-horizontal');

$showPriceColumn = EventbookingHelperRegistration::showPriceColumnForTicketType($rowEvent->id);
?>
<form id="adminForm" class="<?php echo $formFormHorizontal; ?>">
	<?php
		if (!empty($ticketTypes))
		{
		?>
			<h3 class="eb-heading"><?php echo Text::_('EB_TICKET_INFORMATION'); ?></h3>
			<table class="<?php echo $bootstrapHelper->getClassMapping('table table-striped table-bordered table-condensed'); ?>">
				<thead>
				<tr>
					<th>
						<?php echo Text::_('EB_TICKET_TYPE'); ?>
					</th>
					<?php
						if ($showPriceColumn)
						{
						?>
							<th class="eb-text-right">
								<?php echo Text::_('EB_PRICE'); ?>
							</th>
						<?php
						}
					?>
					<th class="center">
						<?php echo Text::_('EB_QUANTITY'); ?>
					</th>
					<?php
						if ($showPriceColumn)
						{
						?>
							<th class="eb-text-right">
								<?php echo Text::_('EB_SUB_TOTAL'); ?>
							</th>
						<?php
						}
					?>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ($ticketTypes as $ticketType)
				{
				?>
					<tr>
						<td>
							<?php echo Text::_($ticketType->title); ?>
						</td>
						<?php
							if ($showPriceColumn)
							{
							?>
								<td class="eb-text-right">
									<?php echo EventbookingHelper::formatCurrency($ticketType->price, $config, $rowEvent->currency_symbol); ?>
								</td>
							<?php
							}
						?>
						<td class="center">
							<?php echo $ticketType->quantity; ?>
						</td>
						<?php
							if ($showPriceColumn)
							{
							?>
								<td class="eb-text-right">
									<?php echo EventbookingHelper::formatCurrency($ticketType->price*$ticketType->quantity, $config, $rowEvent->currency_symbol); ?>
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
	?>
	<div class="<?php echo $controlGroupClass; ?>">
		<label class="<?php echo $controlLabelClass; ?>">
			<?php echo  Text::_('EB_EVENT') ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php echo $rowEvent->title ; ?>
		</div>
	</div>
	<?php
		if ($config->show_event_date)
		{
		?>
		<div class="<?php echo $controlGroupClass; ?>">
			<label class="<?php echo $controlLabelClass; ?>">
				<?php echo  Text::_('EB_EVENT_DATE') ?>
			</label>
			<div class="<?php echo $controlsClass; ?>">
				<?php
					if ($rowEvent->event_date == EB_TBC_DATE)
					{
						echo Text::_('EB_TBC');
					}
					else
					{
						echo EventbookingHelperFormatter::getFormattedDatetime($rowEvent->event_date);
					}
				?>
			</div>
		</div>
		<?php
			if ((int) $rowEvent->event_end_date)
			{
			?>
				<div class="<?php echo $controlGroupClass; ?>">
					<label class="<?php echo $controlLabelClass; ?>">
						<?php echo Text::_('EB_EVENT_END_DATE') ?>
					</label>
					<div class="<?php echo $controlsClass; ?>">
						<?php echo EventbookingHelperFormatter::getFormattedDatetime($rowEvent->event_end_date); ?>
					</div>
				</div>
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
			<div class="<?php echo $controlGroupClass; ?>">
				<label class="<?php echo $controlLabelClass; ?>">
					<?php echo  Text::_('EB_LOCATION') ?>
				</label>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $location->name . ' (' . implode(', ', $locationInformation) . ')' ; ?>
				</div>
			</div>
		<?php
		}

		//Show data for form
		$fields = $form->getFields();

		foreach ($fields as $field)
		{
			if ($field->hideOnDisplay || $field->row->hide_on_email)
			{
				continue;
			}

			echo $field->getOutput(true, $bootstrapHelper);
		}

		if ($row->total_amount > 0)
		{
		?>
		<div class="<?php echo $controlGroupClass; ?>">
			<label class="<?php echo $controlLabelClass; ?>">
				<?php echo Text::_('EB_AMOUNT'); ?>
			</label>
			<div class="<?php echo $controlsClass; ?>">
				<?php echo EventbookingHelper::formatCurrency($row->total_amount, $config, $rowEvent->currency_symbol); ?>
			</div>
		</div>
		<?php
			if ($row->discount_amount > 0)
			{
			?>
				<div class="<?php echo $controlGroupClass; ?>">
					<label class="<?php echo $controlLabelClass; ?>">
						<?php echo  Text::_('EB_DISCOUNT_AMOUNT'); ?>
					</label>
					<div class="<?php echo $controlsClass; ?>">
						<?php echo EventbookingHelper::formatCurrency($row->discount_amount, $config, $rowEvent->currency_symbol); ?>
					</div>
				</div>
			<?php
			}

			if ($row->late_fee > 0)
			{
			?>
				<div class="<?php echo $controlGroupClass; ?>">
					<label class="<?php echo $controlLabelClass; ?>">
						<?php echo  Text::_('EB_LATE_FEE'); ?>
					</label>
					<div class="<?php echo $controlsClass; ?>">
						<?php echo EventbookingHelper::formatCurrency($row->late_fee, $config, $rowEvent->currency_symbol); ?>
					</div>
				</div>
			<?php
			}

			if ($row->tax_amount > 0)
			{
			?>
				<div class="<?php echo $controlGroupClass; ?>">
					<label class="<?php echo $controlLabelClass; ?>">
						<?php echo  Text::_('EB_TAX_AMOUNT'); ?>
					</label>
					<div class="<?php echo $controlsClass; ?>">
						<?php echo EventbookingHelper::formatCurrency($row->tax_amount, $config, $rowEvent->currency_symbol); ?>
					</div>
				</div>
			<?php
			}

			if ($row->payment_processing_fee > 0)
			{
			?>
				<div class="<?php echo $controlGroupClass; ?>">
					<label class="<?php echo $controlLabelClass; ?>">
						<?php echo  Text::_('EB_PAYMENT_FEE'); ?>
					</label>
					<div class="<?php echo $controlsClass; ?>">
						<?php echo EventbookingHelper::formatCurrency($row->payment_processing_fee, $config, $rowEvent->currency_symbol); ?>
					</div>
				</div>
			<?php
			}

			if ($row->discount_amount > 0 || $row->tax_amount > 0 || $row->payment_processing_fee > 0 || $row->late_fee > 0)
			{
			?>
				<div class="<?php echo $controlGroupClass; ?>">
					<label class="<?php echo $controlLabelClass; ?>">
						<?php echo  Text::_('EB_GROSS_AMOUNT'); ?>
					</label>
					<div class="<?php echo $controlsClass; ?>">
						<?php echo EventbookingHelper::formatCurrency($row->amount, $config, $rowEvent->currency_symbol) ; ?>
					</div>
				</div>
			<?php
			}
		}

		if ($row->deposit_amount > 0)
		{
		?>
		<div class="<?php echo $controlGroupClass; ?>">
			<label class="<?php echo $controlLabelClass; ?>">
				<?php echo Text::_('EB_DEPOSIT_AMOUNT'); ?>
			</label>
			<div class="<?php echo $controlsClass; ?>">
				<?php echo EventbookingHelper::formatCurrency($row->deposit_amount, $config, $rowEvent->currency_symbol); ?>
			</div>
		</div>
		<div class="<?php echo $controlGroupClass; ?>">
			<label class="<?php echo $controlLabelClass; ?>">
				<?php echo Text::_('EB_DUE_AMOUNT'); ?>
			</label>
			<div class="<?php echo $controlsClass; ?>">
				<?php echo EventbookingHelper::formatCurrency($row->amount - $row->deposit_amount, $config, $rowEvent->currency_symbol); ?>
			</div>
		</div>
		<?php
		}

		if ($row->amount > 0 && $row->published != 3)
		{
		?>
		<div class="<?php echo $controlGroupClass; ?>">
			<label class="<?php echo $controlLabelClass; ?>">
				<?php echo  Text::_('EB_PAYMENT_METHOD'); ?>
			</label>
			<div class="<?php echo $controlsClass; ?>">
			<?php
				$method = EventbookingHelperPayments::loadPaymentMethod($row->payment_method);

				if ($method)
				{
					echo Text::_($method->title) ;
				}
			?>
			</div>
		</div>
		<div class="<?php echo $controlGroupClass; ?>">
			<label class="<?php echo $controlLabelClass; ?>">
				<?php echo Text::_('EB_TRANSACTION_ID'); ?>
			</label>
			<div class="<?php echo $controlsClass; ?>">
				<?php echo $row->transaction_id ; ?>
			</div>
		</div>
		<?php
		}
	?>
</form>