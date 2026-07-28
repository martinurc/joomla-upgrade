<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

return [
	'admin_cancel_registration_notification_email_subject' => 'Registration #[ID] from [FIRST_NAME] [LAST_NAME] for [EVENT_TITLE] cancelled notification',
	'admin_cancel_registration_notification_email_body'    => '<p>Dear administrator</p>
<p>The registration #<strong>[ID]</strong> from <strong>[FIRST_NAME] [LAST_NAME]</strong> for <strong>[EVENT_TITLE]</strong> has just been cancelled by  administrator <strong>[CANCELLED_BY_USER_NAME]</strong> (<strong>[CANCELLED_BY_USER_USERNAME]</strong>).</p>
<p>Regards,</p>
<p>Administrator Team</p>',
	'new_registration_admin_sms'                           => 'User [FIRST_NAME] [LAST_NAME] registered for your event [EVENT_TITLE].',
	'first_reminder_sms'                                   => 'First Reminder: Please remember to attend the event [EVENT_TITLE] on [EVENT_DATE]',
	'second_reminder_sms'                                  => 'Second Reminder: Please remember to attend the event [EVENT_TITLE] on [EVENT_DATE]',
	'registration_cancel_confirmation_message'             => '<p>Please click on the Process button below to cancel your registration for event [EVENT_TITLE].</p>',
	'registration_cancel_confirmation_email_subject'       => 'Your registration for event [EVENT_TITLE] was cancelled',
	'registration_cancel_confirmation_email_body'          => '<p>You just cancelled your registration for event [EVENT_TITLE]</p><p>Regards,</p><p>Event Registration Team</p>',
	'offline_payment_reminder_email_subject'               => 'Offline Payment Reminder for event [EVENT_TITLE] registration',
	'offline_payment_reminder_email_body'                  => '<p>Dear <strong>[FIRST_NAME], [LAST_NAME]</strong></p>
<p>You registered for our event <strong>[EVENT_TITLE]</strong> using offline payment method but has not made payment yet. The payment amount is <strong>[AMOUNT]. </strong></p>
<p>Please send the offline payment via our bank account. Information of our bank account is as follow :</p>
<p><strong style="font-size: 12.1599998474121px; line-height: 15.8079996109009px;">Account Holder Name, Bank Name, Account Number XXXYYYZZZZ</strong></p>
<p>Regards,</p>
<p>Website Administrator Team</p>',
	'event_cancel_email_subject'                           => 'We will cancel event [EVENT_TITLE]',
	'event_cancel_email_body'                              => '<p>Dear [FIRST_NAME] [LAST_NAME]</p>
<p>We want to inform you that we will cancel the event [EVENT_TITLE] [EVENT_DATE]</p>
<p>We are sorry for the inconvenience</p>
<p>Regards,</p>
<p>Website administrator team</p>',
	'waiting_list_cancel_confirmation_message'             => '<p>Please click on the Process button below to cancel your waiting list for event [EVENT_TITLE].</p>',
	'waiting_list_cancel_complete_message'                 => '<p>You have just cancel your waiting list for event [EVENT_TITLE]</p>
<p>Regards,</p>
<p>Events Management Team</p>',
	'waiting_list_cancel_confirmation_email_subject'       => 'Your waiting list for [EVENT_TITLE] cancelled',
	'waiting_list_cancel_confirmation_email_body'          => '<p>Your waiting list for event <strong>[EVENT_TITLE]</strong> has successfully cancelled.</p>
<p>Regards,</p>
<p>Events Management Team</p>',
	'waiting_list_cancel_notification_email_subject'       => '[FIRST_NAME] [LAST_NAME] cancel waiting list for [EVENT_TITLE]',
	'waiting_list_cancel_notification_email_body'          => '<p>Dear administrator</p>
<p>User <strong>[FIRST_NAME] [LAST_NAME]</strong> has just cancel their waiting list for event <strong>[EVENT_TITLE]</strong> </p>
<p>Regards,</p>
<p>Events Management Team</p>',
	'admin_registration_approved_email_body'               => '<p>Dear administrator</p>
<p>The registration of <strong>[FIRST_NAME] [LAST_NAME]</strong> for event <strong>[EVENT_TITLE]</strong> has just approved by  <strong>[APPROVAL_USER_USERNAME]</strong>. The registration details is as follow:</p>
<p>[REGISTRATION_DETAIL]</p>
<p>Regards,</p>
<p>Events Management Team</p>',
	'send_registrants_list_email_subject'                  => 'Registrants list of your event [EVENT_TITLE] [EVENT_DATE]',
	'send_registrants_list_email_body'                     => '<p>Dear administrator</p>
<p>Please get the registrants list of your event [EVENT_TITLE] [EVENT_DATE] in the attachment of this email.</p>
<p>Regards,</p>
<p>Events Management Team</p>',
];