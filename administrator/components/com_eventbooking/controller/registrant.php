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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

class EventbookingControllerRegistrant extends EventbookingController
{
	use RADControllerDownload;
	use EventbookingControllerCommonRegistrant;


	public function batch_sms()
	{
		$cid     = $this->input->get('cid', [], 'array');
		$cid     = ArrayHelper::toInteger($cid);
		$message = $this->getInput()->getString('sms_message');

		/* @var EventbookingModelRegistrant $model */
		$model = $this->getModel();

		try
		{
			$model->batchSMS($cid, $message);

			$this->setMessage(Text::_('EB_BATCH_SMS_SUCCESS'));
		}
		catch (Exception $e)
		{
			$this->setMessage($e->getMessage(), 'warning');
		}

		$this->setRedirect('index.php?option=com_eventbooking&view=registrants');
	}

	/**
	 * Export registrants into a CSV file
	 */
	public function export()
	{
		ini_set('memory_limit', '-1');
		set_time_limit(0);

		$config = EventbookingHelper::getConfig();

		// Fake config data so that registrants model get correct data for export
		if (isset($config->export_group_billing_records))
		{
			$config->set('include_group_billing_in_registrants', $config->export_group_billing_records);
		}

		if (isset($config->export_group_member_records))
		{
			$config->set('include_group_members_in_registrants', $config->export_group_member_records);
		}

		/* @var EventbookingModelRegistrants $model */
		$model = $this->getModel('registrants');

		$model->setState('limitstart', 0)
			->setState('limit', 0)
			->setState('filter_order', $config->get('export_registrants_order', 'tbl.id'))
			->setState('filter_order_Dir', $config->get('export_registrants_order_dir', 'asc'));
		$cid = $this->input->get('cid', [], 'raw');

		if ($config->export_exclude_statuses)
		{
			$model->setExcludeStatus(explode(',', $config->export_exclude_statuses));
		}

		if (!is_array($cid))
		{
			$cid = explode(',', $cid);
		}

		$cid = array_filter(ArrayHelper::toInteger($cid));

		$model->setRegistrantIds($cid);

		$rows = $model->getData();

		if (count($rows) == 0)
		{
			$this->setMessage(Text::_('There are no registrants to export'));
			$this->setRedirect('index.php?option=com_eventbooking&view=dashboard');

			return;
		}

		$eventId = (int) $model->getState('filter_event_id');

		// Trigger event for action log
		PluginHelper::importPlugin('eventbooking');
		$this->app->triggerEvent('onRegistrantsExport', [$model->getState(), count($rows)]);

		$rowFields = EventbookingHelperRegistration::getAllEventFields($eventId);

		$fieldIds = [];

		foreach ($rowFields as $rowField)
		{
			$fieldIds[] = $rowField->id;
		}

		$fieldValues = $model->getFieldsData($fieldIds);

		[$fields, $headers] = EventbookingHelper::callOverridableHelperMethod(
			'Data',
			'prepareRegistrantsExportData',
			[$rows, $config, $rowFields, $fieldValues, $eventId]
		);

		if ($exportTemplateId = $this->input->getInt('export_template', 0))
		{
			$exportTemplate = $model->getExportTemplate($exportTemplateId);

			if ($exportTemplate->fields)
			{
				$templateFields = json_decode($exportTemplate->fields, true);

				[$fields, $headers] = $this->getFieldsAndHeadersFromExportTemplates($templateFields, $fields, $headers);
			}
		}

		// Give plugin a chance to process export data
		$results = $this->app->triggerEvent('onBeforeExportDataToXLSX', [$rows, &$fields, &$headers, 'registrants_list.xlsx']);

		if (count($results) && $filename = $results[0])
		{
			// There is a plugin handles export, it returns the filename, so we just process download the file
			$this->processDownloadFile($filename);

			return;
		}

		$date = Factory::getDate('now', $this->app->get('offset'));

		$filename = 'registrants_list_on_' . $date->format('Y_m_d', true);

		$filePath = EventbookingHelper::callOverridableHelperMethod('Data', 'excelExport', [$fields, $rows, $filename, $headers]);

		if ($filePath)
		{
			$this->processDownloadFile($filePath);
		}
	}

