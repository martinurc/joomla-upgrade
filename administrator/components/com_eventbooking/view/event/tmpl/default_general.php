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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var \Joomla\CMS\Editor\Editor $editor
 */

EventbookingHelper::normalizeNullDateTimeData($this->item, ['event_date', 'late_fee_date', 'registration_start_date', 'cut_off_date']);

Factory::getApplication()->getDocument()->addScript(Uri::root(true) . '/media/com_eventbooking/assets/js/eventbookingjq.min.js');

EventbookingHelperJquery::colorbox('eb-colorbox-addlocation');
?>
<fieldset class="form-horizontal options-form eb-event-generate-settings">
	<legend><?php echo Text::_('EB_EVENT_DETAIL'); ?></legend>
	<div class="control-group">
		<div class="control-label"><?php echo Text::_('EB_TITLE'); ?></div>
		<div class="controls">
			<input type="text" name="title" value="<?php echo $this->escape($this->item->title); ?>" class="input-xlarge form-control w-100" size="70"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label"><?php echo Text::_('EB_ALIAS'); ?></div>
		<div class="controls">
			<input type="text" name="alias" value="<?php echo $this->item->alias; ?>" class="input-xlarge form-control w-100" size="70"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label"><?php echo Text::_('EB_MAIN_EVENT_CATEGORY'); ?></div>
		<div class="controls">
			<?php echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['main_category_id'], Text::_('EB_TYPE_OR_SELECT_ONE_CATEGORY')); ?>
		</div>
	</div>
	<?php
	if ($this->config->get('bes_show_additional_categories', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_ADDITIONAL_CATEGORIES'); ?>
			</div>
			<div class="controls">
				<?php echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['category_id'], Text::_('EB_TYPE_OR_SELECT_SOME_CATEGORIES')); ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_thumb_image', 1))
	{
		if (EventbookingHelper::useStipEasyImage())
		{
			echo EventbookingHelperHtml::getMediaInput($this->item->image, 'image');
		}
		else
		{
		?>
			<div class="control-group">
				<div class="control-label"><?php echo Text::_('EB_IMAGE'); ?></div>
				<div class="controls">
					<?php echo EventbookingHelperHtml::getMediaInput($this->item->image, 'image'); ?>
					<input type="hidden" name="thumb" value="<?php echo $this->item->thumb; ?>" />
				</div>
			</div>
		<?php
		}
	}

	if ($this->config->get('bes_show_image_alt', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_IMAGE_ALT'); ?>
			</div>
			<div class="controls">
				<input type="text" class="form-control" size="10" name="image_alt" value="<?php echo $this->escape($this->item->image_alt); ?>"/>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_location', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label"><?php echo Text::_('EB_LOCATION'); ?></div>
			<div class="controls">
				<?php
				echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['location_id']);

				if ($this->config->get('map_provider', 'googlemap') === 'googlemap')
				{
				?>
					<button type="button" class="btn btn-small btn-success eb-colorbox-addlocation" href="<?php echo Route::_('index.php?option=com_eventbooking&view=location&layout=popup&tmpl=component')?>"><span class="icon-new icon-white"></span><?php echo Text::_('EB_ADD_NEW_LOCATION') ; ?></button>
				<?php
				}
				?>
			</div>
		</div>
	<?php
	}
	?>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_EVENT_START_DATE'); ?>
		</div>
		<div class="controls eb-date-time-container md-flex d-flex justify-content-start">
			<?php echo HTMLHelper::_('calendar', $this->item->event_date, 'event_date', 'event_date', $this->datePickerFormat, ['class' => 'input-medium']); ?>
			<?php echo $this->lists['event_date_hour'] . ' ' . $this->lists['event_date_minute']; ?>
		</div>
	</div>
	<?php
	if ($this->config->get('bes_show_event_end_date', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_EVENT_END_DATE'); ?>
			</div>
			<div class="controls eb-date-time-container d-flex justify-content-start">
				<?php echo HTMLHelper::_('calendar', $this->item->event_end_date, 'event_end_date', 'event_end_date', $this->datePickerFormat, ['class' => 'input-medium']); ?>
				<?php echo $this->lists['event_end_date_hour'] . ' ' . $this->lists['event_end_date_minute']; ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_registration_start_date', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_REGISTRATION_START_DATE'); ?>
			</div>
			<div class="controls eb-date-time-container d-flex justify-content-start">
				<?php echo HTMLHelper::_('calendar', $this->item->registration_start_date, 'registration_start_date', 'registration_start_date', $this->datePickerFormat, ['class' => 'input-medium']); ?>
				<?php echo $this->lists['registration_start_hour'] . ' ' . $this->lists['registration_start_minute']; ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_cut_off_date', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('cut_off_date', Text::_('EB_CUT_OFF_DATE'), Text::_('EB_CUT_OFF_DATE_EXPLAIN')); ?>
			</div>
			<div class="controls eb-date-time-container d-flex justify-content-start">
				<?php echo HTMLHelper::_('calendar', $this->item->cut_off_date, 'cut_off_date', 'cut_off_date', $this->datePickerFormat, ['class' => 'input-medium']); ?>
				<?php echo $this->lists['cut_off_hour'] . ' ' . $this->lists['cut_off_minute']; ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_price', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_PRICE'); ?>
			</div>
			<div class="controls">
				<input type="number" min="0" step="0.01" name="individual_price" id="individual_price" class="form-control input-medium" size="10" value="<?php echo $this->item->individual_price; ?>"/>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_price_text', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('price_text', Text::_('EB_PRICE_TEXT'), Text::_('EB_PRICE_TEXT_EXPLAIN')); ?>
			</div>
			<div class="controls">
				<input type="text" name="price_text" id="price_text" class="input-xxlarge form-control" value="<?php echo $this->escape($this->item->price_text); ?>"/>
			</div>
		</div>
	<?php
	}

	if ($this->config->activate_simple_tax)
	{
	?>
        <div class="control-group">
            <div class="control-label">
				<?php echo Text::_('EB_TAX_RATE'); ?>
            </div>
            <div class="controls">
                <input type="number" min="0" step="0.01" name="tax_rate" id="tax_rate" class="form-control input-medium" size="10" value="<?php echo $this->item->tax_rate; ?>"/>
            </div>
        </div>
	<?php
	}

	if ($this->config->get('bes_show_capacity', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('event_capacity', Text::_('EB_EVENT_CAPACITY'), Text::_('EB_CAPACITY_EXPLAIN')); ?>
			</div>
			<div class="controls">
				<input type="number" step="1" min="0" name="event_capacity" id="event_capacity" class="input-medium form-control" size="10" value="<?php echo $this->item->event_capacity; ?>"/>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_private_booking_count', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('private_booking_count', Text::_('EB_PRIVATE_BOOKING_COUNT'), Text::_('EB_PRIVATE_BOOKING_COUNT_EXPLAIN')); ?>
			</div>
			<div class="controls">
				<input type="number" step="1" min="0" name="private_booking_count" id="private_booking_count" class="input-medium form-control" size="10" value="<?php echo $this->item->private_booking_count; ?>"/>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_waiting_list_capacity', 0))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('waiting_list_capacity', Text::_('EB_WAITING_LIST_CAPACITY'), Text::_('EB_WAITING_LIST_CAPACITY_EXPLAIN')); ?>
			</div>
			<div class="controls">
				<input type="number" step="1" min="0" name="waiting_list_capacity" id="waiting_list_capacity" class="input-medium form-control" size="10" value="<?php echo $this->item->waiting_list_capacity; ?>"/>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_registration_type', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label"><?php echo Text::_('EB_REGISTRATION_TYPE'); ?></div>
			<div class="controls">
				<?php echo $this->lists['registration_type']; ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_custom_registration_handle_url', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('registration_handle_url', Text::_('EB_CUSTOM_REGISTRATION_HANDLE_URL'), Text::_('EB_CUSTOM_REGISTRATION_HANDLE_URL_EXPLAIN')); ?>
			</div>
			<div class="controls">
				<input type="url" name="registration_handle_url" id="registration_handle_url"
					   class="input-xxlarge form-control" size="10"
					   value="<?php echo $this->item->registration_handle_url; ?>"/>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_event_detail_url', 0))
	{
	?>
		<div class="control-group">
			<div class="control-label">
                <?php echo EventbookingHelperHtml::getFieldLabel('event_detail_url', Text::_('EB_CUSTOM_EVENT_DETAIL_URL'), Text::_('EB_CUSTOM_EVENT_DETAIL_URL_EXPLAIN')); ?>
			</div>
			<div class="controls">
				<input type="url" name="event_detail_url" id="event_detail_url" class="input-xxlarge form-control" size="10" value="<?php echo $this->item->event_detail_url; ?>"/>
			</div>
		</div>
    <?php
	}

	if ($this->config->get('bes_show_attachment', 1) && !PluginHelper::isEnabled('eventbooking', 'attachments'))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo EventbookingHelperHtml::getFieldLabel('attachment', Text::_('EB_ATTACHMENT'), Text::_('EB_ATTACHMENT_EXPLAIN')); ?>
			</div>
			<div class="controls">
				<input type="file" name="attachment" />
				<?php
				echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['available_attachment']);

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

	if ($this->config->get('bes_show_send_first_reminder', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_SEND_FIRST_REMINDER'); ?>
			</div>
			<div class="controls">
				<input type="number" min="0" class="input-small form-control d-inline-block" name="send_first_reminder" value="<?php echo $this->item->send_first_reminder; ?>" size="5" /><span><?php echo ' ' . $this->lists['first_reminder_frequency'] . ' ' . $this->lists['send_first_reminder_time']; ?></span><?php echo Text::_('EB_EVENT_STARTED'); ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_send_second_reminder', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_SEND_SECOND_REMINDER'); ?>
			</div>
			<div class="controls">
				<input type="number" min="0" class="input-small form-control d-inline-block" name="send_second_reminder" value="<?php echo $this->item->send_second_reminder; ?>" size="5" /><span><?php echo ' ' . $this->lists['second_reminder_frequency'] . ' ' . $this->lists['send_second_reminder_time']; ?></span><?php echo Text::_('EB_EVENT_STARTED'); ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_send_third_reminder', 1))
	{
	?>
        <div class="control-group">
            <div class="control-label">
				<?php echo  Text::_('EB_SEND_THIRD_REMINDER'); ?>
            </div>
            <div class="controls">
                <input type="number" min="0" class="input-small form-control d-inline-block" name="send_third_reminder" value="<?php echo $this->item->send_third_reminder; ?>" size="5" /><span><?php echo ' ' . $this->lists['third_reminder_frequency'] . ' ' . $this->lists['send_third_reminder_time']; ?></span><?php echo Text::_('EB_EVENT_STARTED'); ?>
            </div>
        </div>
	<?php
	}

	if (property_exists($this->item, 'send_fourth_reminder'))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_SEND_FOURTH_REMINDER'); ?>
			</div>
			<div class="controls">
				<input type="number" min="0" class="input-small form-control d-inline-block" name="send_fourth_reminder" value="<?php echo $this->item->send_fourth_reminder; ?>" size="5" /><span><?php echo ' ' . $this->lists['fourth_reminder_frequency'] . ' ' . $this->lists['send_fourth_reminder_time']; ?></span><?php echo Text::_('EB_EVENT_STARTED'); ?>
			</div>
		</div>
	<?php
	}

	if (property_exists($this->item, 'send_fifth_reminder'))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_SEND_FIFTH_REMINDER'); ?>
			</div>
			<div class="controls">
				<input type="number" min="0" class="input-small form-control d-inline-block" name="send_fifth_reminder" value="<?php echo $this->item->send_fifth_reminder; ?>" size="5" /><span><?php echo ' ' . $this->lists['fifth_reminder_frequency'] . ' ' . $this->lists['send_fifth_reminder_time']; ?></span><?php echo Text::_('EB_EVENT_STARTED'); ?>
			</div>
		</div>
	<?php
	}

	if (property_exists($this->item, 'send_sixth_reminder'))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_SEND_SIXTH_REMINDER'); ?>
			</div>
			<div class="controls">
				<input type="number" min="0" class="input-small form-control d-inline-block" name="send_sixth_reminder" value="<?php echo $this->item->send_sixth_reminder; ?>" size="5" /><span><?php echo ' ' . $this->lists['sixth_reminder_frequency'] . ' ' . $this->lists['send_sixth_reminder_time']; ?></span><?php echo Text::_('EB_EVENT_STARTED'); ?>
			</div>
		</div>
	<?php
	}

	if (PluginHelper::isEnabled('system', 'eventbookingsms'))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_ENABLE_SMS'); ?>
			</div>
			<div class="controls">
				<?php echo EventbookingHelperHtml::getBooleanInput('enable_sms_reminder', $this->item->enable_sms_reminder); ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_short_description', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_SHORT_DESCRIPTION'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('short_description', $this->item->short_description, '100%', '400', '90', '6'); ?>
			</div>
		</div>
	<?php
	}

	if ($this->config->get('bes_show_description', 1))
	{
	?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_DESCRIPTION'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('description', $this->item->description, '100%', '400', '90', '10'); ?>
			</div>
		</div>
	<?php
	}
	?>
</fieldset>
