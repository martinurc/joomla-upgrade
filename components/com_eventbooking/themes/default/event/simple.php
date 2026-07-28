<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');

if (EventbookingHelper::isJoomla4())
{
	EventbookingHelperJquery::colorbox('a.modal');
}
else
{
	HTMLHelper::_('behavior.modal');
}

$user = Factory::getUser();

$editor = Editor::getInstance(Factory::getApplication()->get('editor', 'none'));
EventbookingHelperJquery::validateForm();

$bootstrapHelper   = EventbookingHelperBootstrap::getInstance();
$controlGroupClass = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass = $bootstrapHelper->getClassMapping('control-label');
$controlsClass     = $bootstrapHelper->getClassMapping('controls');
$iconCalendar      = $bootstrapHelper->getClassMapping('icon-calendar');
$rowFluidClass     = $bootstrapHelper->getClassMapping('row-fluid');
$btnPrimary        = $bootstrapHelper->getClassMapping('btn btn-primary');
$formHorizontal    = $bootstrapHelper->getClassMapping('form form-horizontal');

$dateFields = [
	'event_date',
	'event_end_date',
	'registration_start_date',
	'cut_off_date',
];

foreach ($dateFields as $dateField)
{
	if ((int) $this->item->{$dateField})
	{
		continue;
	}

	$this->item->{$dateField} = '';
}

$useTabs = $this->isMultilingual || count($this->plugins);

EventbookingHelperHtml::addOverridableScript('media/com_eventbooking/js/site-event-simple.min.js');

$message     = EventbookingHelper::getMessages();
$fieldSuffix = EventbookingHelper::getFieldSuffix();

$toolbarButtons = Toolbar::getInstance('toolbar')->render();