	/**
	 * Export registrants into a PDF file
	 */
	public function export_pdf()
	{
		ini_set('memory_limit', '-1');
		set_time_limit(0);

		$filterOrder    = $this->input->getString('filter_order', 'tbl.id');
		$filterOrderDir = $this->input->getString('filter_order_Dir', 'ASC');

		$config = EventbookingHelper::getConfig();

		// Fake config data so that registrants model get correct data for export
		if (isset($config->export_group_billing_records))
		{
			$config->set('include_group_billing_in_registrants', $config->export_group_billing_records);
		}

		if (isset($config->export_group_member_records))
		{
			$config->set('include_group_members_in_registrants', $config->export_group_member_records);
		}

		$model = $this->getModel('registrants');

		/* @var EventbookingModelRegistrants $model */
		$model->setState('limitstart', 0)
			->setState('limit', 0)
			->setState('filter_order', $filterOrder)
			->setState('filter_order_Dir', $filterOrderDir);
		$cid = $this->input->get('cid', [], 'raw');

		if (!is_array($cid))
		{
			$cid = explode(',', $cid);
		}

		$cid = array_filter(ArrayHelper::toInteger($cid));

		$model->setRegistrantIds($cid);

		$rows = $model->getData();

		if (count($rows) == 0)
		{
			$this->setMessage(Text::_('There are no registrants to export'));
			$this->setRedirect('index.php?option=com_eventbooking&view=dashboard');

			return;
		}

		$eventId = (int) $model->getState('filter_event_id');

		// Trigger event for action log
		PluginHelper::importPlugin('eventbooking');
		$this->app->triggerEvent('onRegistrantsExport', [$model->getState(), count($rows)]);

		$rowFields = EventbookingHelperRegistration::getAllEventFields($eventId);

		$fieldIds = [];

		foreach ($rowFields as $rowField)
		{
			$fieldIds[] = $rowField->id;
		}

		$fieldValues = $model->getFieldsData($fieldIds);

		[$fields, $headers] = EventbookingHelper::callOverridableHelperMethod(
			'Data',
			'prepareRegistrantsExportData',
			[$rows, $config, $rowFields, $fieldValues, $eventId]
		);

		$filePath = EventbookingHelper::callOverridableHelperMethod('Helper', 'generateRegistrantsPDF', [$rows, $fields, $headers]);

		$this->processDownloadFile($filePath);
	}

	/**
	 * Export PDF invoices
	 */
	public function export_invoices()
	{
		ini_set('memory_limit', '-1');
		set_time_limit(0);

		$config = EventbookingHelper::getConfig();

		// Fake config data so that registrants model get correct data for export
		if (isset($config->export_group_billing_records))
		{
			$config->set('include_group_billing_in_registrants', $config->export_group_billing_records);
		}

		if (isset($config->export_group_member_records))
		{
			$config->set('include_group_members_in_registrants', $config->export_group_member_records);
		}

		$model = $this->getModel('registrants');

		/* @var EventbookingModelRegistrants $model */
		$model->setState('limitstart', 0)
			->setState('limit', 0)
			->setState('filter_order', 'tbl.invoice_number')
			->setState('filter_order_Dir', 'ASC');
		$cid = $this->input->get('cid', [], 'raw');

		if (!is_array($cid))
		{
			$cid = explode(',', $cid);
		}

		$cid = array_filter(ArrayHelper::toInteger($cid));

		$model->setRegistrantIds($cid);

		// Only return registrants with invoice_number
		$model->getQuery()->where('tbl.invoice_number > 0');

		$rows = $model->getData();

		if (count($rows) == 0)
		{
			$this->setMessage(Text::_('There are no registrants to export'));
			$this->setRedirect('index.php?option=com_eventbooking&view=dashboard');

			return;
		}

		// Trigger event for action log
		PluginHelper::importPlugin('eventbooking');
		$this->app->triggerEvent('onInvoicesExport', [$model->getState(), count($rows)]);

		$filePath = EventbookingHelper::callOverridableHelperMethod('Helper', 'generateRegistrantsInvoices', [$rows]);

		$this->processDownloadFile($filePath);
	}

