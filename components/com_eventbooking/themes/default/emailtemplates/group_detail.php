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
 * @var EventbookingHelperBootstrap $bootstrapHelper
 * @var RADConfig                   $config
 * @var stdClass                    $rowLocation
 * @var RADForm                     $form
 * @var array                       $rowMembers
 * @var array                       $ticketTypes
 * @var string                      $last4Digits
 * @var string                      $autoCouponCode
 */

$controlGroupClass  = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass  = $bootstrapHelper->getClassMapping('control-label');
$controlsClass      = $bootstrapHelper->getClassMapping('controls');
$rowFluidClass      = $bootstrapHelper->getClassMapping('row-fluid');
$span6Class         = $bootstrapHelper->getClassMapping('span6');
$formFormHorizontal = $bootstrapHelper->getClassMapping('form form-horizontal');
?>
<form id="adminForm" class="<?php echo $formFormHorizontal; ?>">
	<div class="<?php echo $controlGroupClass;  ?>">
		<h3 class="eb-heading"><?php echo Text::_('EB_GENERAL_INFORMATION') ; ?></h3>
	</div>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass;  ?>">
			<?php echo  Text::_('EB_EVENT') ?>
		</label>
		<div class="<?php echo $controlsClass;  ?>">
			<?php echo $rowEvent->title ; ?>
		</div>
	</div>
	<?php
		if ($config->show_event_date)
		{
		?>
		<div class="<?php echo $controlGroupClass;  ?>">
			<label class="<?php echo $controlLabelClass;  ?>">
				<?php echo  Text::_('EB_EVENT_DATE') ?>
			</label>
			<div class="<?php echo $controlsClass;  ?>">
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
			<div class="<?php echo $controlGroupClass;  ?>">
				<label class="<?php echo $controlLabelClass;  ?>">
					<?php echo  Text::_('EB_LOCATION') ?>
				</label>
				<div class="<?php echo $controlsClass;  ?>">
					<?php echo $location->name . ' (' . implode(', ', $locationInformation) . ')' ; ?>
				</div>
			</div>
		<?php
		}
	?>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass;  ?>">
			<?php echo  Text::_('EB_NUMBER_REGISTRANTS') ?>
		</label>
		<div class="<?php echo $controlsClass;  ?>">
			<?php echo $row->number_registrants ; ?>
		</div>
	</div>
	<?php
		$showBillingStep = EventbookingHelperRegistration::showBillingStep($row->event_id);

		if ($showBillingStep)
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<h3 class="eb-heading"><?php echo Text::_('EB_BILLING_INFORMATION') ; ?></h3>
			</div>
		<?php
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
				<div class="<?php echo $controlGroupClass;  ?>">
					<label class="<?php echo $controlLabelClass;  ?>">
						<?php echo Text::_('EB_AMOUNT'); ?>
					</label>
					<div class="<?php echo $controlsClass;  ?>">
						<?php echo EventbookingHelper::formatCurrency($row->total_amount, $config, $rowEvent->currency_symbol); ?>
					</div>
				</div>
				<?php
				if ($row->discount_amount > 0)
				{
				?>
					<div class="<?php echo $controlGroupClass;  ?>">
						<label class="<?php echo $controlLabelClass;  ?>">
							<?php echo  Text::_('EB_DISCOUNT_AMOUNT'); ?>
						</label>
						<div class="<?php echo $controlsClass;  ?>">
							<?php echo EventbookingHelper::formatCurrency($row->discount_amount, $config, $rowEvent->currency_symbol); ?>
						</div>
					</div>
				<?php
				}

				if ($row->late_fee > 0)
				{
				?>
					<div class="<?php echo $controlGroupClass;  ?>">
						<label class="<?php echo $controlLabelClass;  ?>">
							<?php echo  Text::_('EB_LATE_FEE'); ?>
						</label>
						<div class="<?php echo $controlsClass;  ?>">
							<?php echo EventbookingHelper::formatCurrency($row->late_fee, $config, $rowEvent->currency_symbol);?>
						</div>
					</div>
				<?php
				}

				if ($row->tax_amount > 0)
				{
				?>
					<div class="<?php echo $controlGroupClass;  ?>">
						<label class="<?php echo $controlLabelClass;  ?>">
							<?php echo  Text::_('EB_TAX_AMOUNT'); ?>
						</label>
						<div class="<?php echo $controlsClass;  ?>">
							<?php echo EventbookingHelper::formatCurrency($row->tax_amount, $config, $rowEvent->currency_symbol); ?>
						</div>
					</div>
				<?php
				}

				if ($row->payment_processing_fee > 0)
				{
				?>
					<div class="<?php echo $controlGroupClass;  ?>">
						<label class="<?php echo $controlLabelClass;  ?>">
							<?php echo  Text::_('EB_PAYMENT_FEE'); ?>
						</label>
						<div class="<?php echo $controlsClass;  ?>">
							<?php echo EventbookingHelper::formatCurrency($row->payment_processing_fee, $config, $rowEvent->currency_symbol); ?>
						</div>
					</div>
				<?php
				}

				if ($row->discount_amount > 0 || $row->tax_amount > 0 || $row->payment_processing_fee > 0 || $row->late_fee > 0)
				{
				?>
					<div class="<?php echo $controlGroupClass;  ?>">
						<label class="<?php echo $controlLabelClass;  ?>">
							<?php echo  Text::_('EB_GROSS_AMOUNT'); ?>
						</label>
						<div class="<?php echo $controlsClass;  ?>">
							<?php echo EventbookingHelper::formatCurrency($row->amount, $config, $rowEvent->currency_symbol) ; ?>
						</div>
					</div>
				<?php
				}
			}

			if ($row->deposit_amount > 0)
			{
			?>
				<div class="<?php echo $controlGroupClass;  ?>">
					<label class="<?php echo $controlLabelClass;  ?>">
						<?php echo Text::_('EB_DEPOSIT_AMOUNT'); ?>
					</label>
					<div class="<?php echo $controlsClass;  ?>">
						<?php echo EventbookingHelper::formatCurrency($row->deposit_amount, $config, $rowEvent->currency_symbol); ?>
					</div>
				</div>
				<div class="<?php echo $controlGroupClass;  ?>">
					<label class="<?php echo $controlLabelClass;  ?>">
						<?php echo Text::_('EB_DUE_AMOUNT'); ?>
					</label>
					<div class="<?php echo $controlsClass;  ?>">
						<?php echo EventbookingHelper::formatCurrency($row->amount - $row->deposit_amount, $config, $rowEvent->currency_symbol); ?>
					</div>
				</div>
			<?php
			}

			if ($row->amount > 0)
			{
			?>
				<div class="<?php echo $controlGroupClass;  ?>">
					<label class="<?php echo $controlLabelClass;  ?>">
						<?php echo  Text::_('EB_PAYMENT_METHOD'); ?>
					</label>
					<div class="<?php echo $controlsClass;  ?>">
						<?php
						$method = EventbookingHelperPayments::loadPaymentMethod($row->payment_method);
						if ($method)
						{
							echo Text::_($method->title) ;
						}
						?>
					</div>
				</div>
				<div class="<?php echo $controlGroupClass;  ?>">
					<label class="<?php echo $controlLabelClass;  ?>">
						<?php echo Text::_('EB_TRANSACTION_ID'); ?>
					</label>
					<div class="<?php echo $controlsClass;  ?>">
						<?php echo $row->transaction_id ; ?>
					</div>
				</div>
			<?php
			}
		}

		if ($rowEvent->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $rowEvent->collect_member_information;
		}

		if ($collectMemberInformation && count($rowMembers))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<h3 class="eb-heading"><?php echo Text::_('EB_MEMBERS_INFORMATION') ; ?></h3>
			</div>
			<?php
				if ($row->published == 3)
				{
					$typeOfRegistration = 2;
				}
				else
				{
					$typeOfRegistration = 1;
				}

				$userId    = $row->user_id ?: null;
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 2, null, $userId, $typeOfRegistration);

				for ($i = 0 , $n  = count($rowMembers); $i < $n; $i++)
				{
					$currentMemberFormFields = EventbookingHelperRegistration::getGroupMemberFields($rowFields, $i + 1);
					$memberForm = new RADForm($currentMemberFormFields);
					$rowMember = $rowMembers[$i];

					$memberData = EventbookingHelperRegistration::getRegistrantData($rowMember, $currentMemberFormFields);
					$memberForm->bind($memberData);

					//Build dependency
					$memberForm->buildFieldsDependency();

					if ($rowEvent->has_multiple_ticket_types)
					{
						$ticketType = EventbookingHelperRegistration::getGroupMemberTicketTypeData($rowMember->id, $row->language);

						if ($ticketType)
						{
							$memberForm->handleFieldsDependOnTicketTypes([$ticketType->id]);
						}
					}
					else
					{
						$ticketType = null;
					}


					$fields = $memberForm->getFields();

					foreach ($fields as $field)
					{
						if ($field->hideOnDisplay)
						{
							unset($fields[$field->name]);
						}
					}

					$memberForm->setFields($fields);
				?>
					<div class="<?php echo $controlGroupClass;  ?>">
						<h4 class="eb-heading"><?php echo Text::sprintf('EB_MEMBER_INFORMATION', $i + 1) ; ?></h4>
					</div>
					<?php
						if ($ticketType)
						{
						?>
							<div class="<?php echo $controlGroupClass;  ?>">
								<label class="<?php echo $controlLabelClass;  ?>">
									<?php echo Text::_('EB_TICKET_TYPE'); ?>
								</label>
								<div class="<?php echo $controlsClass;  ?>">
									<?php echo Text::_($ticketType->title); ?>
								</div>
							</div>
						<?php
						}

						$fields = $memberForm->getFields();

						foreach ($fields as $field)
						{
							echo $field->getOutput(true, $bootstrapHelper);
						}
					?>
				<?php
			}
		}
	?>
</form>