if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'submit_event_form_message' . $fieldSuffix}))
{
	$msg = $message->{'submit_event_form_message' . $fieldSuffix};
}
elseif(EventbookingHelper::isValidMessage($message->submit_event_form_message))
{
	$msg = $message->submit_event_form_message;
}
else
{
	$msg = '';
}
?>
<h1 class="eb-page-heading"><?php echo $this->escape(Text::_('EB_ADD_EDIT_EVENT')); ?></h1>
<?php
if ($msg)
{
?>
	<div class="<?php echo $rowFluidClass; ?> eb-submit-event-form-message">
		<?php echo $msg; ?>
	</div>
<?php
}
?>
<div id="eb-submit-event-simple" class="<?php echo $rowFluidClass; ?> eb-container">
    <?php
	if (in_array($this->config->get('submit_event_toolbar', 'top'), ['top', 'both']))
	{
	?>
        <div class="btn-toolbar" id="btn-toolbar">
		    <?php echo $toolbarButtons ; ?>
        </div>
	<?php
	}
	?>
	<form action="<?php echo Route::_('index.php?option=com_eventbooking&view=events&Itemid=' . $this->Itemid); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data" class="<?php echo $formHorizontal; ?>">
		<div class="clearfix"></div>
		<?php
		if ($useTabs)
		{
			echo HTMLHelper::_('bootstrap.startTabSet', 'event', ['active' => 'basic-information-page', 'recall' => true]);
			echo HTMLHelper::_('bootstrap.addTab', 'event', 'basic-information-page', Text::_('EB_BASIC_INFORMATION'));
		}

		if (EventbookingHelper::isValidMessage($this->params->get('intro_text')))
		{
		?>
            <div class="eb-submit-event-intro-text <?php echo $bootstrapHelper->getClassMapping('control-group clearfix'); ?>">
				<?php echo $this->params->get('intro_text'); ?>
            </div>
		<?php
		}
		?>
		<div class="<?php echo $controlGroupClass;  ?>">
			<div class="<?php echo $controlLabelClass; ?>"><?php echo Text::_('EB_TITLE') ; ?></div>
			<div class="<?php echo $controlsClass; ?>">
				<input type="text" name="title" value="<?php echo $this->escape($this->item->title); ?>" class="validate[required] input-xlarge form-control" size="70" />
			</div>
		</div>
		<?php
		if ($this->config->get('fes_show_alias', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>"><?php echo Text::_('EB_ALIAS') ; ?></div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="text" name="alias" value="<?php echo $this->item->alias; ?>" class="input-xlarge form-control" size="70" />
				</div>
			</div>
		<?php
		}
		?>
		<div class="<?php echo $controlGroupClass;  ?>">
			<div class="<?php echo $controlLabelClass; ?>"><?php echo Text::_('EB_MAIN_EVENT_CATEGORY') ; ?></div>
			<div class="<?php echo $controlsClass; ?>">
				<?php echo $this->lists['main_category_id'] ; ?>
			</div>
		</div>
		<?php
		if ($this->config->get('fes_show_additional_categories', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>"><?php echo Text::_('EB_ADDITIONAL_CATEGORIES') ; ?></div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['category_id'] ; ?>
					<?php echo '      ' . Text::_('EB_SELECT_MULTIPLE_CATEGORIES'); ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_thumb_image', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>"><?php echo Text::_('EB_THUMB_IMAGE') ; ?></div>
				<div class="<?php echo $controlsClass; ?>">
					<?php
						if ($this->config->get('use_media_manager')
						&& ($user->authorise('core.manage', 'com_media') || $user->authorise('core.create', 'com_media')))
						{
							echo EventbookingHelperHtml::getMediaInput($this->item->image, 'image');
						?>
							<input type="hidden" name="use_media_manager" value="1" />
						<?php
						}
						else
						{
						?>
							<input type="file" class="form-control" name="thumb_image" size="60" />
							<?php
							if ($this->item->thumb && file_exists(JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $this->item->thumb))
							{
								$baseUri = Uri::base(true);

								if ($this->item->image && file_exists(JPATH_ROOT . '/' . $this->item->image))
								{
									$largeImageUri = $baseUri . '/' . $this->item->image;
								}
								elseif (file_exists(JPATH_ROOT . '/media/com_eventbooking/images/' . $this->item->thumb))
								{
									$largeImageUri = $baseUri . '/media/com_eventbooking/images/' . $this->item->thumb;
								}
								else
								{
									$largeImageUri = $baseUri . '/media/com_eventbooking/images/thumbs/' . $this->item->thumb;
								}
								?>
								<a href="<?php echo $largeImageUri; ?>" class="modal"><img src="<?php echo $baseUri . '/media/com_eventbooking/images/thumbs/' . $this->item->thumb; ?>" class="img_preview" /></a>
								<input type="checkbox" name="del_thumb" value="1" /><?php echo Text::_('EB_DELETE_CURRENT_THUMB'); ?>
								<?php
							}
							?>
						<?php
						}
					?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_image_alt', 0))
		{
		?>
			<div class="<?php echo $controlGroupClass;?>">
				<div class="<?php echo $controlLabelClass; ?>"><?php echo Text::_('EB_IMAGE_ALT') ; ?></div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="text" name="image_alt" value="<?php echo $this->item->image_alt; ?>" class="input-xlarge form-control" size="70" />
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_location', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>"><?php echo Text::_('EB_LOCATION') ; ?></div>
				<div class="<?php echo $controlsClass; ?>">
					<?php
					echo $this->lists['location_id'] ;

					if ($user->authorise('eventbooking.addlocation', 'com_eventbooking'))
					{
					?>
						<button type="button" class="btn btn-small btn-success eb-colorbox-addlocation" href="<?php echo Route::_('index.php?option=com_eventbooking&view=location&layout=popup&tmpl=component&Itemid=' . $this->Itemid)?>"><span class="icon-new icon-white"></span><?php echo Text::_('EB_ADD_NEW_LOCATION') ; ?></button>
					<?php
					}
					?>
				</div>
			</div>
		<?php
		}
		?>
		<div class="<?php echo $controlGroupClass;  ?>">
			<div class="<?php echo $controlLabelClass; ?>">
				<?php echo Text::_('EB_EVENT_START_DATE'); ?>
			</div>
			<div class="<?php echo $controlsClass; ?>">
				<?php echo str_replace('icon-calendar', $iconCalendar, HTMLHelper::_('calendar', $this->item->event_date, 'event_date', 'event_date', $this->datePickerFormat, ['class' =>  'validate[required]'])); ?>
				<?php echo $this->lists['event_date_hour'] . ' ' . $this->lists['event_date_minute']; ?>
			</div>
		</div>
		<?php
		if ($this->config->get('fes_show_event_end_date'))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_EVENT_END_DATE'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo str_replace('icon-calendar', $iconCalendar, HTMLHelper::_('calendar', $this->item->event_end_date, 'event_end_date', 'event_end_date', $this->datePickerFormat)); ?>
					<?php echo $this->lists['event_end_date_hour'] . ' ' . $this->lists['event_end_date_minute'] ; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_registration_start_date'))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_REGISTRATION_START_DATE'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo str_replace('icon-calendar', $iconCalendar, HTMLHelper::_('calendar', $this->item->registration_start_date, 'registration_start_date', 'registration_start_date', $this->datePickerFormat)) ; ?>
					<?php echo $this->lists['registration_start_hour'] . ' ' . $this->lists['registration_start_minute'] ; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_cut_off_date'))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('cut_off_date', Text::_('EB_CUT_OFF_DATE'), Text::_('EB_CUT_OFF_DATE_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo str_replace('icon-calendar', $iconCalendar, HTMLHelper::_('calendar', $this->item->cut_off_date, 'cut_off_date', 'cut_off_date', $this->datePickerFormat)) ; ?>
					<?php echo $this->lists['cut_off_hour'] . ' ' . $this->lists['cut_off_minute'] ; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_publish_up', 0))
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_PUBLISH_UP'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo HTMLHelper::_('calendar', $this->item->publish_up, 'publish_up', 'publish_up', $this->datePickerFormat . ' %H:%M:%S', ['class' => 'input-medium']); ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_publish_down', 0))
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_PUBLISH_DOWN'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo HTMLHelper::_('calendar', $this->item->publish_down, 'publish_down', 'publish_down', $this->datePickerFormat . ' %H:%M:%S', ['class' => 'input-medium']); ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_price', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_PRICE'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="number" name="individual_price" id="individual_price" class="input-medium form-control" size="10" value="<?php echo $this->item->individual_price; ?>" />
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_price_text'))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('price_text', Text::_('EB_PRICE_TEXT'), Text::_('EB_PRICE_TEXT_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="text" name="price_text" id="price_text" class="input-xlarge form-control" value="<?php echo $this->escape($this->item->price_text); ?>" />
				</div>
			</div>
		<?php
		}

		if ($this->config->get('activate_simple_tax') && $this->config->get('fes_show_tax_rate', 1))
		{
		?>
            <div class="<?php echo $controlGroupClass;?>">
                <div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_TAX_RATE'); ?>
                </div>
                <div class="<?php echo $controlsClass; ?>">
                    <input type="number" name="tax_rate" id="tax_rate" class="input-medium form-control" size="10" value="<?php echo $this->item->tax_rate; ?>" />
                </div>
            </div>
		<?php
		}

		if ($this->config->get('fes_show_capacity'))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('event_capacity', Text::_('EB_EVENT_CAPACITY'), Text::_('EB_CAPACITY_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="number" min="0" step="1" name="event_capacity" id="event_capacity" class="input-medium form-control" size="10" value="<?php echo $this->item->event_capacity; ?>" />
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_private_booking_count'))
		{
		?>
			<div class="<?php echo $controlGroupClass;?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('private_booking_count', Text::_('EB_PRIVATE_BOOKING_COUNT'), Text::_('EB_PRIVATE_BOOKING_COUNT_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="number" min="0" step="1" name="private_booking_count" id="private_booking_count" class="input-medium form-control" size="10" value="<?php echo $this->item->private_booking_count; ?>" />
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_waiting_list_capacity'))
		{
		?>
			<div class="<?php echo $controlGroupClass;?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('waiting_list_capacity', Text::_('EB_WAITING_LIST_CAPACITY')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="number" min="0" step="1" name="waiting_list_capacity" id="waiting_list_capacity" class="input-medium form-control" size="10" value="<?php echo $this->item->waiting_list_capacity; ?>" />
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_registration_type', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>"><?php echo Text::_('EB_REGISTRATION_TYPE'); ?></div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['registration_type'] ; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_custom_registration_handle_url', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('registration_handle_url', Text::_('EB_CUSTOM_REGISTRATION_HANDLE_URL'), Text::_('EB_CUSTOM_REGISTRATION_HANDLE_URL_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="url" name="registration_handle_url" id="registration_handle_url"
						   class="input-xxlarge form-control" size="10" value="<?php echo $this->item->registration_handle_url; ?>" />
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_attachment', 0))
		{
		?>
			<div class="<?php echo $controlGroupClass;?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('attachment', Text::_('EB_ATTACHMENT'), Text::_('EB_ATTACHMENT_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="file" name="attachment" />
					<?php
					if ($user->authorise('core.admin', 'com_eventbooking'))
					{
						echo $this->lists['available_attachment'];
					}

					if ($this->item->attachment)
					{
						Text::_('EB_CURRENT_ATTACHMENT');

						$attachmentRootLink = Uri::root(true) . '/' . ($this->config->attachments_path ?: 'media/com_eventbooking') . '/';

						$attachments = explode('|', $this->item->attachment);

						for ($i = 0, $n = count($attachments); $i < $n; $i++)
						{
							$attachment = $attachments[$i];

							if ($i > 0)
							{
								echo '<br />';
							}
							?>
							<a href="<?php echo $attachmentRootLink . $attachment; ?>" target="_blank"><?php echo $attachment; ?></a>
							<?php
						}
					?>
						<input type="checkbox" name="del_attachment" value="1"/><?php echo Text::_('EB_DELETE_CURRENT_ATTACHMENT'); ?>
					<?php
					}
					?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_notification_emails', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_NOTIFICATION_EMAILS'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="text" name="notification_emails" class="form-control" size="70" value="<?php echo $this->item->notification_emails ; ?>" />
				</div>
			</div>
		<?php
		}

		if ($this->config->activate_deposit_feature)
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_DEPOSIT_AMOUNT'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="number" name="deposit_amount" id="deposit_amount" class="input-medium form-control d-inline-block" size="5" value="<?php echo $this->item->deposit_amount; ?>"/>&nbsp;&nbsp;<?php echo $this->lists['deposit_type']; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_paypal_email', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_PAYPAL_EMAIL'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="text" name="paypal_email" class="form-control" size="50" value="<?php echo $this->item->paypal_email ; ?>" />
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_members_discount_apply_for'))
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_MEMBERS_DISCOUNT_APPLY_FOR'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['members_discount_apply_for']; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_activate_waiting_list'))
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_ENABLE_WAITING_LIST'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['activate_waiting_list']; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_collect_member_information'))
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_COLLECT_MEMBER_INFORMATION'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['collect_member_information']; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_prevent_duplicate_registration'))
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_PREVENT_DUPLICATE'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['prevent_duplicate_registration']; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_send_emails'))
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_SEND_NOTIFICATION_EMAILS'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['send_emails']; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->event_custom_field)
		{
			foreach ($this->form->getFieldset('basic') as $field)
			{
				$submitEventAccess = $field->getAttribute('submit_event_access');

				if ($submitEventAccess !== null && !in_array($submitEventAccess, $user->getAuthorisedViewLevels()))
				{
					continue;
				}
			?>
				<div class="<?php echo $controlGroupClass;  ?>">
					<div class="<?php echo $controlLabelClass; ?>">
						<?php echo $field->label;?>
					</div>
					<div class="<?php echo $controlsClass; ?>">
						<?php echo $field->input; ?>
					</div>
				</div>
			<?php
			}
		}

		if ($this->config->get('fes_show_event_password', 0))
		{
		?>
			<div class="<?php echo $controlGroupClass; ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('event_password', Text::_('EB_EVENT_PASSWORD'), Text::_('EB_EVENT_PASSWORD_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<input type="text" name="event_password" id="event_password" class="input-small" value="<?php echo $this->item->event_password; ?>"/>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_access', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('access', Text::_('EB_ACCESS'), Text::_('EB_ACCESS_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['access']; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_registration_access', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo EventbookingHelperHtml::getFieldLabel('registration_access', Text::_('EB_REGISTRATION_ACCESS'), Text::_('EB_REGISTRATION_ACCESS_EXPLAIN')); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->lists['registration_access']; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_published', 1) && EventbookingHelperAcl::canChangeEventStatus($this->item->id))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_PUBLISHED'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php
						if (isset($this->lists['published']))
						{
							echo $this->lists['published'];
						}
						else
						{
							echo EventbookingHelperHtml::getBooleanInput('published', $this->item->published);
						}
					?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_short_description', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo  Text::_('EB_SHORT_DESCRIPTION'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $editor->display('short_description', $this->item->short_description, '100%', '400', '90', '6') ; ?>
				</div>
			</div>
		<?php
		}

		if ($this->config->get('fes_show_description', 1))
		{
		?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo  Text::_('EB_DESCRIPTION'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $editor->display('description', $this->item->description, '100%', '400', '90', '10') ; ?>
				</div>
			</div>
		<?php
		}

		if ($this->showCaptcha)
		{
			if (in_array($this->captchaPlugin, ['recaptcha_invisible', 'recaptcha_v3']))
			{
				$style = ' style="display:none;"';
			}
			else
			{
				$style = '';
			}
		?>
			<div class="<?php echo $controlGroupClass;  ?>"<?php echo $style; ?>>
				<div class="<?php echo $controlLabelClass; ?>">
					<?php echo  Text::_('EB_CAPTCHA'); ?>
				</div>
				<div class="<?php echo $controlsClass; ?>">
					<?php echo $this->captcha; ?>
				</div>
			</div>
		<?php
		}

		if ($useTabs)
		{
			echo HTMLHelper::_('bootstrap.endTab');
		}

		if ($this->isMultilingual)
		{
			echo $this->loadTemplate('translation', ['editor' => $editor]);
		}

		if (count($this->plugins))
		{
			$count = 0;

			foreach ($this->plugins as $plugin)
			{
				$count++;
				echo HTMLHelper::_('bootstrap.addTab', 'event', 'tab_' . $count, Text::_($plugin['title']));
				echo $plugin['form'];
				echo HTMLHelper::_('bootstrap.endTab');
			}

		}

		if ($useTabs)
		{
			echo HTMLHelper::_('bootstrap.endTabSet');
		}
		?>
		<input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>"/>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="return" value="<?php echo $this->return; ?>" />
		<input type="hidden" name="activate_tickets_pdf" value="<?php echo $this->item->activate_tickets_pdf; ?>"/>
		<input type="hidden" name="send_tickets_via_email" value="<?php echo empty($this->item->send_tickets_via_email) ? 0 : 1; ?>"/>
		<input type="hidden" name="form_layout" value="form" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<?php
	if (in_array($this->config->get('submit_event_toolbar', 'top'), ['bottom', 'both']))
	{
	?>
        <div class="btn-toolbar" id="btn-toolbar-bottom">
			<?php echo $toolbarButtons; ?>
        </div>
	<?php
	}
	?>
</div>