	/**
	 * Export Tickets of selected registrations
	 *
	 * @return void
	 */
	public function export_tickets()
	{
		ini_set('memory_limit', '-1');
		set_time_limit(0);

		$config = EventbookingHelper::getConfig();

		// Fake config data so that registrants model get correct data for export
		if (isset($config->export_group_billing_records))
		{
			$config->set('include_group_billing_in_registrants', $config->export_group_billing_records);
		}

		if (isset($config->export_group_member_records))
		{
			$config->set('include_group_members_in_registrants', $config->export_group_member_records);
		}

		$model = $this->getModel('registrants');

		/* @var EventbookingModelRegistrants $model */
		$model->setState('limitstart', 0)
			->setState('limit', 0)
			->setState('filter_order', 'tbl.id')
			->setState('filter_order_Dir', 'ASC');
		$cid = $this->input->get('cid', [], 'raw');

		if (!is_array($cid))
		{
			$cid = explode(',', $cid);
		}

		$cid = array_filter(ArrayHelper::toInteger($cid));

		$model->setRegistrantIds($cid);

		// Only return registrants with ticket_code available
		$model->getQuery()->where('CHAR_LENGTH(tbl.ticket_code) > 0');

		$rows = $model->getData();

		if (count($rows) == 0)
		{
			$this->setMessage(Text::_('There are no tickets to export'));
			$this->setRedirect('index.php?option=com_eventbooking&view=registrants');

			return;
		}

		// Trigger event for action log
		PluginHelper::importPlugin('eventbooking');
		$this->app->triggerEvent('onTicketsExport', [$model->getState(), count($rows)]);

		$filePath = EventbookingHelper::callOverridableHelperMethod('Ticket', 'generateBatchTickets', [$rows]);

		$this->processDownloadFile($filePath);
	}

	/**
	 * Export registrants into a template file which can be used for modifying, then import back to system
	 */
	public function import_template()
	{
		ini_set('memory_limit', '-1');
		set_time_limit(0);

		$config = EventbookingHelper::getConfig();
		$model  = $this->getModel('registrants');

		/* @var EventbookingModelRegistrants $model */
		$model->setState('limitstart', 0)
			->setState('limit', 0)
			->setState('filter_order', 'tbl.id')
			->setState('filter_order_Dir', 'ASC');

		$cid = $this->input->get('cid', [], 'array');
		$model->setRegistrantIds($cid);

		$rows = $model->getData();

		if (count($rows) == 0)
		{
			$this->setMessage(Text::_('There are no registrants to export'));
			$this->setRedirect('index.php?option=com_eventbooking&view=dashboard');

			return;
		}

		$eventId   = (int) $model->getState('filter_event_id');
		$rowFields = EventbookingHelperRegistration::getAllEventFields($eventId);
		$fieldIds  = [];

		foreach ($rowFields as $rowField)
		{
			$fieldIds[] = $rowField->id;
		}

		$fieldValues = $model->getFieldsData($fieldIds);

		[$fields, $headers] = EventbookingHelper::callOverridableHelperMethod(
			'Data',
			'prepareRegistrantsExportData',
			[$rows, $config, $rowFields, $fieldValues, $eventId, true]
		);

		for ($i = 0, $n = count($fields); $i < $n; $i++)
		{
			if ($fields[$i] == 'registration_group_name')
			{
				unset($fields[$i]);

				continue;
			}
		}

		reset($fields);

		// Give plugin a chance to process export data
		PluginHelper::importPlugin('eventbooking');
		$headers = [];
		$results = $this->app->triggerEvent('onBeforeExportDataToXLSX', [$rows, &$fields, &$headers, 'registrants_list.xlsx']);

		if (count($results) && $filename = $results[0])
		{
			// There is a plugin handles export, it return the filename, so we just process download the file
			$this->processDownloadFile($filename);

			return;
		}

		$filePath = EventbookingHelperData::excelExport($fields, $rows, 'registrants_list', $fields);

		if ($filePath)
		{
			$this->processDownloadFile($filePath);
		}
	}

