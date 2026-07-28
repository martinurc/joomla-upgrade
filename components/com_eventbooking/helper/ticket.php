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
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;

class EventbookingHelperTicket
{
	/**
	 * Format ticket number
	 *
	 * @param   string     $ticketPrefix
	 * @param   int        $ticketNumber
	 * @param   RADConfig  $config
	 *
	 * @return string The formatted ticket number
	 */
	public static function formatTicketNumber($ticketPrefix, $ticketNumber, $config)
	{
		return $ticketPrefix . str_pad($ticketNumber, $config->ticket_number_length ?: 5, '0', STR_PAD_LEFT);
	}

	/**
	 * Generate Ticket PDFs
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	public static function generateTicketsPDF($row, $config)
	{
		EventbookingHelper::loadLanguage();

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$rowEvent = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

		$options = self::getTicketPDFOptions($rowEvent, $config);

		if ($fieldSuffix && EventbookingHelper::isValidMessage($rowEvent->{'ticket_layout' . $fieldSuffix}))
		{
			$ticketLayout = $rowEvent->{'ticket_layout' . $fieldSuffix};
		}
		elseif (EventbookingHelper::isValidMessage($rowEvent->ticket_layout))
		{
			$ticketLayout = $rowEvent->ticket_layout;
		}
		elseif ($fieldSuffix && EventbookingHelper::isValidMessage($config->{'default_ticket_layout' . $fieldSuffix}))
		{
			$ticketLayout = $config->{'default_ticket_layout' . $fieldSuffix};
		}
		else
		{
			$ticketLayout = $config->default_ticket_layout;
		}

		$currentUserId = Factory::getUser()->id;

		if ($row->group_id > 0)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('id = ' . $row->group_id);
			$db->setQuery($query);
			$billingRow = $db->loadObject();
		}
		else
		{
			$billingRow = $row;
		}

		if (empty($billingRow))
		{
			$billingRow = $row;
		}

		$pages = [];

		$billingReplaces = EventbookingHelperRegistration::getRegistrationReplaces($billingRow, $rowEvent, $currentUserId);

		if ($row->is_group_billing)
		{
			$query->clear()
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

		if ($row->is_group_billing)
		{
			foreach ($rowMembers as $rowMember)
			{
				$replaces = EventbookingHelperRegistration::getRegistrationReplaces($rowMember, $rowEvent, $currentUserId);

				$replaces['ticket_number']     = self::formatTicketNumber($rowEvent->ticket_prefix, $rowMember->ticket_number, $config);
				$replaces['registration_date'] = HTMLHelper::_('date', $row->register_date, $config->date_format);
				$replaces['event_title']       = $rowEvent->title;

				$output = $ticketLayout;

				$output = EventbookingHelper::replaceCaseInsensitiveTags($output, $replaces);
				$output = EventbookingHelper::replaceCaseInsensitiveTags($output, $billingReplaces);

				foreach ($billingReplaces as $key => $value)
				{
					$value = EventbookingHelper::ensureValueIsString($value);
					$key   = strtoupper('BILLING_' . $key);

					$output = str_ireplace("[$key]", $value, $output);
				}

				$output = EventbookingHelperRegistration::processQRCODE($rowMember, $output, false);

				if (strpos($output, '[TICKET_NUMBER_QRCODE]') !== false)
				{
					EventbookingHelperRegistration::generateTicketNumberQrcode($replaces['ticket_number']);
					$imgTag = '<img src="media/com_eventbooking/qrcodes/' . $replaces['ticket_number'] . '.png" border="0" alt="QRCODE" />';
					$output = str_ireplace('[TICKET_NUMBER_QRCODE]', $imgTag, $output);
				}

				$output = EventbookingHelperHtml::processConditionalText($output);

				$page          = new stdClass();
				$page->content = $output;
				$pages[]       = $page;
			}
		}
		else
		{
			$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row, $rowEvent, $currentUserId, $config->multiple_booking);

			$replaces['ticket_number']     = self::formatTicketNumber($rowEvent->ticket_prefix, $row->ticket_number, $config);
			$replaces['registration_date'] = HTMLHelper::_('date', $row->register_date, $config->date_format);
			$replaces['event_title']       = $rowEvent->title;

			$ticketLayout = EventbookingHelper::replaceCaseInsensitiveTags($ticketLayout, $replaces);
			$ticketLayout = EventbookingHelper::replaceCaseInsensitiveTags($ticketLayout, $billingReplaces);

			foreach ($billingReplaces as $key => $value)
			{
				$value = EventbookingHelper::ensureValueIsString($value);
				$key   = strtoupper('BILLING_' . $key);

				$ticketLayout = str_ireplace("[$key]", $value, $ticketLayout);
			}

			$ticketLayout = EventbookingHelperRegistration::processQRCODE($row, $ticketLayout, false);

			if (strpos($ticketLayout, '[TICKET_NUMBER_QRCODE]') !== false)
			{
				EventbookingHelperRegistration::generateTicketNumberQrcode($replaces['ticket_number']);
				$imgTag       = '<img src="media/com_eventbooking/qrcodes/' . $replaces['ticket_number'] . '.png" border="0" alt="QRCODE" />';
				$ticketLayout = str_ireplace('[TICKET_NUMBER_QRCODE]', $imgTag, $ticketLayout);
			}

			$ticketLayout = EventbookingHelperHtml::processConditionalText($ticketLayout);

			$page          = new stdClass();
			$page->content = $ticketLayout;
			$pages[]       = $page;
		}

		$filePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($row->id, 5, '0', STR_PAD_LEFT) . '.pdf';

		EventbookingHelperPdf::generatePDFFile($pages, $filePath, $options);

		return $filePath;
	}

	/**
	 * Generate Ticket PDFs and return path to the files. Each ticket will be a separate PDF file for easier sending to
	 * members in a group registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 *
	 * @return []
	 */
	public static function generateRegistrationTicketsPDF($row, $config)
	{
		EventbookingHelper::loadLanguage();

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$rowEvent = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

		$options = static::getTicketPDFOptions($rowEvent, $config);

		if ($fieldSuffix && EventbookingHelper::isValidMessage($rowEvent->{'ticket_layout' . $fieldSuffix}))
		{
			$ticketLayout = $rowEvent->{'ticket_layout' . $fieldSuffix};
		}
		elseif (EventbookingHelper::isValidMessage($rowEvent->ticket_layout))
		{
			$ticketLayout = $rowEvent->ticket_layout;
		}
		elseif ($fieldSuffix && EventbookingHelper::isValidMessage($config->{'default_ticket_layout' . $fieldSuffix}))
		{
			$ticketLayout = $config->{'default_ticket_layout' . $fieldSuffix};
		}
		else
		{
			$ticketLayout = $config->default_ticket_layout;
		}

		if ($rowEvent->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $rowEvent->collect_member_information;
		}

		$currentUserId = Factory::getUser()->id;

		if ($row->group_id > 0)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('id = ' . $row->group_id);
			$db->setQuery($query);
			$billingRow = $db->loadObject();
		}
		else
		{
			$billingRow = $row;
		}

