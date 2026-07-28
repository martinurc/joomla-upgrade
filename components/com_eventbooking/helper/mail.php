<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class EventbookingHelperMail
{
	/**
	 * From Name
	 *
	 * @var string
	 */
	public static $fromName;

	/**
	 * From Email
	 *
	 * @var string
	 */
	public static $fromEmail;

	/**
	 * Mark email sent to admin
	 */
	public const SEND_TO_ADMIN = 1;

	/**
	 * Mark email sent to registrant
	 */
	public const SEND_TO_REGISTRANT = 2;

	/**
	 * Helper function for sending emails to registrants and administrator
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function sendEmails($row, $config)
	{
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
		$category    = EventbookingHelperDatabase::getCategory($event->main_category_id);

		EventbookingHelper::setEventMessagesDataFromCategory(
			$event,
			$category,
			['admin_email_body', 'user_email_body', 'user_email_body_offline', 'group_member_email_body'],
			$fieldSuffix
		);
		EventbookingHelper::setEventStringsDataFromCategory($event, $category, ['user_email_subject', 'notification_emails'], $fieldSuffix);

		if ($event->send_emails != -1)
		{
			$config->send_emails = $event->send_emails;
		}

		if ($config->send_emails == 3)
		{
			return;
		}

		$userId = $row->user_id ?: null;

		// Load frontend component language if needed
		EventbookingHelper::loadRegistrantLanguage($row);

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$mailer = static::getMailer($config, $event);

		if ($event->reply_to_email && MailHelper::isEmailAddress($event->reply_to_email))
		{
			$mailer->addReplyTo($event->reply_to_email);
		}
		elseif ($event->created_by && $config->send_email_to_event_creator)
		{
			$eventCreator = User::getInstance($event->created_by);

			if (MailHelper::isEmailAddress($eventCreator->email) && !$eventCreator->authorise('core.admin'))
			{
				$mailer->addReplyTo($eventCreator->email);
			}
		}

		if ($row->published == 3)
		{
			$typeOfRegistration = 2;
		}
		else
		{
			$typeOfRegistration = 1;
		}

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language, $userId, $typeOfRegistration);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language, $userId, $typeOfRegistration);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language, $userId, $typeOfRegistration);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$replaces = EventbookingHelper::callOverridableHelperMethod('Registration', 'buildTags', [$row, $form, $event, $config], 'Helper');

		// Get group members data
		if ($row->is_group_billing)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id);
			$db->setQuery($query);
			$rowMembers = $db->loadObjectList();
		}
		else
		{
			$rowMembers = [];
		}

		$invoiceFilePath = '';

		if ($config->activate_invoice_feature
			&& $row->invoice_number
			&& ($config->send_invoice_to_admin || $config->send_invoice_to_customer))
		{
			$invoiceFilePath = EventbookingHelper::callOverridableHelperMethod('Helper', 'generateInvoicePDF', [$row]);

			// This is for backward-compatible only in case someone override generateInvoicePDF method
			if (!$invoiceFilePath)
			{
				$invoiceFilePath = JPATH_ROOT . '/media/com_eventbooking/invoices/' . EventbookingHelper::callOverridableHelperMethod(
						'Helper',
						'formatInvoiceNumber',
						[$row->invoice_number, $config, $row]
					) . '.pdf';
			}
		}

		// Send confirmation email to registrant
		if (in_array($config->send_emails, [0, 2]))
		{
			// Send to billing member, get attachments back and use it to add to attachments to group member email
			$attachments = EventbookingHelper::callOverridableHelperMethod(
				'Mail',
				'sendRegistrationEmailToRegistrant',
				[$mailer, $row, $rowMembers, $replaces, $rowFields, $invoiceFilePath]
			);

			// Send emails to group members
			if ($config->send_email_to_group_members && count($rowMembers) > 0)
			{
				static::sendRegistrationEmailToGroupMembers($mailer, $row, $rowMembers, $replaces, $attachments);
			}

			// Clear attachments
			$mailer->clearAttachments();
			$mailer->clearReplyTos();
		}

		// Send notification emails to admin if needed
		if (in_array($config->send_emails, [0, 1]))
		{
			static::sendRegistrationEmailToAdmin($mailer, $row, $form, $replaces, $rowFields, $invoiceFilePath);
		}
	}

	/**
	 * Send registration email to registrant
	 *
	 * @param   Joomla\CMS\Mail\Mail           $mailer
	 * @param   EventbookingTableRegistrant    $row
	 * @param   EventbookingTableRegistrant[]  $rowMembers
	 * @param   array                          $replaces
	 * @param   array                          $rowFields
	 * @param   string                         $invoiceFilePath
	 *
	 * @return array List of attachments which can be used for group members
	 */
	public static function sendRegistrationEmailToRegistrant($mailer, $row, $rowMembers, $replaces, $rowFields, $invoiceFilePath)
	{
		$message     = EventbookingHelper::getMessages();
		$config      = EventbookingHelper::getConfig();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
		$logEmails   = static::loggingEnabled('new_registration_emails', $config);

		if (strlen($event->user_email_subject))
		{
			$subject = $event->user_email_subject;
		}
		elseif ($fieldSuffix && strlen($message->{'user_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'user_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->user_email_subject;
		}

		if (!$row->published && strpos($row->payment_method, 'os_offline') !== false)
		{
			$offlineSuffix = str_replace('os_offline', '', $row->payment_method);

			if ($offlineSuffix && $fieldSuffix && EventbookingHelper::isValidMessage(
					$message->{'user_email_body_offline' . $offlineSuffix . $fieldSuffix}
				))
			{
				$body = $message->{'user_email_body_offline' . $offlineSuffix . $fieldSuffix};
			}
			elseif ($offlineSuffix && EventbookingHelper::isValidMessage($message->{'user_email_body_offline' . $offlineSuffix}))
			{
				$body = $message->{'user_email_body_offline' . $offlineSuffix};
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($event->{'user_email_body_offline' . $fieldSuffix}))
			{
				$body = $event->{'user_email_body_offline' . $fieldSuffix};
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'user_email_body_offline' . $fieldSuffix}))
			{
				$body = $message->{'user_email_body_offline' . $fieldSuffix};
			}
			elseif (EventbookingHelper::isValidMessage($event->user_email_body_offline))
			{
				$body = $event->user_email_body_offline;
			}
			else
			{
				$body = $message->user_email_body_offline;
			}
		}
		else
		{
			if ($fieldSuffix && EventbookingHelper::isValidMessage($event->{'user_email_body' . $fieldSuffix}))
			{
				$body = $event->{'user_email_body' . $fieldSuffix};
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'user_email_body' . $fieldSuffix}))
			{
				$body = $message->{'user_email_body' . $fieldSuffix};
			}
			elseif (EventbookingHelper::isValidMessage($event->user_email_body))
			{
				$body = $event->user_email_body;
			}
			else
			{
				$body = $message->user_email_body;
			}
		}

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);
		$body    = EventbookingHelperRegistration::processQRCODE($row, $body);

		if ($config->send_invoice_to_customer && $invoiceFilePath)
		{
			$mailer->addAttachment($invoiceFilePath);
		}

		if ($config->get('activate_tickets_pdf') && $config->get('send_tickets_via_email', 1))
		{
			static::addRegistrationTickets($mailer, $row, $rowMembers, $config);
		}

		if ($config->get('send_event_attachments', 1)
			|| ($config->send_event_attachments == 2 && $row->published == 1))
		{
			$attachments = static::addEventAttachments($mailer, $row, $event, $config);
		}
		else
		{
			$attachments = [];
		}

		//Generate and send ics file to registrants
		if ($config->send_ics_file)
		{
			$icFile = static::addRegistrationIcs($mailer, $row, $event, $config);

			if ($icFile)
			{
				$attachments[] = $icFile;
			}
		}

		if (MailHelper::isEmailAddress($row->email))
		{
			$sendTos = [$row->email];

			foreach ($rowFields as $rowField)
			{
				if ($rowField->receive_confirmation_email && !empty($replaces[$rowField->name]) && MailHelper::isEmailAddress(
						$replaces[$rowField->name]
					))
				{
					$sendTos[] = $replaces[$rowField->name];
				}
			}

			static::send($mailer, $sendTos, $subject, $body, $logEmails, 2, 'new_registration_emails');
			$mailer->clearAllRecipients();
		}

		return $attachments;
	}

	/**
	 * Send registration email to group members
	 *
	 * @param   Mail                           $mailer
	 * @param   EventbookingTableRegistrant    $row
	 * @param   EventbookingTableRegistrant[]  $rowMembers
	 * @param   array                          $replaces
	 * @param   array                          $attachments
	 */
	protected static function sendRegistrationEmailToGroupMembers($mailer, $row, $rowMembers, $replaces, $attachments)
	{
		$message     = EventbookingHelper::getMessages();
		$config      = EventbookingHelper::getConfig();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
		$rowLocation = EventbookingHelperDatabase::getLocation($event->location_id, $fieldSuffix);

		if (strlen($message->{'group_member_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'group_member_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->group_member_email_subject;
		}

		if (!$subject)
		{
			return;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($event->{'group_member_email_body' . $fieldSuffix}))
		{
			$body = $event->{'group_member_email_body' . $fieldSuffix};
		}
		elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'group_member_email_body' . $fieldSuffix}))
		{
			$body = $message->{'group_member_email_body' . $fieldSuffix};
		}
		elseif (EventbookingHelper::isValidMessage($event->group_member_email_body))
		{
			$body = $event->group_member_email_body;
		}
		else
		{
			$body = $message->group_member_email_body;
		}

		if (!$body)
		{
			return;
		}

		$userId = $row->user_id ?: null;

		if ($row->published == 3)
		{
			$typeOfRegistration = 2;
		}
		else
		{
			$typeOfRegistration = 1;
		}

		$logEmails = static::loggingEnabled('new_registration_emails', $config);

		$memberReplaces = [];

		if ($config->event_custom_field
			&& file_exists(JPATH_ROOT . '/components/com_eventbooking/fields.xml'))
		{
			foreach ($event->paramData as $customFieldName => $param)
			{
				$memberReplaces[strtoupper($customFieldName)] = $param['value'];
			}
		}

		$memberReplaces['registration_detail'] = $replaces['registration_detail'];
		$memberReplaces['payment_method']      = $replaces['payment_method'];
		$memberReplaces['payment_method_name'] = $replaces['payment_method_name'];

		$memberReplaces['group_billing_first_name'] = $row->first_name;
		$memberReplaces['group_billing_last_name']  = $row->last_name;
		$memberReplaces['group_billing_email']      = $row->email;

		$memberReplaces['event_title']       = $replaces['event_title'];
		$memberReplaces['event_date']        = $replaces['event_date'];
		$memberReplaces['event_end_date']    = $replaces['event_end_date'];
		$memberReplaces['transaction_id']    = $replaces['transaction_id'];
		$memberReplaces['date']              = $replaces['date'];
		$memberReplaces['short_description'] = $replaces['short_description'];
		$memberReplaces['description']       = $replaces['short_description'];
		$memberReplaces['location']          = $replaces['location'];
		$memberReplaces['event_link']        = $replaces['event_link'];

		$memberReplaces['download_certificate_link'] = $replaces['download_certificate_link'];

		$memberFormFields = EventbookingHelperRegistration::getFormFields($row->event_id, 2, $row->language, $userId, $typeOfRegistration);

		foreach ($rowMembers as $rowMember)
		{
			if (!MailHelper::isEmailAddress($rowMember->email))
			{
				continue;
			}

			// Clear attachments sent to billing records
			$mailer->clearAttachments();

			//Build the member form
			$memberForm = new RADForm($memberFormFields);
			$memberData = EventbookingHelperRegistration::getRegistrantData($rowMember, $memberFormFields);
			$memberForm->bind($memberData);
			$memberForm->buildFieldsDependency();
			$fields = $memberForm->getFields();

			foreach ($fields as $field)
			{
				if ($field->hideOnDisplay)
				{
					$fieldValue = '';
				}
				else
				{
					if (is_string($field->value) && is_array(json_decode($field->value)))
					{
						$fieldValue = implode(', ', json_decode($field->value));
					}
					else
					{
						$fieldValue = $field->value;
					}
				}

				$memberReplaces[$field->name] = $fieldValue;
			}

			$memberReplaces['member_detail'] = EventbookingHelperRegistration::getMemberDetails(
				$config,
				$rowMember,
				$event,
				$rowLocation,
				true,
				$memberForm
			);

			$memberReplaces['id']     = $rowMember->id;
			$memberReplaces['amount'] = EventbookingHelper::formatAmount($rowMember->amount, $config);

			$groupMemberEmailSubject = $subject;
			$groupMemberEmailBody    = $body;

			$groupMemberEmailSubject = EventbookingHelper::replaceCaseInsensitiveTags($groupMemberEmailSubject, $memberReplaces);
			$groupMemberEmailBody    = EventbookingHelper::replaceCaseInsensitiveTags($groupMemberEmailBody, $memberReplaces);

			$groupMemberEmailBody = EventbookingHelper::convertImgTags($groupMemberEmailBody);

			foreach ($attachments as $attachment)
			{
				$mailer->addAttachment($attachment);
			}

			// Create PDF ticket
			if ($row->ticket_code && $row->payment_status == 1)
			{
				$ticketNumber   = EventbookingHelperTicket::formatTicketNumber($event->ticket_prefix, $rowMember->ticket_number, $config);
				$ticketFileName = File::makeSafe($ticketNumber);
				$ticketFilePath = JPATH_ROOT . '/media/com_eventbooking/tickets/' . $ticketFileName;

				if (!file_exists($ticketFilePath))
				{
					$ticketFilePaths = EventbookingHelper::callOverridableHelperMethod(
						'Ticket',
						'generateRegistrationTicketsPDF',
						[$rowMember, $config]
					);

					foreach ($ticketFilePaths as $ticketFilePath)
					{
						$mailer->addAttachment($ticketFilePath);
					}
				}
				else
				{
					$mailer->addAttachment($ticketFilePath);
				}
			}

			static::send($mailer, [$rowMember->email], $groupMemberEmailSubject, $groupMemberEmailBody, $logEmails, 2, 'new_registration_emails');
			$mailer->clearAllRecipients();
		}
	}

	/**
	 * Send registration email to administrator
	 *
	 * @param   Mail                         $mailer
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADForm                      $form
	 * @param   array                        $replaces
	 * @param   array                        $rowFields
	 * @param   array                        $attachments
	 * @param   string                       $invoiceFilePath
	 */
	protected static function sendRegistrationEmailToAdmin($mailer, $row, $form, $replaces, $rowFields, $invoiceFilePath)
	{
		$message     = EventbookingHelper::getMessages();
		$config      = EventbookingHelper::getConfig();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
		$logEmails   = static::loggingEnabled('new_registration_emails', $config);

		// Send invoice PDF to admin email
		if ($config->send_invoice_to_admin && $invoiceFilePath)
		{
			$mailer->addAttachment($invoiceFilePath);
		}

		// Send attachments which registrants uploaded on registration form to admin
		if ($config->send_attachments_to_admin)
		{
			static::addRegistrationFormAttachments($mailer, $rowFields, $replaces);
		}

		$emails = explode(',', $config->notification_emails);

		if ($fieldSuffix && strlen($message->{'admin_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'admin_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->admin_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($event->{'admin_email_body' . $fieldSuffix}))
		{
			$body = $event->{'admin_email_body' . $fieldSuffix};
		}
		elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'admin_email_body' . $fieldSuffix}))
		{
			$body = $message->{'admin_email_body' . $fieldSuffix};
		}
		elseif (EventbookingHelper::isValidMessage($event->admin_email_body))
		{
			$body = $event->admin_email_body;
		}
		else
		{
			$body = $message->admin_email_body;
		}

		if ($row->payment_method == 'os_offline_creditcard')
		{
			$replaces['registration_detail'] = EventbookingHelperRegistration::getEmailContent($config, $row, true, $form, true);
		}

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);


		$body = EventbookingHelper::convertImgTags($body);
		$body = EventbookingHelperRegistration::processQRCODE($row, $body);

		if ($config->send_email_to_event_creator && $event->created_by)
		{
			$eventCreator = User::getInstance($event->created_by);

			if (!empty($eventCreator->email)
				&& !$eventCreator->authorise('core.admin')
				&& MailHelper::isEmailAddress($eventCreator->email)
				&& !in_array($eventCreator->email, $emails))
			{
				$emails[] = $eventCreator->email;
			}
		}

		if (MailHelper::isEmailAddress($row->email))
		{
			$mailer->addReplyTo($row->email);
		}

		static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'new_registration_emails');
	}

	/**
	 * Send email to registrant when admin approves his registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function sendRegistrationApprovedEmail($row, $config)
	{
		if (!MailHelper::isEmailAddress($row->email))
		{
			return;
		}

		$logEmails = static::loggingEnabled('registration_approved_emails', $config);

		EventbookingHelper::loadRegistrantLanguage($row);

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
		$category    = EventbookingHelperDatabase::getCategory($event->main_category_id);

		EventbookingHelper::setEventMessagesDataFromCategory($event, $category, ['registration_approved_email_body'], $fieldSuffix);
		EventbookingHelper::setEventStringsDataFromCategory($event, $category, ['notification_emails'], $fieldSuffix);

		$mailer = static::getMailer($config, $event);

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$replaces = EventbookingHelper::callOverridableHelperMethod('Registration', 'buildTags', [$row, $form, $event, $config], 'Helper');

		if (isset($event->registration_approved_email_subject) && strlen(trim($event->registration_approved_email_subject)))
		{
			$subject = $event->registration_approved_email_subject;
		}
		elseif ($fieldSuffix && strlen($message->{'registration_approved_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'registration_approved_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->registration_approved_email_subject;
		}

		if ($fieldSuffix && isset($event->{'registration_approved_email_body' . $fieldSuffix}) && EventbookingHelper::isValidMessage(
				$event->{'registration_approved_email_body' . $fieldSuffix}
			))
		{
			$body = $event->{'registration_approved_email_body' . $fieldSuffix};
		}
		elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'registration_approved_email_body' . $fieldSuffix}))
		{
			$body = $message->{'registration_approved_email_body' . $fieldSuffix};
		}
		elseif (isset($event->registration_approved_email_body) && EventbookingHelper::isValidMessage($event->registration_approved_email_body))
		{
			$body = $event->registration_approved_email_body;
		}
		else
		{
			$body = $message->registration_approved_email_body;
		}

		$subject = EventbookingHelper::replaceUpperCaseTags($subject, $replaces);
		$body    = EventbookingHelper::replaceUpperCaseTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);
		$body    = EventbookingHelperRegistration::processQRCODE($row, $body);

		if (strpos($body, '[QRCODE]') !== false)
		{
			EventbookingHelper::generateQrcode($row->id);
			$imgTag = '<img src="' . EventbookingHelper::getSiteUrl() . 'media/com_eventbooking/qrcodes/' . $row->id . '.png" border="0" />';
			$body   = str_ireplace('[QRCODE]', $imgTag, $body);
		}

		if ($config->activate_invoice_feature
			&& ($config->send_invoice_to_customer || $config->send_invoice_to_admin)
			&& $row->invoice_number && !$row->group_id)
		{
			$invoiceFilePath = EventbookingHelper::callOverridableHelperMethod('Helper', 'generateInvoicePDF', [$row]);

			if (!$invoiceFilePath)
			{
				$invoiceFilePath = JPATH_ROOT . '/media/com_eventbooking/invoices/' . EventbookingHelper::callOverridableHelperMethod(
						'Helper',
						'formatInvoiceNumber',
						[$row->invoice_number, $config, $row]
					) . '.pdf';
			}
		}

		if ($config->send_invoice_to_customer && !empty($invoiceFilePath))
		{
			$mailer->addAttachment($invoiceFilePath);
		}

		$pdfTickets = [];

		if ($row->ticket_code && $config->get('send_tickets_via_email', 1))
		{
			if ($config->get('multiple_booking'))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('*')
					->from('#__eb_registrants')
					->where('id = ' . $row->id . ' OR cart_id = ' . $row->id);
				$db->setQuery($query);
				$rowRegistrants = $db->loadObjectList();

				foreach ($rowRegistrants as $rowRegistrant)
				{
					if ($rowRegistrant->ticket_code)
					{
						$ticketFilePaths = EventbookingHelper::callOverridableHelperMethod(
							'Ticket',
							'generateRegistrationTicketsPDF',
							[$rowRegistrant, $config]
						);

						foreach ($ticketFilePaths as $ticketFilePath)
						{
							$mailer->addAttachment($ticketFilePath);

							$pdfTickets[] = $ticketFilePath;
						}
					}
				}
			}
			else
			{
				$ticketFilePaths = EventbookingHelper::callOverridableHelperMethod('Ticket', 'generateRegistrationTicketsPDF', [$row, $config]);

				foreach ($ticketFilePaths as $ticketFilePath)
				{
					$mailer->addAttachment($ticketFilePath);

					$pdfTickets[] = $ticketFilePath;
				}
			}
		}

		$sendTos = [$row->email];

		foreach ($rowFields as $rowField)
		{
			if ($rowField->receive_confirmation_email && !empty($replaces[$rowField->name]) && MailHelper::isEmailAddress(
					$replaces[$rowField->name]
				))
			{
				$sendTos[] = $replaces[$rowField->name];
			}
		}

		if ($config->send_event_attachments == 2 && $row->published == 1)
		{
			static::addEventAttachments($mailer, $row, $event, $config);
		}

		static::send($mailer, $sendTos, $subject, $body, $logEmails, 2, 'registration_approved_emails');

		if ($fieldSuffix && strlen($message->{'admin_registration_approved_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'admin_registration_approved_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->admin_registration_approved_email_subject;
		}

		// Do not process it further if no subject is provided
		if (!trim($subject))
		{
			return;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'admin_registration_approved_email_body' . $fieldSuffix}))
		{
			$body = $message->{'admin_registration_approved_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->admin_registration_approved_email_body;
		}

		$mailer->clearAllRecipients();
		$mailer->clearAttachments();

		if ($config->send_invoice_to_admin && !empty($invoiceFilePath))
		{
			$mailer->addAttachment($invoiceFilePath);
		}

		foreach ($pdfTickets as $pdfTicket)
		{
			$mailer->addAttachment($pdfTicket);
		}

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$emails = explode(',', $config->notification_emails);

		if ($config->send_email_to_event_creator && $event->created_by)
		{
			$eventCreator = User::getInstance($event->created_by);

			if (!empty($eventCreator->email)
				&& !$eventCreator->authorise('core.admin')
				&& MailHelper::isEmailAddress($eventCreator->email)
				&& !in_array($eventCreator->email, $emails))
			{
				$emails[] = $eventCreator->email;
			}
		}

		$user                               = Factory::getUser();
		$replaces['APPROVAL_USER_USERNAME'] = $user->username;
		$replaces['APPROVAL_USER_NAME']     = $user->name;
		$replaces['APPROVAL_USER_EMAIL']    = $user->email;
		$replaces['APPROVAL_USER_ID']       = $user->id;

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);

		static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'registration_approved_emails');
	}

	/**
	 * Send email to registrant when admin change the status to cancelled
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function sendRegistrationCancelledEmail($row, $config)
	{
		if (!MailHelper::isEmailAddress($row->email))
		{
			return;
		}

		$logEmails = static::loggingEnabled('registration_cancel_emails', $config);

		$app = Factory::getApplication();

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$event = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

		if ($app->isClient('administrator'))
		{
			if ($row->language && $row->language != '*')
			{
				$tag = $row->language;
			}
			else
			{
				$tag = EventbookingHelper::getDefaultLanguage();
			}

			Factory::getLanguage()->load('com_eventbooking', JPATH_ROOT, $tag);
		}

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		if ($fieldSuffix && strlen($message->{'user_registration_cancel_subject' . $fieldSuffix}))
		{
			$subject = $message->{'user_registration_cancel_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->user_registration_cancel_subject;
		}

		if (empty($subject))
		{
			return;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'user_registration_cancel_message' . $fieldSuffix}))
		{
			$body = $message->{'user_registration_cancel_message' . $fieldSuffix};
		}
		else
		{
			$body = $message->user_registration_cancel_message;
		}

		if (empty($body))
		{
			return;
		}

		if (!MailHelper::isEmailAddress($row->email))
		{
			return;
		}

		$mailer = static::getMailer($config, $event);

		$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row, $event);

		$subject = EventbookingHelper::replaceUpperCaseTags($subject, $replaces);
		$body    = EventbookingHelper::replaceUpperCaseTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		static::send($mailer, [$row->email], $subject, $body, $logEmails, 2, 'registration_cancel_emails');

		// Send notification to administrator
		if ($fieldSuffix && strlen($message->{'admin_cancel_registration_notification_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'admin_cancel_registration_notification_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->admin_cancel_registration_notification_email_subject;
		}

		// Do not process it further if no subject is provided
		if (!trim($subject))
		{
			return;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'admin_cancel_registration_notification_email_body' . $fieldSuffix}))
		{
			$body = $message->{'admin_cancel_registration_notification_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->admin_cancel_registration_notification_email_body;
		}

		$mailer->clearAllRecipients();

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$emails = explode(',', $config->notification_emails);

		if ($config->send_email_to_event_creator && $event->created_by)
		{
			$eventCreator = User::getInstance($event->created_by);

			if (!empty($eventCreator->email)
				&& !$eventCreator->authorise('core.admin')
				&& MailHelper::isEmailAddress($eventCreator->email)
				&& !in_array($eventCreator->email, $emails))
			{
				$emails[] = $eventCreator->email;
			}
		}

		$user                                   = Factory::getUser();
		$replaces['CANCELLED_BY_USER_USERNAME'] = $user->username;
		$replaces['CANCELLED_BY_USER_NAME']     = $user->name;
		$replaces['CANCELLED_BY_USER_EMAIL']    = $user->email;
		$replaces['CANCELLED_BY_USER_ID']       = $user->id;

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'registration_cancel_emails');
	}

	/**
	 * Send email when users fill-in waitinglist
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function sendWaitinglistEmail($row, $config)
	{
		$logEmails = static::loggingEnabled('waiting_list_emails', $config);

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$event = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$mailer = static::getMailer($config, $event);

		$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row, $event, $row->user_id, $config->multiple_booking);

		//Notification email send to user
		if ($fieldSuffix && strlen($message->{'watinglist_confirmation_subject' . $fieldSuffix}))
		{
			$subject = $message->{'watinglist_confirmation_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->watinglist_confirmation_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'watinglist_confirmation_body' . $fieldSuffix}))
		{
			$body = $message->{'watinglist_confirmation_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->watinglist_confirmation_body;
		}

		$subject = EventbookingHelper::replaceUpperCaseTags($subject, $replaces);
		$body    = EventbookingHelper::replaceUpperCaseTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		if (MailHelper::isEmailAddress($row->email))
		{
			$sendTos = [$row->email];

			$userId             = (int) $row->user_id;
			$typeOfRegistration = 2;

			if ($row->is_group_billing)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language, $userId, $typeOfRegistration);
			}
			elseif ($row->group_id > 0)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 2, $row->language, $userId, $typeOfRegistration);
			}
			else
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language, $userId, $typeOfRegistration);
			}

			foreach ($rowFields as $rowField)
			{
				if ($rowField->receive_confirmation_email &&
					!empty($replaces[$rowField->name])
					&& MailHelper::isEmailAddress($replaces[$rowField->name]))
				{
					$sendTos[] = $replaces[$rowField->name];
				}
			}

			static::send($mailer, $sendTos, $subject, $body, $logEmails, 2, 'waiting_list_emails');

			$mailer->clearAllRecipients();
		}

		$emails = explode(',', $config->notification_emails);

		if ($config->send_email_to_event_creator && $event->created_by)
		{
			$eventCreator = User::getInstance($event->created_by);

			if (!empty($eventCreator->email)
				&& !$eventCreator->authorise('core.admin')
				&& MailHelper::isEmailAddress($eventCreator->email)
				&& !in_array($eventCreator->email, $emails))
			{
				$emails[] = $eventCreator->email;
			}
		}

		if (strlen($message->{'watinglist_notification_subject' . $fieldSuffix}))
		{
			$subject = $message->{'watinglist_notification_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->watinglist_notification_subject;
		}

		if (EventbookingHelper::isValidMessage($message->{'watinglist_notification_body' . $fieldSuffix}))
		{
			$body = $message->{'watinglist_notification_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->watinglist_notification_body;
		}

		$subject = str_ireplace('[EVENT_TITLE]', $event->title, $subject);
		$subject = EventbookingHelper::replaceUpperCaseTags($subject, $replaces);
		$body    = EventbookingHelper::replaceUpperCaseTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		if (MailHelper::isEmailAddress($row->email))
		{
			$mailer->addReplyTo($row->email);
		}

		static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'waiting_list_emails');
	}

	/**
	 * Send notification emails to waiting list users when a registration is cancelled
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function sendWaitingListNotificationEmail($row, $config)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_registrants')
			->where('event_id=' . (int) $row->event_id)
			->where('group_id = 0')
			->where('published = 3')
			->order('id');
		$db->setQuery($query);
		$registrants = $db->loadObjectList();

		$logEmails = static::loggingEnabled('waiting_list_notification_emails', $config);

		if (count($registrants))
		{
			$rowEvent = EventbookingHelperDatabase::getEvent($row->event_id);
			$mailer   = static::getMailer($config, $rowEvent);
			$message  = EventbookingHelper::getMessages();

			foreach ($registrants as $registrant)
			{
				if (!MailHelper::isEmailAddress($registrant->email))
				{
					continue;
				}

				// Check to see if user already registered
				$query->clear()
					->select('COUNT(*)')
					->from('#__eb_registrants')
					->where('event_id = ' . (int) $row->event_id)
					->where('group_id = 0')
					->where('(published = 1 OR (published = 0 AND payment_method LIKE "os_offline%"))');

				if ($registrant->user_id > 0)
				{
					$query->where('user_id = ' . $registrant->user_id);
				}
				else
				{
					$query->where('email = ' . $db->quote($registrant->email));
				}

				$db->setQuery($query);

				if ($db->loadResult() > 0)
				{
					// Ignore sending email because this user is already registered for the event
					continue;
				}

				$fieldSuffix = EventbookingHelper::getFieldSuffix($registrant->language);

				if (strlen(trim($message->{'registrant_waitinglist_notification_subject' . $fieldSuffix})))
				{
					$subject = $message->{'registrant_waitinglist_notification_subject' . $fieldSuffix};
				}
				else
				{
					$subject = $message->registrant_waitinglist_notification_subject;
				}

				if (empty($subject))
				{
					//Admin has not entered email subject and email message for notification yet, simply return
					return false;
				}

				if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'registrant_waitinglist_notification_body' . $fieldSuffix}))
				{
					$body = $message->{'registrant_waitinglist_notification_body' . $fieldSuffix};
				}
				else
				{
					$body = $message->registrant_waitinglist_notification_body;
				}

				$rowEvent = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

				$replaces                          = EventbookingHelperRegistration::getRegistrationReplaces($registrant, $rowEvent);
				$replaces['registrant_first_name'] = $row->first_name;
				$replaces['registrant_last_name']  = $row->last_name;

				$subject = EventbookingHelper::replaceUpperCaseTags($subject, $replaces);
				$body    = EventbookingHelper::replaceUpperCaseTags($body, $replaces);
				$body    = EventbookingHelper::convertImgTags($body);

				static::send($mailer, [$registrant->email], $subject, $body, $logEmails, 2, 'waiting_list_notification_emails');

				$mailer->clearAddresses();
			}
		}
	}

	/**
	 * Send email when registrants complete deposit payment
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function sendDepositPaymentEmail($row, $config)
	{
		$logEmails   = static::loggingEnabled('deposit_payment_emails', $config);
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$mailer = static::getMailer($config, $event);

		$replaces = EventbookingHelper::callOverridableHelperMethod('Registration', 'buildDepositPaymentTags', [$row, $config]);

		//Notification email send to user
		if (MailHelper::isEmailAddress($row->email))
		{
			if ($fieldSuffix && strlen($message->{'deposit_payment_user_email_subject' . $fieldSuffix}))
			{
				$subject = $message->{'deposit_payment_user_email_subject' . $fieldSuffix};
			}
			else
			{
				$subject = $message->deposit_payment_user_email_subject;
			}

			if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'deposit_payment_user_email_body' . $fieldSuffix}))
			{
				$body = $message->{'deposit_payment_user_email_body' . $fieldSuffix};
			}
			else
			{
				$body = $message->deposit_payment_user_email_body;
			}

			$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
			$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);

			if ($row->ticket_code)
			{
				$ticketFilePath = EventbookingHelper::callOverridableHelperMethod('Ticket', 'generateTicketsPDF', [$row, $config]);

				// This line is added for backward compatible only, in case someone override the method generateTicketsPDF without returning file path
				if (!$ticketFilePath)
				{
					$ticketFilePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($row->id, 5, '0', STR_PAD_LEFT) . '.pdf';
				}

				$mailer->addAttachment($ticketFilePath);
			}

			static::send($mailer, [$row->email], $subject, $body, $logEmails, 2);

			$mailer->clearAttachments();
			$mailer->clearAllRecipients();
		}

		$emails = explode(',', $config->notification_emails);

		if (strlen($message->{'deposit_payment_admin_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'deposit_payment_admin_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->deposit_payment_admin_email_subject;
		}

		if (EventbookingHelper::isValidMessage($message->{'deposit_payment_admin_email_body' . $fieldSuffix}))
		{
			$body = $message->{'deposit_payment_admin_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->deposit_payment_admin_email_body;
		}

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		static::send($mailer, $emails, $subject, $body, $logEmails, 1);
	}

	/**
	 * Send new event notification email to admin and users when new event is submitted in the frontend
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   RADConfig               $config
	 */
	public static function sendNewEventNotificationEmail($row, $config)
	{
		$logEmails = static::loggingEnabled('new_event_notification_emails', $config);

		$user        = Factory::getUser();
		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->created_language);
		$Itemid      = Factory::getApplication()->input->getInt('Itemid');
		$category    = EventbookingHelperDatabase::getCategory($row->main_category_id);

		$mailer = static::getMailer($config);

		$replaces = [
			'user_id'     => $user->id,
			'username'    => $user->username,
			'name'        => $user->name,
			'email'       => $user->email,
			'event_id'    => $row->id,
			'event_title' => $row->title,
			'category'    => $category->name,
			'event_date'  => HTMLHelper::_('date', $row->event_date, $config->event_date_format, null),
			'event_link'  => Uri::root() . 'index.php?option=com_eventbooking&view=event&layout=form&id=' . $row->id . '&Itemid=' . $Itemid,
		];

		// Support event custom fields text
		if ($config->event_custom_field
			&& file_exists(JPATH_ROOT . '/components/com_eventbooking/fields.xml'))
		{
			EventbookingHelperData::prepareCustomFieldsData([$row]);

			foreach ($row->paramData as $customFieldName => $param)
			{
				$replaces[strtoupper($customFieldName)] = $param['value'];
			}
		}

		//Notification email send to user
		if ($fieldSuffix && strlen($message->{'submit_event_user_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'submit_event_user_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->submit_event_user_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'submit_event_user_email_body' . $fieldSuffix}))
		{
			$body = $message->{'submit_event_user_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->submit_event_user_email_body;
		}

		if ($subject)
		{
			$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
			$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
			$body    = EventbookingHelper::convertImgTags($body);

			if (MailHelper::isEmailAddress($user->email))
			{
				static::send($mailer, [$user->email], $subject, $body, $logEmails, 2, 'new_event_notification_emails');
				$mailer->clearAllRecipients();
			}
		}

		if (trim($category->notification_emails))
		{
			$emails = explode(',', $category->notification_emails);
		}
		else
		{
			$emails = explode(',', $config->notification_emails);
		}

		$emails = array_map('trim', $emails);

		if (strlen($message->{'submit_event_admin_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'submit_event_admin_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->submit_event_admin_email_subject;
		}

		if (!$subject)
		{
			return;
		}

		if (EventbookingHelper::isValidMessage($message->{'submit_event_admin_email_body' . $fieldSuffix}))
		{
			$body = $message->{'submit_event_admin_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->submit_event_admin_email_body;
		}

		$replaces['event_link'] = Uri::root() . 'administrator/index.php?option=com_eventbooking&view=event&id=' . $row->id;

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'new_event_notification_emails');
	}

	/**
	 * Send new event notification email to admin and users when new event is submitted in the frontend
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   RADConfig               $config
	 * @param   User                    $eventCreator
	 */
	public static function sendEventApprovedEmail($row, $config, $eventCreator)
	{
		$logEmails = static::loggingEnabled('event_approved_emails', $config);

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->created_language);
		$Itemid      = EventbookingHelper::getItemid();

		$mailer = static::getMailer($config);

		$replaces = [
			'username'    => $eventCreator->username,
			'name'        => $eventCreator->name,
			'email'       => $eventCreator->email,
			'event_id'    => $row->id,
			'event_title' => $row->title,
			'event_date'  => HTMLHelper::_('date', $row->event_date, $config->event_date_format, null),
			'event_link'  => Uri::root() . 'index.php?option=com_eventbooking&view=event&layout=form&id=' . $row->id . '&Itemid=' . $Itemid,
		];

		// Support event custom fields text
		if ($config->event_custom_field
			&& file_exists(JPATH_ROOT . '/components/com_eventbooking/fields.xml'))
		{
			EventbookingHelperData::prepareCustomFieldsData([$row]);

			foreach ($row->paramData as $customFieldName => $param)
			{
				$replaces[strtoupper($customFieldName)] = $param['value'];
			}
		}

		//Notification email send to user
		if ($fieldSuffix && strlen($message->{'event_approved_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'event_approved_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->event_approved_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'event_approved_email_body' . $fieldSuffix}))
		{
			$body = $message->{'event_approved_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->event_approved_email_body;
		}

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		static::send($mailer, [$eventCreator->email], $subject, $body, $logEmails, 2, 'event_approved_emails');
	}

	/**
	 * Send notification email to admin when users update their event from frontend
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   RADConfig               $config
	 * @param   User                    $eventCreator
	 */
	public static function sendEventUpdateEmail($row, $config)
	{
		$logEmails = static::loggingEnabled('event_update_emails', $config);

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->created_language);

		$eventCreator = User::getInstance($row->created_by);

		$mailer = static::getMailer($config);

		$replaces = [
			'username'    => $eventCreator->username,
			'name'        => $eventCreator->name,
			'email'       => $eventCreator->email,
			'event_id'    => $row->id,
			'event_title' => $row->title,
			'event_date'  => HTMLHelper::_('date', $row->event_date, $config->event_date_format, null),
			'event_link'  => Uri::root() . 'administrator/index.php?option=com_eventbooking&view=event&id=' . $row->id,
		];

		//Notification email send to user
		if ($fieldSuffix && strlen($message->{'event_update_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'event_update_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->event_update_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'event_update_email_body' . $fieldSuffix}))
		{
			$body = $message->{'event_update_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->event_update_email_body;
		}

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		$emails = explode(',', $config->notification_emails);
		$emails = array_map('trim', $emails);

		static::send($mailer, $emails, $subject, $body, $logEmails, 2, 'event_update_emails');
	}

	/**
	 * Method to send reminder emails to registrants
	 *
	 * @param   Registry  $params
	 * @param   int       $reminderNumber
	 *
	 * @return void
	 */
	public static function sendReminderEmails($params, $reminderNumber)
	{
		// We only support up to 3 reminders at the moment
		if (!in_array($reminderNumber, [1, 2, 3, 4, 5, 6]))
		{
			return;
		}

		switch ($reminderNumber)
		{
			case 1:
				$sendReminderField         = 'b.send_first_reminder';
				$reminderSentField         = 'a.is_reminder_sent';
				$reminderFrequencyField    = 'b.first_reminder_frequency';
				$reminderEmailSubjectField = 'reminder_email_subject';
				$reminderEmailBodyField    = 'reminder_email_body';
				break;
			case 2:
				$sendReminderField         = 'b.send_second_reminder';
				$reminderSentField         = 'a.is_second_reminder_sent';
				$reminderFrequencyField    = 'b.second_reminder_frequency';
				$reminderEmailSubjectField = 'second_reminder_email_subject';
				$reminderEmailBodyField    = 'second_reminder_email_body';
				break;
			case 4:
				$sendReminderField         = 'b.send_fourth_reminder';
				$reminderSentField         = 'a.is_fourth_reminder_sent';
				$reminderFrequencyField    = 'b.fourth_reminder_frequency';
				$reminderEmailSubjectField = 'fourth_reminder_email_subject';
				$reminderEmailBodyField    = 'fourth_reminder_email_body';
				break;
			case 5:
				$sendReminderField         = 'b.send_fifth_reminder';
				$reminderSentField         = 'a.is_fifth_reminder_sent';
				$reminderFrequencyField    = 'b.fifth_reminder_frequency';
				$reminderEmailSubjectField = 'fifth_reminder_email_subject';
				$reminderEmailBodyField    = 'fifth_reminder_email_body';
				break;
			case 6:
				$sendReminderField         = 'b.send_sixth_reminder';
				$reminderSentField         = 'a.is_sixth_reminder_sent';
				$reminderFrequencyField    = 'b.sixth_reminder_frequency';
				$reminderEmailSubjectField = 'sixth_reminder_email_subject';
				$reminderEmailBodyField    = 'sixth_reminder_email_body';
				break;
			default:
				$sendReminderField         = 'b.send_third_reminder';
				$reminderSentField         = 'a.is_third_reminder_sent';
				$reminderFrequencyField    = 'b.third_reminder_frequency';
				$reminderEmailSubjectField = 'third_reminder_email_subject';
				$reminderEmailBodyField    = 'third_reminder_email_body';
				break;
		}

		$db      = Factory::getDbo();
		$query   = $db->getQuery(true);
		$config  = EventbookingHelper::getConfig();
		$message = EventbookingHelper::getMessages();
		$mailer  = static::getMailer($config);
		$now     = $db->quote(Factory::getDate('now', Factory::getApplication()->get('offset'))->toSql(true));

		$bccEmail                = $params->get('bcc_email', '');
		$numberEmailSendEachTime = (int) $params->get('number_registrants', 15) ?: 15;

		EventbookingHelper::loadLanguage();

		if ($bccEmail)
		{
			$bccEmails = explode(',', $bccEmail);

			$bccEmails = array_map('trim', $bccEmails);

			foreach ($bccEmails as $bccEmail)
			{
				if (MailHelper::isEmailAddress($bccEmail))
				{
					$mailer->addBcc($bccEmail);
				}
			}
		}

		$hourFrequencyConditionReminderBefore = "$sendReminderField >= TIMESTAMPDIFF(HOUR, $now, b.event_date) AND TIMESTAMPDIFF(HOUR, $now, b.event_date) >= 0";
		$dayFrequencyConditionReminderBefore  = "$sendReminderField >= DATEDIFF(b.event_date, $now) AND DATEDIFF(b.event_date, $now) >= 0";
		$dayFrequencyConditionReminderAfter   = "DATEDIFF($now, b.event_date) >= ABS($sendReminderField) AND DATEDIFF($now, b.event_date) <= 60";
		$hourFrequencyConditionReminderAfter  = "TIMESTAMPDIFF(HOUR, b.event_date, $now) >= ABS($sendReminderField) AND TIMESTAMPDIFF(HOUR, b.event_date, $now) <= 100";

		$query->select('a.*')
			->select('b.from_name, b.from_email')
			->select('b.activate_certificate_feature')
			->select('b.send_first_reminder, b.send_second_reminder, b.send_third_reminder')
			->select(['b.' . $reminderEmailSubjectField, 'b.' . $reminderEmailBodyField])
			->select(
				$db->quoteName(
					['c.' . $reminderEmailSubjectField, 'c.' . $reminderEmailBodyField],
					['cat_' . $reminderEmailSubjectField, 'cat_' . $reminderEmailBodyField]
				)
			)
			->from('#__eb_registrants AS a')
			->innerJoin('#__eb_events AS b ON a.event_id = b.id')
			->leftJoin('#__eb_categories AS c ON b.main_category_id = c.id')
			->where("$reminderSentField = 0")
			->where("$sendReminderField != 0")
			->where(
				"IF($sendReminderField > 0, IF($reminderFrequencyField = 'd', $dayFrequencyConditionReminderBefore, $hourFrequencyConditionReminderBefore), IF($reminderFrequencyField = 'd', $dayFrequencyConditionReminderAfter, $hourFrequencyConditionReminderAfter))"
			)
			->order('b.event_date, a.register_date');

		if (!$params->get('send_to_group_billing', 1))
		{
			$query->where('a.is_group_billing = 0');
		}

		if (!$params->get('send_to_group_members', 1))
		{
			$query->where('a.group_id = 0');
		}

		if (!$params->get('send_to_unpublished_events', 0))
		{
			$query->where('b.published = 1');
		}

		if ($params->get('only_send_to_paid_registrants', 0))
		{
			$query->where('a.published = 1');
		}
		else
		{
			$query->where('(a.published = 1 OR (a.payment_method LIKE "os_offline%" AND a.published = 0))');
		}

		if ($params->get('only_send_to_checked_in_registrants', 0))
		{
			$query->where('a.checked_in = 1');
		}

		$db->setQuery($query, 0, $numberEmailSendEachTime);

		try
		{
			$rows = $db->loadObjectList();
		}
		catch (Exception  $e)
		{
			$rows = [];
		}

		$logEmails = static::loggingEnabled('reminder_emails', $config);

		foreach ($rows as $row)
		{
			// Mark the reminder as sent event
			$query->clear()
				->update('#__eb_registrants AS a')
				->set("$reminderSentField = 1")
				->where('id = ' . (int) $row->id);
			$db->setQuery($query)
				->execute();

			if (!MailHelper::isEmailAddress($row->email))
			{
				continue;
			}

			$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

			if (strlen($row->{$reminderEmailSubjectField}))
			{
				$emailSubject = $row->{$reminderEmailSubjectField};
			}
			elseif (strlen($row->{'cat_' . $reminderEmailSubjectField}))
			{
				$emailSubject = $row->{'cat_' . $reminderEmailSubjectField};
			}
			elseif ($fieldSuffix && strlen($message->{$reminderEmailSubjectField . $fieldSuffix}))
			{
				$emailSubject = $message->{$reminderEmailSubjectField . $fieldSuffix};
			}
			else
			{
				$emailSubject = $message->{$reminderEmailSubjectField};
			}

			if (EventbookingHelper::isValidMessage($row->{$reminderEmailBodyField}))
			{
				$emailBody = $row->{$reminderEmailBodyField};
			}
			elseif (EventbookingHelper::isValidMessage($row->{'cat_' . $reminderEmailBodyField}))
			{
				$emailBody = $row->{'cat_' . $reminderEmailBodyField};
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{$reminderEmailBodyField . $fieldSuffix}))
			{
				$emailBody = $message->{$reminderEmailBodyField . $fieldSuffix};
			}
			else
			{
				$emailBody = $message->{$reminderEmailBodyField};
			}

			$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row);

			$emailSubject = EventbookingHelper::replaceUpperCaseTags($emailSubject, $replaces);
			$emailBody    = EventbookingHelper::replaceUpperCaseTags($emailBody, $replaces);
			$emailBody    = EventbookingHelperRegistration::processQRCODE($row, $emailBody);
			$emailBody    = EventbookingHelper::convertImgTags($emailBody);

			if ($row->from_name && MailHelper::isEmailAddress($row->from_email))
			{
				$mailer->setSender([$row->from_email, $row->from_name]);
				$useEventSenderSettings = true;
			}
			else
			{
				$useEventSenderSettings = false;
			}

			// Check to see if the system should send certificate
			$sendCertificate = EventbookingHelperCertificate::shouldSendCertificateInReminderEmail($row, $params, $reminderNumber);

			if ($sendCertificate)
			{
				[$fileName, $filePath] = EventbookingHelper::callOverridableHelperMethod(
					'Certificate',
					'generateCertificates',
					[[$row], $config]
				);

				$mailer->addAttachment($filePath, $fileName);
			}

			static::send($mailer, [$row->email], $emailSubject, $emailBody, $logEmails, 2, 'reminder_emails');

			$mailer->clearAddresses();

			if ($sendCertificate)
			{
				$mailer->clearAttachments();
			}

			if ($useEventSenderSettings)
			{
				// Restore original sender setting
				$mailer->setSender([static::$fromEmail, static::$fromName]);
			}
		}
	}

	/**
	 * Send deposit payment reminder email to registrants
	 *
	 * @param   int     $numberDays
	 * @param   int     $numberEmailSendEachTime
	 * @param   string  $bccEmail
	 */
	public static function sendDepositReminder($numberDays, $numberEmailSendEachTime = 0, $bccEmail = null)
	{
		$config = EventbookingHelper::getConfig();

		$logEmails = static::loggingEnabled('deposit_payment_reminder_emails', $config);

		$db      = Factory::getDbo();
		$query   = $db->getQuery(true);
		$config  = EventbookingHelper::getConfig();
		$message = EventbookingHelper::getMessages();
		$mailer  = static::getMailer($config);

		if ($bccEmail)
		{
			$mailer->addBcc($bccEmail);
		}

		if (!$numberDays)
		{
			$numberDays = 7;
		}

		if (!$numberEmailSendEachTime)
		{
			$numberEmailSendEachTime = 15;
		}

		$query->select('a.*, b.from_name, b.from_email')
			->from('#__eb_registrants AS a')
			->innerJoin('#__eb_events AS b ON a.event_id = b.id')
			->where('b.deposit_amount > 0')
			->where('(a.published = 1 OR (a.payment_method LIKE "os_offline%" AND a.published = 0))')
			->where('a.payment_status != 1')
			->where('a.group_id = 0')
			->where('a.is_deposit_payment_reminder_sent = 0')
			->where('b.published = 1')
			->where('DATEDIFF(b.event_date, NOW()) <= ' . $numberDays)
			->where('DATEDIFF(b.event_date, NOW()) >= 0')
			->order('b.event_date, a.register_date');

		$db->setQuery($query, 0, $numberEmailSendEachTime);

		try
		{
			$rows = $db->loadObjectList();
		}
		catch (Exception  $e)
		{
			$rows = [];
		}

		foreach ($rows as $row)
		{
			if (!MailHelper::isEmailAddress($row->email))
			{
				continue;
			}

			$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

			if ($fieldSuffix && strlen($message->{'deposit_payment_reminder_email_subject' . $fieldSuffix}))
			{
				$emailSubject = $message->{'deposit_payment_reminder_email_subject' . $fieldSuffix};
			}
			else
			{
				$emailSubject = $message->deposit_payment_reminder_email_subject;
			}

			if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'deposit_payment_reminder_email_body' . $fieldSuffix}))
			{
				$emailBody = $message->{'deposit_payment_reminder_email_subject' . $fieldSuffix};
			}
			else
			{
				$emailBody = $message->deposit_payment_reminder_email_body;
			}

			$replaces           = EventbookingHelperRegistration::getRegistrationReplaces($row);
			$replaces['amount'] = EventbookingHelper::formatCurrency($row->amount - $row->deposit_amount, $config, $row->currency_symbol);

			$emailSubject = EventbookingHelper::replaceUpperCaseTags($emailSubject, $replaces);
			$emailBody    = EventbookingHelper::replaceUpperCaseTags($emailBody, $replaces);
			$emailBody    = EventbookingHelper::convertImgTags($emailBody);

			if ($row->from_name && MailHelper::isEmailAddress($row->from_email))
			{
				$useEventSenderSetting = true;
				$mailer->setSender([$row->from_email, $row->from_name]);
			}
			else
			{
				$useEventSenderSetting = false;
			}

			static::send($mailer, [$row->email], $emailSubject, $emailBody, $logEmails, 2, 'deposit_payment_reminder_emails');
			$mailer->clearAddresses();

			if ($useEventSenderSetting)
			{
				$mailer->setSender([static::$fromEmail, static::$fromName]);
			}

			$query->clear()
				->update('#__eb_registrants')
				->set('is_deposit_payment_reminder_sent = 1')
				->where('id = ' . (int) $row->id);
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Send deposit payment reminder email to registrants
	 *
	 * @param   int       $numberDaysToSendReminder
	 * @param   int       $numberRegistrants
	 * @param   Registry  $params
	 */
	public static function sendOfflinePaymentReminder($numberDaysToSendReminder, $numberRegistrants, $params)
	{
		$config = EventbookingHelper::getConfig();

		$logEmails = static::loggingEnabled('offline_payment_reminder_emails', $config);
		$baseOn    = $params->get('base_on', 0);

		$db      = Factory::getDbo();
		$query   = $db->getQuery(true);
		$config  = EventbookingHelper::getConfig();
		$message = EventbookingHelper::getMessages();
		$mailer  = static::getMailer($config);
		$query->select('a.*, b.from_name, b.from_email')
			->from('#__eb_registrants AS a')
			->innerJoin('#__eb_events AS b ON a.event_id = b.id')
			->where('a.published = 0')
			->where('a.group_id = 0')
			->where('a.amount > 0')
			->where('a.payment_method LIKE "os_offline%"')
			->where('a.is_offline_payment_reminder_sent = 0')
			->order('a.register_date');

		if ($baseOn == 0)
		{
			$query->where('DATEDIFF(NOW(), a.register_date) >= ' . $numberDaysToSendReminder)
				->where('(DATEDIFF(b.event_date, NOW()) > 0 OR DATEDIFF(b.cut_off_date, NOW()) > 0)');
		}
		else
		{
			$query->where('DATEDIFF(b.event_date, NOW()) <= ' . $numberDaysToSendReminder)
				->where('DATEDIFF(b.event_date, NOW()) >= 0');
		}

		$eventIds = array_filter(ArrayHelper::toInteger($params->get('event_ids')));

		if (count($eventIds))
		{
			$query->where('a.event_id IN (' . implode(',', $eventIds) . ')');
		}

		$db->setQuery($query, 0, $numberRegistrants);

		try
		{
			$rows = $db->loadObjectList();
		}
		catch (Exception  $e)
		{
			$rows = [];
		}

		// Load component language
		EventbookingHelper::loadLanguage();

		foreach ($rows as $row)
		{
			if (!MailHelper::isEmailAddress($row->email))
			{
				continue;
			}

			$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

			if ($fieldSuffix && strlen($message->{'offline_payment_reminder_email_subject' . $fieldSuffix}))
			{
				$emailSubject = $message->{'offline_payment_reminder_email_subject' . $fieldSuffix};
			}
			else
			{
				$emailSubject = $message->offline_payment_reminder_email_subject;
			}

			if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'offline_payment_reminder_email_body' . $fieldSuffix}))
			{
				$emailBody = $message->{'offline_payment_reminder_email_body' . $fieldSuffix};
			}
			else
			{
				$emailBody = $message->offline_payment_reminder_email_body;
			}

			$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row);

			$emailSubject = EventbookingHelper::replaceUpperCaseTags($emailSubject, $replaces);
			$emailBody    = EventbookingHelper::replaceUpperCaseTags($emailBody, $replaces);
			$emailBody    = EventbookingHelper::convertImgTags($emailBody);

			if ($row->from_name && MailHelper::isEmailAddress($row->from_email))
			{
				$useEventSenderSetting = true;
				$mailer->setSender([$row->from_email, $row->from_name]);
			}
			else
			{
				$useEventSenderSetting = false;
			}

			static::send($mailer, [$row->email], $emailSubject, $emailBody, $logEmails, 1, 'offline_payment_reminder_emails');
			$mailer->clearAddresses();

			if ($useEventSenderSetting)
			{
				$mailer->setSender([static::$fromEmail, static::$fromName]);
			}

			$query->clear()
				->update('#__eb_registrants')
				->set('is_offline_payment_reminder_sent = 1')
				->where('id = ' . (int) $row->id);
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Send event cancel emails to registrants
	 *
	 * @param   array  $rows
	 */
	public static function sendEventCancelEmails($rows)
	{
		$message = EventbookingHelper::getMessages();
		$config  = EventbookingHelper::getConfig();

		$logEmails = static::loggingEnabled('event_cancel_emails', $config);

		$mailer = static::getMailer($config);

		// Load component language
		EventbookingHelper::loadLanguage();

		foreach ($rows as $row)
		{
			if (!MailHelper::isEmailAddress($row->email))
			{
				continue;
			}

			$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

			if ($fieldSuffix && strlen($message->{'event_cancel_email_subject' . $fieldSuffix}))
			{
				$emailSubject = $message->{'event_cancel_email_subject' . $fieldSuffix};
			}
			else
			{
				$emailSubject = $message->event_cancel_email_subject;
			}

			if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'event_cancel_email_body' . $fieldSuffix}))
			{
				$emailBody = $message->{'event_cancel_email_body' . $fieldSuffix};
			}
			else
			{
				$emailBody = $message->event_cancel_email_body;
			}

			$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row);

			$emailSubject = EventbookingHelper::replaceUpperCaseTags($emailSubject, $replaces);
			$emailBody    = EventbookingHelper::replaceUpperCaseTags($emailBody, $replaces);
			$emailBody    = EventbookingHelper::convertImgTags($emailBody);

			static::send($mailer, [$row->email], $emailSubject, $emailBody, $logEmails, 1, 'event_cancel_emails');
			$mailer->clearAddresses();
		}
	}

	/**
	 * Create and initialize mailer object from configuration data
	 *
	 * @param   RADConfig               $config
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return Mail
	 */
	public static function getMailer($config, $event = null)
	{
		$mailer = Factory::getMailer();

		if ($config->reply_to_email && MailHelper::isEmailAddress($config->reply_to_email))
		{
			$mailer->addReplyTo($config->reply_to_email);
		}
		elseif ($event && $event->reply_to_email && MailHelper::isEmailAddress($event->reply_to_email))
		{
			$mailer->addReplyTo($event->reply_to_email);
		}

		if ($event && $event->from_name && MailHelper::isEmailAddress($event->from_email))
		{
			$fromName  = $event->from_name;
			$fromEmail = $event->from_email;
		}
		elseif ($config->from_name && MailHelper::isEmailAddress($config->from_email))
		{
			$fromName  = $config->from_name;
			$fromEmail = $config->from_email;
		}
		else
		{
			$fromName  = Factory::getApplication()->get('fromname');
			$fromEmail = Factory::getApplication()->get('mailfrom');
		}

		$mailer->setSender([$fromEmail, $fromName]);

		$mailer->isHtml(true);

		if (empty($config->notification_emails))
		{
			$config->notification_emails = $fromEmail;
		}

		static::$fromName  = $fromName;
		static::$fromEmail = $fromEmail;

		return $mailer;
	}

	/**
	 * Add event's attachments to mailer object for sending emails to registrants
	 *
	 * @param   Mail                         $mailer
	 * @param   EventbookingTableRegistrant  $row
	 * @param   EventbookingTableEvent       $event
	 * @param   RADConfig                    $config
	 *
	 * @return array
	 */
	public static function addEventAttachments($mailer, $row, $event, $config)
	{
		$attachments = [];

		if ($config->multiple_booking)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('attachment')
				->from('#__eb_events')
				->where(
					'id IN (SELECT event_id FROM #__eb_registrants AS a WHERE a.id=' . $row->id . ' OR a.cart_id=' . $row->id . ' ORDER BY a.id)'
				);
			$db->setQuery($query);
			$attachmentFiles = $db->loadColumn();
		}
		elseif ($event->attachment)
		{
			$attachmentFiles = [$event->attachment];
		}
		else
		{
			$attachmentFiles = [];
		}

		// Remove empty value from array
		$attachmentFiles = array_filter($attachmentFiles);
		$attachmentsPath = JPATH_ROOT . '/' . ($config->attachments_path ?: 'media/com_eventbooking') . '/';

		// Add all valid attachments to email
		foreach ($attachmentFiles as $attachmentFile)
		{
			$files = explode('|', $attachmentFile);

			foreach ($files as $file)
			{
				$filePath = $attachmentsPath . $file;

				if ($file && file_exists($filePath))
				{
					$mailer->addAttachment($filePath);
					$attachments[] = $filePath;
				}
			}
		}

		return $attachments;
	}

	/**
	 * Add file uploads to the mailer object for sending to administrator
	 *
	 * @param   Mail   $mailer
	 * @param   array  $rowFields
	 * @param   array  $replaces
	 */
	public static function addRegistrationFormAttachments($mailer, $rowFields, $replaces)
	{
		$attachmentsPath = JPATH_ROOT . '/media/com_eventbooking/files';

		foreach ($rowFields as $rowField)
		{
			if ($rowField->fieldtype == 'File' && isset($replaces[$rowField->name]))
			{
				$fileName = $replaces[$rowField->name];
				static::addUploadedFileToMailer($mailer, $attachmentsPath, $fileName);
			}
		}

		if (empty($replaces['registrant_id']))
		{
			return;
		}

		/**
		 * This is just a workaround to get ID of the registration record. Ideally, we should pass ID of the registration record as a method parameter
		 * but it was not available and we don't want to introduce new method (for compatible with PHP 8.x+), so we use the below workaround
		 */

		$id    = (int) $replaces['registrant_id'];
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__eb_registrants')
			->where('group_id = ' . $id);
		$db->setQuery($query);
		$memberIds = $db->loadColumn();

		foreach ($memberIds as $memberId)
		{
			$query->clear()
				->select('a.field_value')
				->from('#__eb_field_values AS a')
				->innerJoin('#__eb_fields AS b ON a.field_id = b.id')
				->where('a.registrant_id = ' . $memberId)
				->where('b.fieldtype = ' . $db->quote('File'));
			$db->setQuery($query);

			foreach ($db->loadColumn() as $fileName)
			{
				static::addUploadedFileToMailer($mailer, $attachmentsPath, $fileName);
			}
		}
	}

	/**
	 * @param   Joomla\CMS\Mail\Mail  $mailer
	 * @param   string                $attachmentsPath
	 * @param   string                $fileName
	 *
	 * @return void
	 */
	protected static function addUploadedFileToMailer($mailer, $attachmentsPath, $fileName): void
	{
		if ($fileName && file_exists($attachmentsPath . '/' . $fileName))
		{
			$pos = strpos($fileName, '_');

			if ($pos !== false)
			{
				$originalFilename = substr($fileName, $pos + 1);
			}
			else
			{
				$originalFilename = $fileName;
			}

			$mailer->addAttachment($attachmentsPath . '/' . $fileName, $originalFilename);
		}
	}

	/**
	 * Generate PDF tickets and add to registration email
	 *
	 * @param   Mail                         $mailer
	 * @param   EventbookingTableRegistrant  $row
	 * @param   array                        $rowMembers
	 * @param   RADConfig                    $config
	 */
	protected static function addRegistrationTickets($mailer, $row, $rowMembers, $config)
	{
		if ($config->get('multiple_booking'))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from('#__eb_registrants')
				->where('id = ' . $row->id . ' OR cart_id = ' . $row->id);
			$db->setQuery($query);
			$rowRegistrants = $db->loadObjectList();

			foreach ($rowRegistrants as $rowRegistrant)
			{
				if (!$rowRegistrant->ticket_code)
				{
					continue;
				}

				$ticketFilePaths = EventbookingHelper::callOverridableHelperMethod(
					'Ticket',
					'generateRegistrationTicketsPDF',
					[$rowRegistrant, $config]
				);

				foreach ($ticketFilePaths as $ticketFilePath)
				{
					$mailer->addAttachment($ticketFilePath);
				}
			}
		}
		else
		{
			if ($row->ticket_code && $row->payment_status == 1)
			{
				if (count($rowMembers))
				{
					foreach ($rowMembers as $rowMember)
					{
						$ticketFilePaths = EventbookingHelper::callOverridableHelperMethod(
							'Ticket',
							'generateRegistrationTicketsPDF',
							[$rowMember, $config]
						);

						foreach ($ticketFilePaths as $ticketFilePath)
						{
							$mailer->addAttachment($ticketFilePath);
						}
					}
				}
				else
				{
					$ticketFilePaths = EventbookingHelper::callOverridableHelperMethod('Ticket', 'generateRegistrationTicketsPDF', [$row, $config]);

					foreach ($ticketFilePaths as $ticketFilePath)
					{
						$mailer->addAttachment($ticketFilePath);
					}
				}
			}
		}
	}

	/**
	 * @param   Mail                         $mailer
	 * @param   EventbookingTableRegistrant  $row
	 * @param   EventbookingTableEvent       $event
	 * @param   RADConfig                    $config
	 *
	 * @return string
	 */
	protected static function addRegistrationIcs($mailer, $row, $event, $config)
	{
		$icsFile     = '';
		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		if ($config->multiple_booking)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('a.id, a.title, a.event_date, a.event_end_date, a.params, a.short_description, l.lat, l.long, l.address AS location_address')
				->select('c.params AS registrant_params')
				->select($db->quoteName('l.name' . $fieldSuffix, 'location_name'))
				->from('#__eb_events AS a')
				->leftJoin('#__eb_locations AS l ON a.location_id =  l.id')
				->innerJoin('#__eb_registrants AS c ON a.id = c.event_id')
				->where("(c.id = $row->id OR c.cart_id = $row->id)")
				->order('c.id');

			if ($fieldSuffix)
			{
				EventbookingHelperDatabase::getMultilingualFields($query, ['a.title', 'a.short_description'], $fieldSuffix);
			}

			$db->setQuery($query);
			$rowEvents = $db->loadObjectList();

			$fileName = JPATH_ROOT . '/media/com_eventbooking/icsfiles/' . $row->id . '.ics';
			EventbookingHelper::generateIcs($rowEvents, static::$fromEmail, static::$fromName, $fileName);
			$mailer->addAttachment($fileName, 'Events.ics');
		}
		else
		{
			$event->registrant_params = $row->params;
			$fileName                 = JPATH_ROOT . '/media/com_eventbooking/icsfiles/' . $row->id . '.ics';
			EventbookingHelper::generateIcs([$event], static::$fromEmail, static::$fromName, $fileName);
			$mailer->addAttachment($fileName, ApplicationHelper::stringURLSafe($event->title) . '.ics');
		}

		return $icsFile;
	}

	/**
	 * Process sending after all the data has been initialized
	 *
	 * @param   Mail    $mailer
	 * @param   array   $emails
	 * @param   string  $subject
	 * @param   string  $body
	 * @param   bool    $logEmails
	 * @param   int     $sentTo
	 * @param   string  $emailType
	 */
	public static function send($mailer, $emails, $subject, $body, $logEmails = false, $sentTo = 0, $emailType = '')
	{
		if (empty($subject) || empty($body))
		{
			return;
		}

		$emails = array_map('trim', $emails);

		for ($i = 0, $n = count($emails); $i < $n; $i++)
		{
			if (!MailHelper::isEmailAddress($emails[$i]))
			{
				unset($emails[$i]);
			}
		}

		$emails = array_unique($emails);

		if (count($emails) == 0)
		{
			return;
		}

		$email     = $emails[0];
		$bccEmails = [];
		$mailer->addRecipient($email);

		if (count($emails) > 1)
		{
			unset($emails[0]);
			$bccEmails = $emails;
			$mailer->addBcc($bccEmails);
		}

		$emailBody = EventbookingHelperHtml::loadSharedLayout('emailtemplates/tmpl/email.php', ['body' => $body, 'subject' => $subject]);

		$emailBody = EventbookingHelper::callOverridableHelperMethod('Html', 'processConditionalText', [$emailBody]);

		try
		{
			$mailer->setSubject($subject)
				->setBody($emailBody)
				->Send();
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		if ($logEmails)
		{
			$db = Factory::getDbo();
			$row             = new EventbookingTableEmail($db);
			$row->sent_at    = Factory::getDate()->toSql();
			$row->email      = $email;
			$row->subject    = $subject;
			$row->body       = $body;
			$row->sent_to    = $sentTo;
			$row->email_type = $emailType;
			$row->store();

			if (count($bccEmails))
			{
				foreach ($bccEmails as $email)
				{
					$row->id    = 0;
					$row->email = $email;
					$row->store();
				}
			}
		}
	}

	/**
	 * Send email to registrant to ask them to make payment for their registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function sendRequestPaymentEmail($row, $config)
	{
		if (!MailHelper::isEmailAddress($row->email))
		{
			return;
		}

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

		EventbookingHelper::loadComponentLanguage($row->language, true);

		$message = EventbookingHelper::getMessages();

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$replaces = EventbookingHelper::callOverridableHelperMethod('Registration', 'buildTags', [$row, $form, $event, $config], 'Helper');

		if ($row->published == 0)
		{
			if ($fieldSuffix && $message->{'request_payment_email_subject_pdr' . $fieldSuffix})
			{
				$subject = $message->{'request_payment_email_subject_pdr' . $fieldSuffix};
			}
			elseif ($message->request_payment_email_subject_pdr)
			{
				$subject = $message->request_payment_email_subject_pdr;
			}
			elseif ($fieldSuffix && $message->{'request_payment_email_subject' . $fieldSuffix})
			{
				$subject = $message->{'request_payment_email_subject' . $fieldSuffix};
			}
			else
			{
				$subject = $message->request_payment_email_subject;
			}

			if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'request_payment_email_body_pdr' . $fieldSuffix}))
			{
				$body = $message->{'request_payment_email_body_pdr' . $fieldSuffix};
			}
			elseif (EventbookingHelper::isValidMessage($message->request_payment_email_body_pdr))
			{
				$body = $message->request_payment_email_body_pdr;
			}
			elseif ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'request_payment_email_body' . $fieldSuffix}))
			{
				$body = $message->{'request_payment_email_body' . $fieldSuffix};
			}
			else
			{
				$body = $message->request_payment_email_body;
			}
		}
		else
		{
			// Deposit payment with partial
			if ($row->deposit_amount > 0 && $row->payment_status != 1)
			{
				if ($fieldSuffix && strlen($message->{'deposit_payment_reminder_email_subject' . $fieldSuffix}))
				{
					$subject = $message->{'deposit_payment_reminder_email_subject' . $fieldSuffix};
				}
				else
				{
					$subject = $message->deposit_payment_reminder_email_subject;
				}

				if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'deposit_payment_reminder_email_body' . $fieldSuffix}))
				{
					$body = $message->{'deposit_payment_reminder_email_body' . $fieldSuffix};
				}
				else
				{
					$body = $message->deposit_payment_reminder_email_body;
				}

				$replaces['amount'] = EventbookingHelper::formatCurrency($row->amount - $row->deposit_amount, $config, $row->currency_symbol);
			}
			else
			{
				if ($fieldSuffix && $message->{'request_payment_email_subject' . $fieldSuffix})
				{
					$subject = $message->{'request_payment_email_subject' . $fieldSuffix};
				}
				else
				{
					$subject = $message->request_payment_email_subject;
				}

				if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'request_payment_email_body' . $fieldSuffix}))
				{
					$body = $message->{'request_payment_email_body' . $fieldSuffix};
				}
				else
				{
					$body = $message->request_payment_email_body;
				}
			}
		}

		// Make sure subject and message is configured
		if (empty($subject))
		{
			throw new Exception('Please configure request payment email subject in Waiting List Messages tab');
		}

		$mailer = static::getMailer($config, $event);

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		$logEmails = static::loggingEnabled('request_payment_emails', $config);

		static::send($mailer, [$row->email], $subject, $body, $logEmails, 2, 'request_payment_emails');
	}

	/**
	 * Send email to registrant when admin approves his registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function sendCertificateEmail($row, $config)
	{
		if (!MailHelper::isEmailAddress($row->email))
		{
			return;
		}

		EventbookingHelper::loadComponentLanguage($row->language, true);

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
		$message     = EventbookingHelper::getMessages();
		$mailer      = static::getMailer($config, $event);

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$replaces = EventbookingHelper::callOverridableHelperMethod('Registration', 'buildTags', [$row, $form, $event, $config], 'Helper');

		if ($fieldSuffix && strlen($message->{'certificate_email_subject' . $fieldSuffix}))
		{
			$subject = $message->{'certificate_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->certificate_email_subject;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{'certificate_email_body' . $fieldSuffix}))
		{
			$body = $message->{'certificate_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->certificate_email_body;
		}

		if (empty($subject))
		{
			throw new Exception('Email subject could not be empty. Go to Events Booking -> Emails & Messages and setup Certificate email subject');
		}

		if (empty($body))
		{
			throw new Exception('Email message could not be empty. Go to Events Booking -> Emails & Messages and setup Certificate email body');
		}

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);
		$body    = EventbookingHelperRegistration::processQRCODE($row, $body);

		[$fileName, $filePath] = EventbookingHelper::callOverridableHelperMethod('Certificate', 'generateCertificates', [[$row], $config]);

		$mailer->addAttachment($filePath, $fileName);

		$logEmails = static::loggingEnabled('send_certificate_emails', $config);

		static::send($mailer, [$row->email], $subject, $body, $logEmails);
	}

	/**
	 * Send email to administrator and user when user cancel his registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function sendUserCancelRegistrationEmail($row, $config)
	{
		$logEmails = static::loggingEnabled('registration_cancel_emails', $config);

		$message     = EventbookingHelper::getMessages();
		$fieldSuffix = EventbookingHelper::getFieldSuffix();
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

		$mailer = static::getMailer($config, $event);

		if ($config->multiple_booking)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
		}
		elseif ($row->is_group_billing)
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
		}
		else
		{
			$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
		}

		$form = new RADForm($rowFields);
		$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
		$form->bind($data);
		$form->buildFieldsDependency();

		$replaces = EventbookingHelper::callOverridableHelperMethod('Registration', 'buildTags', [$row, $form, $event, $config], 'Helper');

		// Do not remove this code. Override event_title to avoid showing multiple event title for shopping cart while only one registration cancelled
		$replaces['event_title'] = $event->title;

		if ($row->published == 4)
		{
			$keyPrefix = 'waiting_list_cancel';
		}
		else
		{
			$keyPrefix = 'registration_cancel';
		}

		if ($fieldSuffix && strlen(trim($message->{$keyPrefix . '_confirmation_email_subject' . $fieldSuffix})))
		{
			$subject = $message->{$keyPrefix . '_confirmation_email_subject' . $fieldSuffix};
		}
		else
		{
			$subject = $message->{$keyPrefix . '_confirmation_email_subject'};
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{$keyPrefix . '_confirmation_email_body' . $fieldSuffix}))
		{
			$body = $message->{$keyPrefix . '_confirmation_email_body' . $fieldSuffix};
		}
		else
		{
			$body = $message->{$keyPrefix . '_confirmation_email_body'};
		}

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		static::send($mailer, [$row->email], $subject, $body, $logEmails, 2, 'registration_cancel_emails');

		$mailer->clearAllRecipients();

		if ($row->published == 4)
		{
			$subjectKey = 'waiting_list_cancel_notification_email_subject';
			$bodyKey    = 'waiting_list_cancel_notification_email_body';
		}
		else
		{
			$subjectKey = 'registration_cancel_email_subject';
			$bodyKey    = 'registration_cancel_email_body';
		}

		if ($fieldSuffix && strlen(trim($message->{$subjectKey . $fieldSuffix})))
		{
			$subject = $message->{$subjectKey . $fieldSuffix};
		}
		else
		{
			$subject = $message->$subjectKey;
		}

		if ($fieldSuffix && EventbookingHelper::isValidMessage($message->{$bodyKey . $fieldSuffix}))
		{
			$body = $message->{$bodyKey . $fieldSuffix};
		}
		else
		{
			$body = $message->{$bodyKey};
		}

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		$category = EventbookingHelperDatabase::getCategory($event->main_category_id);

		EventbookingHelper::setEventStringsDataFromCategory($event, $category, ['notification_emails']);

		// Use notification emails from event if configured
		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$emails = explode(',', $config->notification_emails);

		if ($config->send_email_to_event_creator)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('email')
				->from('#__users')
				->where('id = ' . (int) $event->created_by);
			$db->setQuery($query);
			$eventCreatorEmail = $db->loadResult();

			if ($eventCreatorEmail && MailHelper::isEmailAddress($eventCreatorEmail))
			{
				$emails[] = $eventCreatorEmail;
			}
		}

		static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'registration_cancel_emails');
	}

	/**
	 * Send registrants list to event creator
	 *
	 * @param   EventbookingTableEvent  $event
	 * @param   string                  $attachment
	 * @param   Registry                $params
	 */
	public static function sendRegistrantsListEmail($event, $attachment, $params)
	{
		$config = EventbookingHelper::getConfig();

		$logEmails = static::loggingEnabled('registrants_list_email', $config);

		$message = EventbookingHelper::getMessages();

		$mailer = static::getMailer($config, $event);

		$subject = $message->send_registrants_list_email_subject;
		$body    = $message->send_registrants_list_email_body;

		$replaces = EventbookingHelperRegistration::buildEventTags($event, $config);

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		if (strlen(trim($event->notification_emails)) > 0)
		{
			$config->notification_emails = $event->notification_emails;
		}

		$emails = explode(',', $config->notification_emails);

		if ($config->send_email_to_event_creator)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('email')
				->from('#__users')
				->where('id = ' . (int) $event->created_by);
			$db->setQuery($query);
			$eventCreatorEmail = $db->loadResult();

			if ($eventCreatorEmail && MailHelper::isEmailAddress($eventCreatorEmail))
			{
				$emails[] = $eventCreatorEmail;
			}
		}

		if ($attachment)
		{
			$mailer->addAttachment($attachment);
		}

		static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'registrants_list_email');
	}

	/**
	 * @param   stdClass[]  $rows
	 * @param   Registry    $params
	 *
	 * @return void
	 */
	public static function sendIncompletePaymentRegistrationsEmails($rows, $params)
	{
		$subject = $params->get('subject', '');
		$body    = $params->get('message', '');

		$config    = EventbookingHelper::getConfig();
		$logEmails = static::loggingEnabled('icpr_notify_email', $config);

		$mailer = static::getMailer($config);

		if (trim($params->get('notification_emails')))
		{
			$emails = explode(',', $params->get('notification_emails'));
		}
		else
		{
			$emails = explode(',', $config->notification_emails);
		}

		$ids   = [];
		$links = [];

		foreach ($rows as $row)
		{
			$ids[]   = $row->id;
			$links[] = '<a href="' . Route::link(
					'administrator',
					'index.php?option=com_eventbooking&view=registrant&id=' . $row->id,
					false,
					0,
					true
				) . '"><strong>' . $row->id . '</strong></a>';
		}

		$replaces = [
			'ids'   => implode(', ', $ids),
			'links' => implode(', ', $links),
		];

		$subject = EventbookingHelper::replaceCaseInsensitiveTags($subject, $replaces);
		$body    = EventbookingHelper::replaceCaseInsensitiveTags($body, $replaces);
		$body    = EventbookingHelper::convertImgTags($body);

		static::send($mailer, $emails, $subject, $body, $logEmails, 1, 'icpr_notify_email');
	}

	/**
	 * Method to check if the given email type need to be logged
	 *
	 * @param   string     $emailType
	 * @param   RADConfig  $config
	 *
	 * @return bool
	 */
	public static function loggingEnabled($emailType, $config)
	{
		if ($config->get('log_emails'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Send reminder email to registrants
	 *
	 * @param   int       $numberEmailSendEachTime
	 * @param   string    $bccEmail
	 * @param   Registry  $params
	 *
	 * @deprecated
	 */
	public static function sendReminder($numberEmailSendEachTime = 0, $bccEmail = null, $params = null)
	{
		if ($params === null)
		{
			$params = new Registry();
		}

		static::sendReminderEmails($params, 1);
	}

	/**
	 * Send reminder email to registrants
	 *
	 * @param   int       $numberEmailSendEachTime
	 * @param   string    $bccEmail
	 * @param   Registry  $params
	 *
	 * @deprecated
	 */
	public static function sendSecondReminder($numberEmailSendEachTime = 0, $bccEmail = null, $params = null)
	{
		if ($params === null)
		{
			$params = new Registry();
		}

		static::sendReminderEmails($params, 2);
	}

	/**
	 * Send reminder email to registrants
	 *
	 * @param   int       $numberEmailSendEachTime
	 * @param   string    $bccEmail
	 * @param   Registry  $params
	 *
	 * @deprecated
	 */
	public static function sendThirdReminder($numberEmailSendEachTime = 0, $bccEmail = null, $params = null)
	{
		if ($params === null)
		{
			$params = new Registry();
		}

		static::sendReminderEmails($params, 3);
	}
}