	/**
	 * Download invoice of the given registration record
	 *
	 * @throws Exception
	 */
	public function download_invoice()
	{
		$db  = Factory::getDbo();
		$id  = $this->input->getInt('id');
		$row = new EventbookingTableRegistrant($db);

		if (!$row->load($id))
		{
			throw new Exception(sprintf('There is no registration record with ID %d', $id));
		}

		if (!$row->invoice_number)
		{
			throw new Exception(sprintf('No invoice generated for registration record with ID %d yet', $id));
		}

		// Generate invoice PDF
		EventbookingHelper::loadComponentLanguage($row->language, true);
		$filePath = EventbookingHelper::callOverridableHelperMethod('Helper', 'generateInvoicePDF', [$row]);

		// Handle backward compatible in case the generateInvoicePDF was overridden and does not return file path
		if (!$filePath)
		{
			$config        = EventbookingHelper::getConfig();
			$invoiceNumber = EventbookingHelper::callOverridableHelperMethod('Helper', 'formatInvoiceNumber', [$row->invoice_number, $config, $row]);
			$filePath      = JPATH_ROOT . '/media/com_eventbooking/invoices/' . $invoiceNumber . '.pdf';
		}

		$this->processDownloadFile($filePath);
	}

	/**
	 * Method to checkin multiple registrants
	 *
	 * @return void
	 */
	public function checkin_multiple_registrants()
	{
		$cid = $this->input->get('cid', [], 'array');

		$cid = ArrayHelper::toInteger($cid);

		if (count($cid))
		{
			/* @var EventbookingModelRegistrant $model */
			$model = $this->getModel();

			try
			{
				$model->batchCheckin($cid);
				$this->setMessage(Text::_('EB_CHECKIN_REGISTRANTS_SUCCESSFULLY'));
			}
			catch (Exception $e)
			{
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_eventbooking&view=registrants');
	}

	/*
	 * Check in a registrant
	 */
	public function check_in()
	{
		$cid = $this->input->get('cid', [], 'array');

		/* @var EventbookingModelRegistrant $model */
		$model = $this->getModel();

		try
		{
			$model->checkin($cid[0], true);
			$this->setMessage(Text::_('EB_CHECKIN_SUCCESSFULLY'));
		}
		catch (Exception $e)
		{
			$this->setMessage($e->getMessage(), 'error');
		}

		$this->setRedirect('index.php?option=com_eventbooking&view=registrants');
	}

	/**
	 * Reset check in for a registrant
	 */
	public function reset_check_in()
	{
		$this->check_out();
	}

	/**
	 * Remove group member from group registration
	 */
	public function remove_group_member()
	{
		$id            = $this->input->getInt('id');
		$groupMemberId = $this->input->getInt('group_member_id');

		/* @var $model EventbookingModelRegistrant */
		$model = $this->getModel();
		$model->delete([$groupMemberId]);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(*)')
			->from('#__eb_registrants')
			->where('id = ' . $id);
		$db->setQuery($query);
		$total = $db->loadResult();

		if ($total)
		{
			// Redirect back to registrant edit screen
			$url = Route::_('index.php?option=com_eventbooking&view=registrant&id=' . $id, false);
		}
		else
		{
			// Redirect to registrants management
			$url = Route::_('index.php?option=com_eventbooking&view=registrants', false);
		}

		$this->setRedirect($url, Text::_('EB_GROUP_MEMBER_REMOVED'));
	}

	/**
	 * Method to import registrants from a csv file
	 */
	public function import()
	{
		$inputFile = $this->input->files->get('input_file');
		$fileName  = $inputFile ['name'];
		$fileExt   = strtolower(File::getExt($fileName));

		if (!in_array($fileExt, ['csv', 'xlsx']))
		{
			$this->setRedirect(
				'index.php?option=com_eventbooking&view=registrant&layout=import',
				Text::_('Invalid File Type. Only CSV, XLS and XLS file types are supported')
			);

			return;
		}

		/* @var  EventbookingModelRegistrant $model */
		$model = $this->getModel('Registrant');

		try
		{
			$numberImportedRegistrants = $model->import($inputFile['tmp_name'], $inputFile['name']);

			$this->setRedirect(
				'index.php?option=com_eventbooking&view=registrants',
				Text::sprintf('EB_NUMBER_REGISTRANTS_IMPORTED', $numberImportedRegistrants)
			);
		}
		catch (Exception $e)
		{
			$this->setRedirect('index.php?option=com_eventbooking&view=registrant&layout=import');
			$this->setMessage($e->getMessage(), 'error');
		}
	}

	public function download_ticket()
	{
		$config = EventbookingHelper::getConfig();

		$db  = Factory::getDbo();
		$row = new EventbookingTableRegistrant($db);

		$id = $this->input->getInt('id', 0);

		if (!$row->load($id))
		{
			throw new Exception(Text::_('Invalid Registration Record'), 404);
		}

		if ($row->published == 0)
		{
			throw new Exception(Text::_('Ticket is only allowed for confirmed/paid registrants'), 403);
		}

		// The person is allowed to download ticket, let process it
		$fileName = '';

		if (!$row->is_group_billing)
		{
			// Individual registration or group member record
			$ticketFilePaths = EventbookingHelper::callOverridableHelperMethod('Ticket', 'generateRegistrationTicketsPDF', [$row, $config]);
			$filePath        = $ticketFilePaths[0];
		}
		else
		{
			$filePath = EventbookingHelper::callOverridableHelperMethod('Ticket', 'generateTicketsPDF', [$row, $config]);

			// This line is added for backward compatible only, in case someone override the method generateTicketsPDF without returning file path
			if (!$filePath)
			{
				$fileName = 'ticket_' . str_pad($row->id, 5, '0', STR_PAD_LEFT) . '.pdf';
				$filePath = JPATH_ROOT . '/media/com_eventbooking/tickets/' . $fileName;
			}
		}

		$this->processDownloadFile($filePath, $fileName);
	}

	/**
	 * Refund a registration
	 *
	 * @throws Exception
	 */
	public function refund()
	{
		$id = $this->input->post->getInt('id', 0);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from('#__eb_registrants')
			->where('id = ' . $id);
		$db->setQuery($query);
		$rowRegistrant = $db->loadObject();

		if (EventbookingHelperRegistration::canRefundRegistrant($rowRegistrant))
		{
			/**@var EventbookingModelRegistrant $model * */
			$model = $this->getModel('Registrant');

			try
			{
				$model->refund($rowRegistrant);

				$this->setRedirect('index.php?option=com_eventbooking&view=registrant&id=' . $rowRegistrant->id, Text::_('EB_REGISTRATION_REFUNDED'));
			}
			catch (Exception $e)
			{
				$this->app->enqueueMessage($e->getMessage(), 'error');
				$this->setRedirect('index.php?option=com_eventbooking&view=registrant&id=' . $rowRegistrant->id, $e->getMessage(), 'error');
			}
		}
		else
		{
			throw new InvalidArgumentException(Text::_('EB_CANNOT_PROCESS_REFUND'));
		}
	}

	/**
	 * Get Managable Registrant Ids by current logged in user
	 *
	 * @param   array  $ids
	 *
	 * @return array
	 */
	protected function getManagableIds($ids)
	{
		return $ids;
	}

	/**
	 * Override getViewItemUrl method to add filter_event_id to URL on save2new
	 *
	 * @param   int  $recordId
	 *
	 * @return string
	 */
	protected function getViewItemUrl($recordId = null)
	{
		$url = parent::getViewItemUrl($recordId);

		if ($this->getTask() === 'save2new')
		{
			$url .= '&filter_event_id=' . $this->input->getInt('event_id');
		}

		return $url;
	}
}