		if (empty($billingRow))
		{
			$billingRow = $row;
		}

		$ticketFilePaths = [];
		static $ticketFileNames = [];

		$billingReplaces = EventbookingHelperRegistration::getRegistrationReplaces($billingRow, $rowEvent, $currentUserId);

		if ($row->is_group_billing && $collectMemberInformation)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id);
			$db->setQuery($query);
			$rowMembers = $db->loadObjectList();

			foreach ($rowMembers as $rowMember)
			{
				$replaces = EventbookingHelperRegistration::getRegistrationReplaces($rowMember, $rowEvent, $currentUserId);

				$replaces['ticket_number']     = self::formatTicketNumber($rowEvent->ticket_prefix, $rowMember->ticket_number, $config);
				$replaces['registration_date'] = HTMLHelper::_('date', $row->register_date, $config->date_format);
				$replaces['event_title']       = $rowEvent->title;

				$output = $ticketLayout;

				$output = EventbookingHelper::replaceCaseInsensitiveTags($output, $replaces);
				$output = EventbookingHelper::replaceCaseInsensitiveTags($output, $billingReplaces);

				foreach ($billingReplaces as $key => $value)
				{
					$value = EventbookingHelper::ensureValueIsString($value);
					$key   = strtoupper('BILLING_' . $key);

					$output = str_ireplace("[$key]", $value, $output);
				}

				$output = EventbookingHelperRegistration::processQRCODE($rowMember, $output, false);

				if (strpos($output, '[TICKET_NUMBER_QRCODE]') !== false)
				{
					EventbookingHelperRegistration::generateTicketNumberQrcode($replaces['ticket_number']);
					$imgTag = '<img src="media/com_eventbooking/qrcodes/' . $replaces['ticket_number'] . '.png" border="0" alt="QRCODE" />';
					$output = str_ireplace('[TICKET_NUMBER_QRCODE]', $imgTag, $output);
				}

				$ticketFileName = File::makeSafe($replaces['ticket_number'] . '.pdf');

				// Append ID of the registration to the ticket Filename to make sure it is unique
				if ($config->multiple_booking && in_array($ticketFileName, $ticketFileNames))
				{
					$ticketFileName = $rowMember->id . '_' . $ticketFileName;
				}

				$filePath = JPATH_ROOT . '/media/com_eventbooking/tickets/' . $ticketFileName;

				$output = EventbookingHelperHtml::processConditionalText($output);

				$page          = new stdClass();
				$page->content = $output;

				EventbookingHelperPdf::generatePDFFile([$page], $filePath, $options);

				$ticketFilePaths[] = $filePath;
				$ticketFileNames[] = $ticketFileName;
			}
		}
		else
		{
			// This is for individual or group member registration record, so $enableShoppingCart parameter needs to be hardcoded to 0
			$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row, $rowEvent, $currentUserId, false);

			$replaces['ticket_number']     = self::formatTicketNumber($rowEvent->ticket_prefix, $row->ticket_number, $config);
			$replaces['registration_date'] = HTMLHelper::_('date', $row->register_date, $config->date_format);
			$replaces['event_title']       = $rowEvent->title;

			$ticketLayout = EventbookingHelper::replaceCaseInsensitiveTags($ticketLayout, $replaces);
			$ticketLayout = EventbookingHelper::replaceCaseInsensitiveTags($ticketLayout, $billingReplaces);

			foreach ($billingReplaces as $key => $value)
			{
				$value = EventbookingHelper::ensureValueIsString($value);
				$key   = strtoupper('BILLING_' . $key);

				$ticketLayout = str_ireplace("[$key]", $value, $ticketLayout);
			}

			$ticketLayout = EventbookingHelperRegistration::processQRCODE($row, $ticketLayout, false);

			if (strpos($ticketLayout, '[TICKET_NUMBER_QRCODE]') !== false)
			{
				EventbookingHelperRegistration::generateTicketNumberQrcode($replaces['ticket_number']);
				$imgTag       = '<img src="media/com_eventbooking/qrcodes/' . $replaces['ticket_number'] . '.png" border="0" alt="QRCODE" />';
				$ticketLayout = str_ireplace('[TICKET_NUMBER_QRCODE]', $imgTag, $ticketLayout);
			}

			$ticketFileName = File::makeSafe($replaces['ticket_number'] . '.pdf');

			// Append ID of the registration to the ticket Filename to make sure it is unique
			if ($config->multiple_booking && in_array($ticketFileName, $ticketFileNames))
			{
				$ticketFileName = $row->id . '_' . $ticketFileName;
			}

			$filePath = JPATH_ROOT . '/media/com_eventbooking/tickets/' . $ticketFileName;

			$ticketLayout = EventbookingHelperHtml::processConditionalText($ticketLayout);

			$page          = new stdClass();
			$page->content = $ticketLayout;

			EventbookingHelperPdf::generatePDFFile([$page], $filePath, $options);
			$ticketFilePaths[] = $filePath;
			$ticketFileNames[] = $ticketFileName;
		}

		return $ticketFilePaths;
	}

	/**
	 * Get background options for ticket
	 *
	 * @param   EventbookingTableEvent  $rowEvent
	 * @param   RADConfig               $config
	 *
	 * @return array
	 */
	protected static function getTicketPDFOptions($rowEvent, $config)
	{
		$options          = [];
		$options['title'] = 'Ticket';
		$options['type']  = 'ticket';

		if ($config->get('ticket_page_orientation'))
		{
			$options['PDF_PAGE_ORIENTATION'] = $config->get('ticket_page_orientation');
		}

		if ($config->get('ticket_page_format'))
		{
			$options['PDF_PAGE_FORMAT'] = $config->get('ticket_page_format');
		}

		if ($rowEvent->ticket_bg_image)
		{
			$backgroundImage = $rowEvent->ticket_bg_image;
		}
		else
		{
			$backgroundImage = $config->get('default_ticket_bg_image');
		}

		if ($backgroundImage)
		{
			$backgroundImage = EventbookingHelperHtml::getCleanImagePath($backgroundImage);
		}

		if (!$backgroundImage || !file_exists(JPATH_ROOT . '/' . $backgroundImage))
		{
			return $options;
		}

		$backgroundImagePath = JPATH_ROOT . '/' . $backgroundImage;

		if ($rowEvent->ticket_bg_left > 0)
		{
			$ticketBgLeft = $rowEvent->ticket_bg_left;
		}
		elseif ($config->default_ticket_bg_left > 0)
		{
			$ticketBgLeft = $config->default_ticket_bg_left;
		}
		else
		{
			$ticketBgLeft = 0;
		}

		if ($rowEvent->ticket_bg_top > 0)
		{
			$ticketBgTop = $rowEvent->ticket_bg_top;
		}
		elseif ($config->default_ticket_bg_top > 0)
		{
			$ticketBgTop = $config->default_ticket_bg_top;
		}
		else
		{
			$ticketBgTop = 0;
		}

		if ($rowEvent->ticket_bg_width > 0)
		{
			$ticketBgWidth = $rowEvent->ticket_bg_width;
		}
		elseif ($config->default_ticket_bg_width > 0)
		{
			$ticketBgWidth = $config->default_ticket_bg_width;
		}
		else
		{
			$ticketBgWidth = 0;
		}

		if ($rowEvent->ticket_bg_height > 0)
		{
			$ticketBgHeight = $rowEvent->ticket_bg_height;
		}
		elseif ($config->default_ticket_bg_height > 0)
		{
			$ticketBgHeight = $config->default_ticket_bg_height;
		}
		else
		{
			$ticketBgHeight = 0;
		}

		$options['bg_image']  = $backgroundImagePath;
		$options['bg_left']   = $ticketBgLeft;
		$options['bg_top']    = $ticketBgTop;
		$options['bg_width']  = $ticketBgWidth;
		$options['bg_height'] = $ticketBgHeight;

		return $options;
	}

	/**
	 * Generate TICKET_QRCODE
	 *
	 * @param $row
	 */
	public static function generateTicketQrcode($row)
	{
		EventbookingHelper::callOverridableHelperMethod('Registration', 'generateTicketQrcode', [$row]);
	}

	/**
	 * Process QRCODE for ticket. Support [QRCODE] and [TICKET_QRCODE] tag
	 *
	 * @param $row
	 * @param $output
	 *
	 * @return mixed
	 */
	protected static function processQRCODE($row, $output)
	{
		return EventbookingHelperRegistration::processQRCODE($row, $output, false);
	}

	/**
	 * Set background image for ticket
	 *
	 * @param   TCPDF                   $pdf
	 * @param   EventbookingTableEvent  $rowEvent
	 * @param   RADConfig               $config
	 *
	 * @return void
	 */
	public static function setTicketBackgroundImage($pdf, $rowEvent, $config)
	{
		if ($rowEvent->ticket_bg_image)
		{
			$backgroundImage = $rowEvent->ticket_bg_image;
		}
		else
		{
			$backgroundImage = $config->get('default_ticket_bg_image');
		}

		if (!$backgroundImage || !file_exists(JPATH_ROOT . '/' . $backgroundImage))
		{
			return;
		}

		$backgroundImagePath = JPATH_ROOT . '/' . $backgroundImage;

		if ($rowEvent->ticket_bg_left > 0)
		{
			$ticketBgLeft = $rowEvent->ticket_bg_left;
		}
		elseif ($config->default_ticket_bg_left > 0)
		{
			$ticketBgLeft = $config->default_ticket_bg_left;
		}
		else
		{
			$ticketBgLeft = 0;
		}

		if ($rowEvent->ticket_bg_top > 0)
		{
			$ticketBgTop = $rowEvent->ticket_bg_top;
		}
		elseif ($config->default_ticket_bg_top > 0)
		{
			$ticketBgTop = $config->default_ticket_bg_top;
		}
		else
		{
			$ticketBgTop = 0;
		}

		if ($rowEvent->ticket_bg_width > 0)
		{
			$ticketBgWidth = $rowEvent->ticket_bg_width;
		}
		elseif ($config->default_ticket_bg_width > 0)
		{
			$ticketBgWidth = $config->default_ticket_bg_width;
		}
		else
		{
			$ticketBgWidth = 0;
		}

		if ($rowEvent->ticket_bg_height > 0)
		{
			$ticketBgHeight = $rowEvent->ticket_bg_height;
		}
		elseif ($config->default_ticket_bg_height > 0)
		{
			$ticketBgHeight = $config->default_ticket_bg_height;
		}
		else
		{
			$ticketBgHeight = 0;
		}

		// Get current  break margin
		$breakMargin = $pdf->getBreakMargin();
		// get current auto-page-break mode
		$autoPageBreak = $pdf->getAutoPageBreak();
		// disable auto-page-break
		$pdf->SetAutoPageBreak(false, 0);
		// set background image
		$pdf->Image($backgroundImagePath, $ticketBgLeft, $ticketBgTop, $ticketBgWidth, $ticketBgHeight);
		// restore auto-page-break status
		$pdf->SetAutoPageBreak($autoPageBreak, $breakMargin);
		// set the starting point for the page content
		$pdf->setPageMark();
	}

	/**
	 * Generate batch tickets
	 *
	 * @param   array  $rows
	 */
	public static function generateBatchTickets($rows)
	{
		// Load frontend language
		EventbookingHelper::loadLanguage();

		$pages = [];

		foreach ($rows as $row)
		{
			$pages = array_merge($pages, static::getTicketOutputForRegistration($row));
		}

		$filename = File::makeSafe('tickets_' . Factory::getDate()->toSql() . '.pdf');

		$filePath = JPATH_ROOT . '/media/com_eventbooking/invoices/' . $filename;

		EventbookingHelperPdf::generatePDFFile($pages, $filePath, ['title' => 'Tickets', 'type' => 'ticket']);

		return $filePath;
	}

	public static function getTicketOutputForRegistration($row)
	{
		$pages  = [];
		$config = EventbookingHelper::getConfig();

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$rowEvent = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);

		$options = static::getTicketPDFOptions($rowEvent, $config);

		if ($fieldSuffix && EventbookingHelper::isValidMessage($rowEvent->{'ticket_layout' . $fieldSuffix}))
		{
			$ticketLayout = $rowEvent->{'ticket_layout' . $fieldSuffix};
		}
		elseif (EventbookingHelper::isValidMessage($rowEvent->ticket_layout))
		{
			$ticketLayout = $rowEvent->ticket_layout;
		}
		elseif ($fieldSuffix && EventbookingHelper::isValidMessage($config->{'default_ticket_layout' . $fieldSuffix}))
		{
			$ticketLayout = $config->{'default_ticket_layout' . $fieldSuffix};
		}
		else
		{
			$ticketLayout = $config->default_ticket_layout;
		}

		if ($rowEvent->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $rowEvent->collect_member_information;
		}

		$currentUserId = Factory::getUser()->id;

		if ($row->group_id > 0)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('id = ' . $row->group_id);
			$db->setQuery($query);
			$billingRow = $db->loadObject();
		}
		else
		{
			$billingRow = $row;
		}

		if (empty($billingRow))
		{
			$billingRow = $row;
		}

		$billingReplaces = EventbookingHelperRegistration::getRegistrationReplaces($billingRow, $rowEvent, $currentUserId);

		if ($row->is_group_billing && $collectMemberInformation)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id);
			$db->setQuery($query);
			$rowMembers = $db->loadObjectList();

			foreach ($rowMembers as $rowMember)
			{
				$replaces = EventbookingHelperRegistration::getRegistrationReplaces($rowMember, $rowEvent, $currentUserId);

				$replaces['ticket_number']     = self::formatTicketNumber($rowEvent->ticket_prefix, $rowMember->ticket_number, $config);
				$replaces['registration_date'] = HTMLHelper::_('date', $row->register_date, $config->date_format);
				$replaces['event_title']       = $rowEvent->title;

				$output = $ticketLayout;

				$output = EventbookingHelper::replaceCaseInsensitiveTags($output, $replaces);
				$output = EventbookingHelper::replaceCaseInsensitiveTags($output, $billingReplaces);

				foreach ($billingReplaces as $key => $value)
				{
					$value = EventbookingHelper::ensureValueIsString($value);
					$key   = strtoupper('BILLING_' . $key);

					$output = str_ireplace("[$key]", $value, $output);
				}

				$output = EventbookingHelperRegistration::processQRCODE($rowMember, $output, false);

				if (strpos($output, '[TICKET_NUMBER_QRCODE]') !== false)
				{
					EventbookingHelperRegistration::generateTicketNumberQrcode($replaces['ticket_number']);
					$imgTag = '<img src="media/com_eventbooking/qrcodes/' . $replaces['ticket_number'] . '.png" border="0" alt="QRCODE" />';
					$output = str_ireplace('[TICKET_NUMBER_QRCODE]', $imgTag, $output);
				}

				$page          = new stdClass();
				$page->content = $output;
				$page->options = $options;

				$pages[] = $page;
			}
		}
		else
		{
			$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row, $rowEvent, $currentUserId, $config->multiple_booking);

			$replaces['ticket_number']     = self::formatTicketNumber($rowEvent->ticket_prefix, $row->ticket_number, $config);
			$replaces['registration_date'] = HTMLHelper::_('date', $row->register_date, $config->date_format);
			$replaces['event_title']       = $rowEvent->title;

			$ticketLayout = EventbookingHelper::replaceCaseInsensitiveTags($ticketLayout, $replaces);
			$ticketLayout = EventbookingHelper::replaceCaseInsensitiveTags($ticketLayout, $billingReplaces);

			foreach ($billingReplaces as $key => $value)
			{
				$value = EventbookingHelper::ensureValueIsString($value);
				$key   = strtoupper('BILLING_' . $key);

				$ticketLayout = str_ireplace("[$key]", $value, $ticketLayout);
			}

			$ticketLayout = EventbookingHelperRegistration::processQRCODE($row, $ticketLayout, false);

			if (strpos($ticketLayout, '[TICKET_NUMBER_QRCODE]') !== false)
			{
				EventbookingHelperRegistration::generateTicketNumberQrcode($replaces['ticket_number']);
				$imgTag       = '<img src="media/com_eventbooking/qrcodes/' . $replaces['ticket_number'] . '.png" border="0" alt="QRCODE" />';
				$ticketLayout = str_ireplace('[TICKET_NUMBER_QRCODE]', $imgTag, $ticketLayout);
			}

			$page          = new stdClass();
			$page->content = $ticketLayout;
			$page->options = $options;

			$pages[] = $page;
		}

		return $pages;
	}
}
