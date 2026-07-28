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
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['html' => true, 'sanitize' => false]);
$document = Factory::getApplication()->getDocument();
$document->addStyleDeclaration('.hasTip{display:block !important}');

$translatable = Multilanguage::isEnabled() && count($this->languages);
$editor = Editor::getInstance(Factory::getApplication()->get('editor'));
$fields = EventbookingHelperHtml::getAvailableMessagesTags();

if (EventbookingHelper::isJoomla4())
{
	$tabApiPrefix = 'uitab.';
}
else
{
	HTMLHelper::_('behavior.tabstate');

	$tabApiPrefix = 'bootstrap.';
}
?>
<form action="index.php?option=com_eventbooking&view=message" method="post" name="adminForm" id="adminForm" class="form-horizontal eb-configuration<?php if (!EventbookingHelper::isJoomla4()) echo ' joomla3'; ?>">
	<?php echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'message', ['active' => 'registration-form-messages-page', 'recall' => true]); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'registration-form-messages-page', Text::_('EB_REGISTRATION_FORM_MESSAGES')); ?>
	<?php echo $this->loadTemplate('registration_form', ['editor' => $editor, 'fields' => $fields]); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'endTab'); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'registration-email-messages-page', Text::_('EB_REGISTRATION_EMAIL_MESSAGES')); ?>
	<?php echo $this->loadTemplate('registration_email', ['editor' => $editor, 'fields' => $fields]); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'endTab'); ?>

	<?php echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'reminder-messages-page', Text::_('EB_REMINDER_MESSAGES')); ?>
	<?php echo $this->loadTemplate('reminder_messages', ['editor' => $editor, 'fields' => $fields]); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'endTab'); ?>

	<?php echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'registration-cancel-messages-page', Text::_('EB_REGISTRATION_CANCEL_MESSAGES')); ?>
	<?php echo $this->loadTemplate('registration_cancel', ['editor' => $editor, 'fields' => $fields]); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'endTab'); ?>

	<?php echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'submit-event-email-messages-page', Text::_('EB_SUBMIT_EVENT_EMAIL_MESSAGES')); ?>
		<?php echo $this->loadTemplate('submit_event_email', ['editor' => $editor, 'fields' => $fields]); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'endTab');?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'invitation-messages-page', Text::_('EB_INVITATION_MESSAGES')); ?>
		<?php echo $this->loadTemplate('invitation_message', ['editor' => $editor, 'fields' => $fields]); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'endTab'); ?>
	<?php echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'waitinglist-messages-page', Text::_('EB_WAITING_LIST_MESSAGES')); ?>
	<?php echo $this->loadTemplate('waitinglist_message', ['editor' => $editor, 'fields' => $fields]); ?>
	<?php

	echo HTMLHelper::_($tabApiPrefix . 'endTab');
	echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'pay-deposit-form-messages-page', Text::_('EB_DEPOSIT_PAYMENT_MESSAGES'));
	echo $this->loadTemplate('remainder_payment', ['editor' => $editor, 'fields' => $fields]);
	echo HTMLHelper::_($tabApiPrefix . 'endTab');

	echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'sms-page', Text::_('EB_SMS_MESSAGES'));
	echo $this->loadTemplate('sms');
	echo HTMLHelper::_($tabApiPrefix . 'endTab');

	// Add support for custom settings layout
	if (file_exists(__DIR__ . '/default_custom_settings.php'))
	{
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'custom-settings-page', Text::_('EB_MESSAGE_CUSTOM_SETTINGS'));
		echo $this->loadTemplate('custom_settings', ['editor' => $editor, 'fields' => $fields]);
		echo HTMLHelper::_($tabApiPrefix . 'endTab');
	}

	if ($translatable)
	{
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'message', 'translation-page', Text::_('EB_TRANSLATION'));
		echo $this->loadTemplate('translation', ['editor' => $editor, 'fields' => $fields]);
		echo HTMLHelper::_($tabApiPrefix . 'endTab');
	}
	echo HTMLHelper::_($tabApiPrefix . 'endTabSet');
	?>
	<div class="clearfix"></div>
	<input type="hidden" name="task" value="" />
</form>