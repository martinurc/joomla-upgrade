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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailHelper;

class EventbookingModelInvite extends RADModel
{
	/**
	 * Send invitation to users
	 *
	 * @param $data
	 *
	 * @throws Exception
	 */
	public function sendInvite($data)
	{
		$config      = EventbookingHelper::getConfig();
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		if ($config->from_name)
		{
			$fromName = $config->from_name;
		}
		else
		{
			$fromName = Factory::getApplication()->get('fromname');
		}

		if ($config->from_email)
		{
			$fromEmail = $config->from_email;
		}
		else
		{
			$fromEmail = Factory::getApplication()->get('mailfrom');
		}

		$event = EventbookingHelperDatabase::getEvent($data['event_id'] ?? 0);

		if (!$event)
		{
			throw new Exception(Text::_('EB_EVENT_NOT_FOUND'), 404);
		}

		// Check to see if this is a past event
		$offset = Factory::getApplication()->get('offset');

		if ((int) $event->cut_off_date)
		{
			$dateToCompare = Factory::getDate($event->cut_off_date, $offset);
		}
		else
		{
			$dateToCompare = Factory::getDate($event->event_date, $offset);
		}

		$currentDate = Factory::getDate('now', $offset);

		if ($currentDate > $dateToCompare)
		{
			throw new Exception('Invite Friend Is Not Enabled For Past Event', 500);
		}

		$replaces                      = EventbookingHelperRegistration::buildEventTags($event, $config);
		$replaces['sender_name']       = $data['name'];
		$replaces['PERSONAL_MESSAGE']  = $data['message'];
		$replaces['event_detail_link'] = '<a href="' . $replaces['event_link'] . '">' . $event->title . '</a>';

		if (strlen($message->{'invitation_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'invitation_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->invitation_email_subject;
		}

		if (strlen(strip_tags($message->{'invitation_email_body' . $fieldSuffix})))
		{
			$body = $message->{'invitation_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->invitation_email_body;
		}

		$body = EventbookingHelper::convertImgTags($body);

		$subject = EventbookingHelper::replaceUpperCaseTags($subject, $replaces);
		$body    = EventbookingHelper::replaceUpperCaseTags($body, $replaces);

		$emails = explode("\r\n", $data['friend_emails']);
		$names  = explode("\r\n", $data['friend_names']);
		$mailer = Factory::getMailer();

		$n = max(count($emails), 5);

		for ($i = 0; $i < $n; $i++)
		{
			$emailBody = $body;
			$email     = $emails[$i] ?? '';
			$name      = $names[$i] ?? '';

			if ($name && MailHelper::isEmailAddress($email))
			{
				$emailBody = str_replace('[NAME]', $name, $emailBody);
				$mailer->sendMail($fromEmail, $fromName, $email, $subject, $emailBody, 1);
				$mailer->ClearAllRecipients();
			}
		}
	}
}
