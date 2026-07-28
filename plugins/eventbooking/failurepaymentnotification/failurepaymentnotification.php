<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2021 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;

class plgEventbookingFailurePaymentNotification extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Send notification emails when payment failure
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   string                       $reason
	 */
	public function onAfterEBPaymentFailure($row, $reason)
	{
		// Should we send notification
		if (!$this->needToSendNotification())
		{
			return;
		}

		$notificationEmails = trim($this->params->get('notification_emails', ''));
		$subject            = $this->params->get('subject');
		$message            = $this->params->get('message');

		// Get list of emails which will receive notification
		$config = EventbookingHelper::getConfig();

		if (!$notificationEmails)
		{
			// Try to get emails from event
			$event = EventbookingHelperDatabase::getEvent($row->event_id);

			if (strlen(trim($event->notification_emails)) > 0)
			{
				$notificationEmails = $event->notification_emails;
			}
			elseif ($config->notification_emails)
			{
				$notificationEmails = $config->notification_emails;
			}
			else
			{
				$notificationEmails = $this->app->get('mailfrom');
			}
		}

		// If no notification emails set for some reasons, return early
		if (!$notificationEmails)
		{
			return;
		}

		$notificationEmails = array_map('trim', explode(',', $notificationEmails));


		$config = EventbookingHelper::getConfig();

		// Build tags which can be used in the email which contains event information
		$replaces           = EventbookingHelperRegistration::getRegistrationReplaces($row);
		$replaces['reason'] = $reason;

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$message = EventbookingHelper::replaceCaseInsensitiveTags($message, $replaces);
		$message = EventbookingHelper::convertImgTags($message);

		$mailer    = EventbookingHelperMail::getMailer($config);
		$logEmails = EventbookingHelperMail::loggingEnabled('new_event_notification_emails', $config);

		EventbookingHelperMail::send(
			$mailer,
			$notificationEmails,
			$subject,
			$message,
			$logEmails,
			1,
			'failure_payment_emails'
		);
	}

	/**
	 * Method to check if we should send notification when event is created
	 * depends on app parameter configured in the plugin
	 *
	 * @return bool
	 */
	private function needToSendNotification()
	{
		// If required parameters are not entered, do not send emails
		if (!$this->params->get('subject') || !$this->params->get('message'))
		{
			return false;
		}

		return true;
	}
}
