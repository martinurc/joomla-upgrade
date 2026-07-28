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
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$isJoomla4      = EventbookingHelper::isJoomla4();
$isMultilingual = Multilanguage::isEnabled();
$rootUri        = Uri::root(true);
$languages      = LanguageHelper::getLanguages('lang_code');
$hasTickets     = count($this->tickets) > 0;

$colSpan = 3;

if (in_array('last_name', $this->coreFields))
{
	$showLastName = true;
}
else
{
	$showLastName = false;
}

$dateFields = [
	'filter_from_date',
	'filter_to_date',
];

foreach ($dateFields as $dateField)
{
	if ((int) $this->state->{$dateField} === 0)
	{
		$this->state->{$dateField} = '';
	}
	elseif ((int) $this->state->{$dateField})
	{
		try
		{
			$date = DateTime::createFromFormat($this->dateFormat, $this->state->{$dateField});

			if ($date !== false)
			{
				$this->state->{$dateField} = $date->format('Y-m-d');
			}
		}
		catch (Exception $e)
		{

		}
	}
}

$this->includeTemplate('script');
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	<div id="j-main-container"<?php if ($isJoomla4) echo ' class="eb-joomla4-container"'; ?>>
		<div id="filter-bar" class="btn-toolbar<?php if ($isJoomla4) echo ' js-stools-container-filters-visible'; ?>">
			<div class="filter-search btn-group pull-left">
				<label for="filter_search" class="element-invisible"><?php echo Text::_('EB_FILTER_SEARCH_REGISTRANTS_DESC');?></label>
				<input type="text" name="filter_search" id="filter_search" inputmode="search" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->filter_search); ?>" class="hasTooltip form-control" title="<?php echo HTMLHelper::tooltipText('EB_SEARCH_REGISTRANTS_DESC'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<?php echo $this->lists['filter_date_field']; ?>
			</div>
			<div class="btn-group pull-left">
				<?php
					echo HTMLHelper::_('calendar', $this->state->filter_from_date, 'filter_from_date', 'filter_from_date', $this->datePickerFormat, ['class' => $isJoomla4 ? 'input-medium' : 'input-small', 'placeholder' => Text::_('EB_FROM')]);
					echo HTMLHelper::_('calendar', $this->state->filter_to_date, 'filter_to_date', 'filter_to_date', $this->datePickerFormat, ['class' => $isJoomla4 ? 'input-medium' : 'input-small', 'placeholder' => Text::_('EB_TO')]);
				?>
			</div>
			<div class="btn-group pull-left">
				<button type="submit" class="btn<?php if ($isJoomla4) echo ' btn-primary'; ?> hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>" onclick="document.getElementById('task').value=''; return true;"><span class="icon-search"></span></button>
				<button type="button" class="btn<?php if ($isJoomla4) echo ' btn-primary'; ?> hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value=''; document.getElementById('filter_from_date').value=''; document.getElementById('filter_to_date').value=''; document.getElementById('task').value=''; this.form.submit();"><span class="icon-remove"></span></button>
			</div>
			<div class="btn-group pull-right btn-filter-second-row">
				<?php $this->includeTemplate('filter'); ?>
			</div>
		</div>
		<div class="clearfix"></div>
		<table class="adminlist table table-striped">
			<thead>
			<tr>
				<th width="2%" class="text_center">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
				</th>
				<th class="title" style="text-align: left;" width="10%">
					<?php echo $this->gridSort('EB_FIRST_NAME',  'tbl.first_name'); ?>
				</th>
				<?php
					if ($showLastName && $this->config->get('rm_show_last_name', 1))
					{
						$colSpan++;
					?>
						<th class="title" style="text-align: left;" width="10%">
							<?php echo $this->gridSort('EB_LAST_NAME',  'tbl.last_name'); ?>
						</th>
					<?php
					}
				?>
				<th class="title" style="text-align: left;" width="15%">
					<?php echo $this->gridSort('EB_EVENT',  'ev.title'); ?>
				</th>
				<?php
				if ($this->config->show_event_date)
				{
					$colSpan++;
				?>
					<th width="7%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_EVENT_DATE',  'ev.event_date'); ?>
					</th>
				<?php
				}

				if ($this->config->get('rm_show_email', 1))
				{
					$colSpan++;
				?>
					<th width="10%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_EMAIL',  'tbl.email'); ?>
					</th>
				<?php
				}

				if ($this->config->get('rm_show_number_registrants', 1))
				{
					$colSpan++;
				?>
                    <th class="center" nowrap="nowrap">
	                    <?php echo $this->gridSort('EB_NUMBER_REGISTRANTS',  'tbl.number_registrants'); ?>
                    </th>
                <?php
				}

				if ($hasTickets)
				{
					$colSpan++;
				?>
                    <th width="10%" class="title" nowrap="nowrap">
                        <?php echo Text::_('EB_TICKETS'); ?>
                    </th>
                <?php
				}

				if ($this->config->get('rm_show_registration_date', 1))
				{
					$colSpan++;
				?>
                    <th width="10%" class="title" nowrap="nowrap">
	                    <?php echo $this->gridSort('EB_REGISTRATION_DATE',  'tbl.register_date'); ?>
                    </th>
                <?php
				}

				if ($this->config->get('rm_show_amount', 1))
				{
					$colSpan++;
				?>
                    <th width="5%" class="title" nowrap="nowrap">
	                    <?php echo $this->gridSort('EB_GROSS_AMOUNT',  'tbl.amount'); ?>
                    </th>
                <?php
				}

				foreach ($this->fields as $field)
				{
					$colSpan++;

					if ($field->is_core || $field->is_searchable)
					{
					?>
						<th class="title" nowrap="nowrap">
							<?php echo $this->gridSort($field->title,  'tbl.' . $field->name); ?>
						</th>
					<?php
					}
					else
					{
					?>
						<th class="title" nowrap="nowrap"><?php echo $field->title; ?></th>
					<?php
					}
				}

				if ($this->config->activate_deposit_feature)
				{
					$colSpan++;
				?>
					<th width="5%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_DEPOSIT_PAYMENT_STATUS',  'tbl.payment_status'); ?>
					</th>
				<?php
				}

				if ($this->config->enable_coupon)
				{
					$colSpan++;
				?>
					<th width="7%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_COUPON',  'cp.code'); ?>
					</th>
				<?php
				}

				if ($this->totalPlugins > 1 && $this->config->get('rm_show_payment_method', 1))
				{
					$colSpan++;
				?>
					<th width="5%" class="title" nowrap="nowrap">
						<?php echo $this->gridSort('EB_PAYMENT_METHOD',  'tbl.payment_method'); ?>
					</th>
				<?php
				}

				if ($this->config->activate_tickets_pdf)
				{
					$colSpan++;
				?>
					<th width="8%" class="center">
						<?php echo $this->gridSort('EB_TICKET_NUMBER',  'tbl.ticket_number'); ?>
					</th>
				<?php
				}

				if ($this->config->get('rm_show_registration_status', 1))
				{
					$colSpan++;
				?>
                    <th width="5%" class="title">
	                    <?php echo $this->gridSort('EB_REGISTRATION_STATUS',  'tbl.published'); ?>
                    </th>
                <?php
				}

				if ($this->config->activate_checkin_registrants)
				{
					$colSpan++;
				?>
					<th width="8%" class="center">
						<?php echo $this->gridSort('EB_CHECKED_IN',  'tbl.checked_in'); ?>
					</th>
				<?php
				}

				if ($this->config->activate_invoice_feature)
				{
					$colSpan++;
				?>
					<th width="8%" class="center">
						<?php echo $this->gridSort('EB_INVOICE_NUMBER',  'tbl.invoice_number'); ?>
					</th>
				<?php
				}

				if ($this->config->show_certificate_sent_status)
				{
					$colSpan++;
				?>
					<th class="center">
						<?php echo $this->gridSort('EB_CERTIFICATE_SENT',  'tbl.certificate_sent'); ?>
					</th>
				<?php
				}

				if ($isMultilingual)
				{
					$colSpan++;
				?>
                    <th class="center">
	                    <?php echo $this->gridSort('EB_LANGUAGE',  'tbl.language'); ?>
                    </th>
                <?php
				}

				if ($this->config->rm_show_transaction_id)
				{
					$colSpan++;
				?>
                    <th class="title" nowrap="nowrap">
	                    <?php echo $this->gridSort('EB_TRANSACTION_ID',  'tbl.transaction_id'); ?>
                    </th>
                <?php
				}

				if ($this->config->get('rm_show_id', 1))
				{
					$colSpan++;
				?>
                    <th width="3%" class="title" nowrap="nowrap">
	                    <?php echo $this->gridSort('EB_ID',  'tbl.id'); ?>
                    </th>
                <?php
				}
				?>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="<?php echo $colSpan ; ?>">
					<?php echo $this->pagination->getPaginationLinks(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody>
			<?php
			$k = 0;
			$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
			$iconPublish = $bootstrapHelper->getClassMapping('icon-publish');
			$iconUnPublish = $bootstrapHelper->getClassMapping('icon-unpublish');

			for ($i = 0, $n = count($this->items); $i < $n; $i++)
			{
				$row     = $this->items[$i];
				$link    = $this->getEditItemLink($row);
				$checked = HTMLHelper::_('grid.id', $i, $row->id);

				if (in_array($row->published, [0, 1]))
				{
					$published = HTMLHelper::_('jgrid.published', $row->published, $i);
				}
				elseif ($row->published == 3)
				{
					$published = Text::_('EB_WAITING_LIST');
				}
				elseif ($row->published == 4)
				{
					$published = Text::_('EB_WAITING_LIST_CANCELLED');
				}
				else
				{
					$imageSrc  = $rootUri . '/media/com_eventbooking/assets/admin/icons/cancelled.jpg';
					$title     = Text::_('EB_CANCELLED');
					$published = '<img src="' . $imageSrc . '" title="' . $title . '" />';
				}

				$isMember = $row->group_id > 0;

				if ($isMember)
				{
					$groupLink = Route::_('index.php?option=com_eventbooking&view=registrant&id=' . $row->group_id);
				}

				$iconClass = $row->checked_in ? $iconPublish : $iconUnPublish;
				$alt       = $row->checked_in ? Text::_('EB_CHECKED_IN') : Text::_('EB_NOT_CHECKED_IN');
				$img       = '<span class="' . $iconClass . '"></span>';
				$action    = $row->checked_in ? Text::_('EB_UN_CHECKIN') : Text::_('EB_CHECKIN');
				$task      = $row->checked_in ? 'reset_check_in' : 'check_in';

				if ($isJoomla4)
				{
					$href = '<a class="tbody-icon" href="javascript:void(0);" onclick="return Joomla.listItemTask(\'cb' . $i . '\',\'' . $task . '\')" title="' . $action . '">' . $img . '</a>';
				}
				else
				{
					$href = '<a class="tbody-icon" href="javascript:void(0);" onclick="return listItemTask(\'cb' . $i . '\',\'' . $task . '\')" title="' . $action . '">' . $img . '</a>';
				}

				?>
				<tr class="<?php echo "row$k"; if ($row->is_group_billing) echo ' eb-row-group-billing'; ?>">
					<td class="text_center">
						<?php echo $checked; ?>
					</td>
					<td>
						<a href="<?php echo $link; ?>">
							<?php
								echo $row->first_name;

								if ($row->username)
								{
								?>
									<a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . $row->user_id); ?>" title="View Profile" target="_blank">&nbsp;<strong>[<?php echo $row->username ; ?>]</strong></a>
								<?php
								}
							?>
						</a>
						<?php
						if ($row->is_group_billing)
						{
							echo '<br />' ;
							echo Text::_('EB_GROUP_BILLING');
						}

						if ($isMember && $row->group_name)
						{
						?>
							<br />
							<?php echo Text::_('EB_GROUP'); ?><a href="<?php echo $groupLink; ?>"><?php echo $row->group_name ;  ?></a>
						<?php
						}
						?>
					</td>
					<?php
						if ($showLastName && $this->config->get('rm_show_last_name', 1))
						{
						?>
							<td>
								<?php echo $row->last_name ; ?>
							</td>
						<?php
						}
					?>
					<td>
						<a href="index.php?option=com_eventbooking&view=event&id=<?php echo $row->event_id; ?>"><?php echo $row->title ; ?></a>
					</td>
					<?php
					if ($this->config->show_event_date)
					{
					?>
						<td class="text_center">
							<?php
							if ($row->event_date == EB_TBC_DATE)
							{
								echo Text::_('EB_TBC');
							}
							else
							{
								echo HTMLHelper::_('date', $row->event_date, $this->config->date_format . ' H:i', null);
							}
							?>
						</td>
					<?php
					}

					if ($this->config->get('rm_show_email', 1))
					{
					?>
						<td>
							<a href="mailto:<?php echo $row->email;?>"><?php echo $row->email;?></a>
						</td>
					<?php
					}

					if ($this->config->get('rm_show_number_registrants', 1))
					{
					?>
                        <td class="center" style="font-weight: bold;">
		                    <?php echo $row->number_registrants; ?>
                        </td>
                    <?php
					}

					if ($hasTickets)
					{
					?>
                        <td>
                            <?php echo implode('<br />', $this->getRegistrantTicketOutput($row)); ?>
                        </td>
                    <?php
					}

					if ($this->config->get('rm_show_registration_date', 1))
					{
					?>
                        <td>
		                    <?php echo HTMLHelper::_('date', $row->register_date, $this->config->date_format . ' H:i'); ?>
                        </td>
                    <?php
					}

					if ($this->config->get('rm_show_amount', 1))
					{
					?>
                        <td>
		                    <?php echo EventbookingHelper::formatAmount($row->amount, $this->config) ; ?>
                        </td>
                    <?php
					}

					foreach ($this->fields as $field)
					{
						$fieldValue = $this->fieldsData[$row->id][$field->id] ?? '';

						if ($fieldValue && $field->fieldtype == 'File')
						{
							$fieldValue = '<a href="' . Route::_('index.php?option=com_eventbooking&task=controller.download_file&file_name=' . $fieldValue) . '">' . $fieldValue . '</a>';
						}
					?>
						<td>
							<?php echo $fieldValue; ?>
						</td>
					<?php
					}

					if ($this->config->activate_deposit_feature)
					{
					?>
						<td>
							<?php
							// Do not show deposit payment status for waiting list
							if (!in_array($row->published, [3, 4]))
							{
								if ($row->payment_status == 1)
								{
									echo Text::_('EB_FULL_PAYMENT');
								}
								elseif ($row->payment_status == 2)
								{
									echo Text::_('EB_DEPOSIT_PAID');
								}
								else
								{
									echo Text::_('EB_PARTIAL_PAYMENT');
								}
							}
							?>
						</td>
					<?php
					}

					if ($this->config->enable_coupon)
					{
					?>
						<td>
							<a href="index.php?option=com_eventbooking&view=coupon&id=<?php echo $row->coupon_id; ?>" target="_blank"><?php echo $row->coupon_code ; ?></a>
						</td>
					<?php
					}

					if ($this->totalPlugins > 1 && $this->config->get('rm_show_payment_method', 1))
					{
						$method = EventbookingHelperPayments::getPaymentMethod($row->payment_method) ;
						?>
						<td>
							<?php if ($method) echo Text::_($method->getTitle()); ?>
						</td>
					<?php
					}

					if ($this->config->activate_tickets_pdf)
					{
					?>
						<td class="center">
							<?php
							if ($row->ticket_code)
							{
							?>
								<a href="<?php echo Route::_('index.php?option=com_eventbooking&task=registrant.download_ticket&id=' . $row->id); ?>" title="<?php echo Text::_('EB_DOWNLOAD'); ?>"><?php echo $row->ticket_number ? EventbookingHelperTicket::formatTicketNumber($row->ticket_prefix, $row->ticket_number, $this->config) : Text::_('EB_DOWNLOAD_TICKETS');?></a>
							<?php
							}
							?>
						</td>
					<?php
					}

					if ($this->config->get('rm_show_registration_status', 1))
					{
					?>
                        <td class="center">
		                    <?php
		                        echo $published;

								if ($row->refunded)
			                    {
								?>
				                    <span class="badge text-bg-danger"><?php echo Text::_('EB_REFUNDED'); ?></span>
	                            <?php
			                    }
							?>
                        </td>
                    <?php
					}

					if ($this->config->activate_checkin_registrants)
					{
					?>
						<td class="center">
							<?php
								echo $href;

								if ($row->checked_in && (int) $row->checked_in_at)
								{
								?>
									<br /><?php echo Text::sprintf('EB_CHECKED_IN_AT', HTMLHelper::_('date', $row->checked_in_at, $this->config->date_format . ' H:i:s')); ?>
								<?php
								}

								if (!$row->checked_in && (int) $row->checked_out_at)
								{
								?>
									<br /><span style="color: red;"><?php echo Text::sprintf('EB_CHECKED_OUT_AT', HTMLHelper::_('date', $row->checked_out_at, $this->config->date_format . ' H:i:s')); ?></span>
								<?php
								}
							?>
						</td>
					<?php
					}

					if ($this->config->activate_invoice_feature)
					{
					?>
						<td class="center">
							<?php
							if ($row->invoice_number)
							{
							?>
								<a href="<?php echo Route::_('index.php?option=com_eventbooking&task=registrant.download_invoice&id=' . ($row->cart_id ?: ($row->group_id ?: $row->id))); ?>" title="<?php echo Text::_('EB_DOWNLOAD'); ?>"><?php echo EventbookingHelper::callOverridableHelperMethod('Helper', 'formatInvoiceNumber', [$row->invoice_number, $this->config, $row]) ; ?></a>
							<?php
							}
							?>
					</td>
					<?php
					}

					if ($this->config->show_certificate_sent_status)
					{
					?>
						<td class="center">
							<a class="tbody-icon"><span class="<?php echo $row->certificate_sent ? $iconPublish : $iconUnPublish; ?>"></span></a>
						</td>
					<?php
					}

					if ($isMultilingual)
					{
					?>
                        <td class="center">
							<?php
								if ($row->language && $row->language != '*' && isset($languages[$row->language]))
								{
									echo '<img src="' . $rootUri . '/media/mod_languages/images/' . $languages[$row->language]->image . '.gif" />';
								}
								else
								{
									echo Text::_('EB_ALL');
								}
							?>
                        </td>
					<?php
					}

					if ($this->config->rm_show_transaction_id)
					{
					?>
                        <td><?php echo $row->transaction_id; ?></td>
                    <?php
					}

					if ($this->config->get('rm_show_id', 1))
					{
					?>
                        <td class="center">
		                    <?php echo $row->id; ?>
                        </td>
                    <?php
					}
					?>
				</tr>
				<?php
				$k = 1 - $k;
			}
			?>
			</tbody>
		</table>

		<?php
			echo HTMLHelper::_(
				'bootstrap.renderModal',
				'collapseModal',
				[
						'title' => Text::_('EB_MASS_MAIL'),
						'footer' => $this->loadTemplate('batch_footer'),
				],
				$this->loadTemplate('batch_body')
			);

			if (PluginHelper::isEnabled('system', 'eventbookingsms'))
			{
				echo HTMLHelper::_(
					'bootstrap.renderModal',
					'collapseModal_Sms',
					[
						'title' => Text::_('EB_BATCH_SMS'),
						'footer' => $this->loadTemplate('batch_sms_footer'),
					],
					$this->loadTemplate('batch_sms_body')
				);
			}

			if (count($this->exportTemplates))
			{
				echo HTMLHelper::_(
					'bootstrap.renderModal',
					'collapseModal_Export_Template',
					[
						'title' => Text::_('EB_EXPORT'),
						'footer' => $this->loadTemplate('batch_export_footer'),
					],
					$this->loadTemplate('batch_export_body')
				);
			}
		?>
	</div>
	<?php $this->renderFormHiddenVariables(); ?>
</form>
<?php
	if (!$this->hasFilterFields)
	{
	?>
		<form action="index.php?option=com_eventbooking&view=registrants" method="post" name="registrantsExportForm" id="registrantsExportForm">
			<input type="hidden" name="task" value=""/>
			<input type="hidden" id="export_filter_search" name="filter_search"/>
			<input type="hidden" id="export_filter_from_date" name="filter_from_date" value="">
			<input type="hidden" id="export_filter_to_date" name="filter_to_date" value="">
			<input type="hidden" id="export_filter_event_id" name="filter_event_id" value="">
			<input type="hidden" id="export_filter_published" name="filter_published" value="">
			<input type="hidden" id="export_cid" name="cid" value="">
			<?php
			if ($this->config->activate_checkin_registrants)
			{
			?>
				<input type="hidden" id="export_filter_checked_in" name="filter_checked_in"  value="">
			<?php
			}
			?>
			<input type="hidden" name="filter_order" value="<?php echo $this->state->filter_order; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $this->state->filter_order_Dir; ?>" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php
	}
?>
