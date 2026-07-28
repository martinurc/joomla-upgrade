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
use Joomla\Utilities\ArrayHelper;

class EventbookingControllerEvent extends EventbookingController
{
	use RADControllerDownload;

	/**
	 * Import Events from a csv file
	 */
	public function import()
	{
		$inputFile = $this->input->files->get('input_file');
		$fileName  = $inputFile ['name'];
		$fileExt   = strtolower(File::getExt($fileName));

		if (!in_array($fileExt, ['csv', 'xlsx']))
		{
			$this->setRedirect(
				'index.php?option=com_eventbooking&view=event&layout=import',
				Text::_('Invalid File Type. Only CSV, XLS and XLS file types are supported')
			);

			return;
		}

		/* @var  EventbookingModelEvent $model */
		$model = $this->getModel('Event');

		try
		{
			$numberImportedEvents = $model->import($inputFile['tmp_name'], $inputFile['name']);
			$this->setRedirect('index.php?option=com_eventbooking&view=events', Text::sprintf('EB_NUMBER_EVENTS_IMPORTED', $numberImportedEvents));
		}
		catch (Exception $e)
		{
			$this->setRedirect('index.php?option=com_eventbooking&view=event&layout=import');
			$this->setMessage($e->getMessage(), 'error');
		}
	}

	/**
	 * Export events into an Excel File
	 */
	public function export()
	{
		set_time_limit(0);
		$model = $this->getModel('events');

		/* @var EventbookingModelEvents $model */

		$model->setState('limitstart', 0)
			->setState('limit', 0);

		$cid = $this->input->get('cid', [], 'array');
		$model->setEventIds($cid);

		$rowEvents = $model->getData();

		if (count($rowEvents) == 0)
		{
			$this->setMessage(Text::_('There are no events to export'));
			$this->setRedirect('index.php?option=com_eventbooking&view=events');

			return;
		}

		$config = EventbookingHelper::getConfig();

		$fields = [
			'id',
			'title',
			'alias',
			'category',
			'additional_categories',
			'image',
			'location',
			'event_date',
			'event_end_date',
			'cut_off_date',
			'registration_start_date',
			'individual_price',
			'price_text',
			'tax_rate',
			'event_capacity',
			'waiting_list_capacity',
			'registration_type',
			'registration_handle_url',
			'attachment',
			'short_description',
			'description',
			'event_password',
			'access',
			'registration_access',
			'featured',
			'published',
			'created_by',
			'min_group_number',
			'max_group_number',
			'enable_coupon',
			'deposit_amount',
			'deposit_type',
			'enable_cancel_registration',
			'cancel_before_date',
			'send_first_reminder',
			'send_second_reminder',
			'page_title',
			'page_heading',
			'meta_keywords',
			'meta_description',
			'discount_groups',
			'discount',
			'discount_type',
			'early_bird_discount_amount',
			'early_bird_discount_type',
			'early_bird_discount_date',
			'enable_terms_and_conditions',
			'custom_fields',
		];

		if ($config->event_custom_field)
		{
			EventbookingHelperData::prepareCustomFieldsData($rowEvents);
			$fields = array_merge($fields, array_keys($rowEvents[0]->paramData));
		}

		$fields[] = 'total_registrants';

		// Give plugin a chance to process export data
		PluginHelper::importPlugin('eventbooking');
		$headers = [];
		$results = $this->app->triggerEvent('onBeforeExportDataToXLSX', [$rowEvents, &$fields, &$headers, 'events_list.xlsx']);

		if (count($results) && $filename = $results[0])
		{
			// There is a plugin handles export, it returns the filename, so we just process download the file
			$this->processDownloadFile($filename);

			return;
		}

		// Excel only allows maximum 32767 characters for a cell
		foreach ($rowEvents as $rowEvent)
		{
			if (strlen($rowEvent->short_description) >= 32767)
			{
				$rowEvent->short_description = substr($rowEvent->short_description, 0, 32767);
			}

			if (strlen($rowEvent->description) >= 32767)
			{
				$rowEvent->description = substr($rowEvent->description, 0, 32767);
			}
		}

		$filePath = EventbookingHelperData::excelExport($fields, $rowEvents, 'events_list', $fields);

		if ($filePath)
		{
			$this->processDownloadFile($filePath);
		}
	}

	/**
	 * Cancel the selected events
	 */
	public function cancel_event()
	{
		/* @var EventbookingModelEvent $model */
		$model = $this->getModel();
		$cid   = $this->input->get('cid', [], 'array');
		$cid   = array_filter(ArrayHelper::toInteger($cid));

		if (!count($cid))
		{
			throw new Exception('You need to select one event to cancel');
		}

		try
		{
			$model->cancel($cid[0]);
			$this->setRedirect('index.php?option=com_eventbooking&view=events', Text::_('EB_EVENT_CANCELLED'));
		}
		catch (Exception $e)
		{
			$this->setRedirect('index.php?option=com_eventbooking&view=events', $e->getMessage(), 'error');
		}
	}

	/**
	 * Send registrants list to administrator and event creator
	 *
	 * @return void
	 */
	public function send_registrants_list()
	{
		/* @var EventbookingModelEvent $model */
		$model = $this->getModel();
		$cid   = $this->input->get('cid', [], 'array');
		$cid   = array_filter(ArrayHelper::toInteger($cid));

		try
		{
			foreach ($cid as $id)
			{
				$model->sendRegistrantsList($id);
			}

			$this->setRedirect('index.php?option=com_eventbooking&view=events', Text::_('EB_REGISTRANTS_LIST_SENT'));
		}
		catch (Exception $e)
		{
			$this->setRedirect('index.php?option=com_eventbooking&view=events', $e->getMessage(), 'error');
		}
	}

	/**
	 * Method to check if we can delete an event
	 *
	 * @param   int  $id
	 *
	 * @return bool
	 */
	protected function allowDelete($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__eb_registrants')
			->where('event_id = ' . $id)
			->where('(published >= 1 OR payment_method LIKE "os_offline%")');
		$db->setQuery($query);

		if ($db->loadResult() > 0)
		{
			$this->app->enqueueMessage(Text::sprintf('EB_DELETE_EVENT_NOT_ALLOWED', $id), 'error');

			return false;
		}
		
		return parent::allowDelete($id);
	}
}
