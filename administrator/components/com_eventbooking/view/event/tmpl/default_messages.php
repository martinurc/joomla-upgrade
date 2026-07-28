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
 * @var \Joomla\CMS\Editor\Editor $editor
 */
?>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('registration_form_message', Text::_('EB_REGISTRATION_FORM_MESSAGE'), Text::_('EB_AVAILABLE_TAGS') . ': [EVENT_TITLE]'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('registration_form_message', $this->item->registration_form_message, '100%', '350', '90', '10') ; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('registration_form_message_group', Text::_('EB_REGISTRATION_FORM_MESSAGE_GROUP'), Text::_('EB_AVAILABLE_TAGS') . ': [EVENT_TITLE]'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('registration_form_message_group', $this->item->registration_form_message_group, '100%', '350', '90', '10') ; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('admin_email_body', Text::_('EB_ADMIN_EMAIL_BODY'), Text::_('EB_AVAILABLE_TAGS') . ': [REGISTRATION_DETAIL], [FIRST_NAME], [LAST_NAME], [ORGANIZATION], [ADDRESS], [ADDRESS2], [CITY], [STATE], [CITY], [ZIP], [COUNTRY], [PHONE], [FAX], [EMAIL], [COMMENT], [AMOUNT]'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('admin_email_body', $this->item->admin_email_body, '100%', '350', '90', '10') ; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('user_email_subject', Text::_('EB_USER_EMAIL_SUBJECT')); ?>
	</div>
	<div class="controls">
		<input type="text" name="user_email_subject" class="input-xxlarge form-control" value="<?php echo $this->item->user_email_subject; ?>" />
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('user_email_body', Text::_('EB_USER_EMAIL_BODY'), Text::_('EB_AVAILABLE_TAGS') . ': [REGISTRATION_DETAIL], [FIRST_NAME], [LAST_NAME], [ORGANIZATION], [ADDRESS], [ADDRESS2], [CITY], [STATE], [CITY], [ZIP], [COUNTRY], [PHONE], [FAX], [EMAIL], [COMMENT], [AMOUNT]'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('user_email_body', $this->item->user_email_body, '100%', '350', '90', '10') ; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('user_email_body_offline', Text::_('EB_USER_EMAIL_BODY_OFFLINE'), Text::_('EB_AVAILABLE_TAGS') . ': [REGISTRATION_DETAIL], [FIRST_NAME], [LAST_NAME], [ORGANIZATION], [ADDRESS], [ADDRESS2], [CITY], [STATE], [CITY], [ZIP], [COUNTRY], [PHONE], [FAX], [EMAIL], [COMMENT], [AMOUNT]'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('user_email_body_offline', $this->item->user_email_body_offline, '100%', '350', '90', '10') ; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('group_member_email_body', Text::_('EB_GROUP_MEMBER_EMAIL_BODY')); ?>
		<p class="eb-available-tags">
			<?php echo Text::_('EB_AVAILABLE_TAGS'); ?>: <strong>[MEMBER_DETAIL], <?php echo EventbookingHelperHtml::getAvailableMessagesTags(false); ?></strong>
		</p>
	</div>
	<div class="controls">
		<?php echo $editor->display('group_member_email_body', $this->item->group_member_email_body, '100%', '350', '75', '8') ;?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo  Text::_('EB_THANK_YOU_MESSAGE'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('thanks_message', $this->item->thanks_message, '100%', '350', '90', '6') ; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo  Text::_('EB_THANK_YOU_MESSAGE_OFFLINE'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('thanks_message_offline', $this->item->thanks_message_offline, '100%', '350', '90', '6') ; ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo  Text::_('EB_REGISTRATION_APPROVED_EMAIL_BODY'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('registration_approved_email_body', $this->item->registration_approved_email_body, '100%', '350', '90', '6') ; ?>
	</div>
</div>
<div class="control-group">
    <div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('reminder_email_subject', Text::_('EB_REMINDER_EMAIL_SUBJECT')); ?>
    </div>
    <div class="controls">
        <input type="text" name="reminder_email_subject" class="input-xxlarge form-control" value="<?php echo $this->item->reminder_email_subject; ?>" />
    </div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo  Text::_('EB_REMINDER_EMAIL_BODY'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('reminder_email_body', $this->item->reminder_email_body, '100%', '350', '90', '6') ; ?>
	</div>
</div>
<div class="control-group">
    <div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('second_reminder_email_subject', Text::_('EB_SECOND_REMINDER_EMAIL_SUBJECT')); ?>
    </div>
    <div class="controls">
        <input type="text" name="second_reminder_email_subject" class="input-xxlarge form-control" value="<?php echo $this->item->second_reminder_email_subject; ?>" />
    </div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo  Text::_('EB_SECOND_REMINDER_EMAIL_BODY'); ?>
	</div>
	<div class="controls">
		<?php echo $editor->display('second_reminder_email_body', $this->item->second_reminder_email_body, '100%', '350', '90', '6') ; ?>
	</div>
</div>
<div class="control-group">
    <div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('third_reminder_email_subject', Text::_('EB_THIRD_REMINDER_EMAIL_SUBJECT')); ?>
    </div>
    <div class="controls">
        <input type="text" name="third_reminder_email_subject" class="input-xxlarge form-control" value="<?php echo $this->item->third_reminder_email_subject; ?>" />
    </div>
</div>
<div class="control-group">
    <div class="control-label">
		<?php echo  Text::_('EB_THIRD_REMINDER_EMAIL_BODY'); ?>
    </div>
    <div class="controls">
		<?php echo $editor->display('third_reminder_email_body', $this->item->third_reminder_email_body, '100%', '350', '90', '6') ; ?>
    </div>
</div>
<?php
if (property_exists($this->item, 'fourth_reminder_email_subject'))
{
?>
	<div class="control-group">
		<div class="control-label">
			<?php echo EventbookingHelperHtml::getFieldLabel('fourth_reminder_email_subject', Text::_('EB_FOURTH_REMINDER_EMAIL_SUBJECT')); ?>
		</div>
		<div class="controls">
			<input type="text" name="fourth_reminder_email_subject" class="input-xxlarge form-control" value="<?php echo $this->item->fourth_reminder_email_subject; ?>" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_FOURTH_REMINDER_EMAIL_BODY'); ?>
		</div>
		<div class="controls">
			<?php echo $editor->display('fourth_reminder_email_body', $this->item->fourth_reminder_email_body, '100%', '350', '90', '6') ; ?>
		</div>
	</div>
<?php
}

if (property_exists($this->item, 'fifth_reminder_email_subject'))
{
?>
	<div class="control-group">
		<div class="control-label">
			<?php echo EventbookingHelperHtml::getFieldLabel('fifth_reminder_email_subject', Text::_('EB_FIFTH_REMINDER_EMAIL_SUBJECT')); ?>
		</div>
		<div class="controls">
			<input type="text" name="fifth_reminder_email_subject" class="input-xxlarge form-control" value="<?php echo $this->item->fifth_reminder_email_subject; ?>" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_FIFTH_REMINDER_EMAIL_BODY'); ?>
		</div>
		<div class="controls">
			<?php echo $editor->display('fifth_reminder_email_body', $this->item->fifth_reminder_email_body, '100%', '350', '90', '6') ; ?>
		</div>
	</div>
<?php
}

if (property_exists($this->item, 'sixth_reminder_email_subject'))
{
?>
	<div class="control-group">
		<div class="control-label">
			<?php echo EventbookingHelperHtml::getFieldLabel('sixth_reminder_email_subject', Text::_('EB_SIXTH_REMINDER_EMAIL_SUBJECT')); ?>
		</div>
		<div class="controls">
			<input type="text" name="sixth_reminder_email_subject" class="input-xxlarge form-control" value="<?php echo $this->item->sixth_reminder_email_subject; ?>" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_SIXTH_REMINDER_EMAIL_BODY'); ?>
		</div>
		<div class="controls">
			<?php echo $editor->display('sixth_reminder_email_body', $this->item->sixth_reminder_email_body, '100%', '350', '90', '6') ; ?>
		</div>
	</div>
<?php
}

