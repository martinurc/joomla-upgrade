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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

class plgEventBookingFields extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 */
	protected $db;

	/**
	 * Render settings form
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return array
	 */
	public function onEditEvent($row)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		ob_start();
		$this->drawSettingForm($row);

		return [
			'title' => Text::_('EB_FORM_FIELDS'),
			'form'  => ob_get_clean(),
		];
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   Boolean                 $isNew  true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		$db         = $this->db;
		$query      = $db->getQuery(true);
		$formFields = $data['registration_form_fields'] ?? [];

		if (!$isNew)
		{
			$query->delete('#__eb_field_events')
				->where('event_id = ' . $row->id);
			$db->setQuery($query)
				->execute();

			$query->clear();
		}

		if (!count($formFields))
		{
			return;
		}

		$query->insert('#__eb_field_events')
			->columns('event_id, field_id');

		foreach ($formFields as $fieldId)
		{
			$query->values(implode(',', [$row->id, (int) $fieldId]));
		}

		$db->setQuery($query)
			->execute();
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param   EventbookingTableEvent  $row
	 */
	private function drawSettingForm($row)
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->select('id, event_id, name, title')
			->from('#__eb_fields')
			->where('published = 1')
			->order('event_id, ordering');
		$db->setQuery($query);
		$rowFields = $db->loadObjectList();

		foreach ($rowFields as $rowField)
		{
			if ($rowField->event_id == -1)
			{
				continue;
			}

			$query->clear()
				->select('event_id')
				->from('#__eb_field_events')
				->where('field_id = ' . $rowField->id);
			$rowField->eventIds = $db->loadColumn();
		}

		$selectedFieldIds = [];

		// Load assigned fields for this event
		if ($row->id)
		{
			$query->clear()
				->select('field_id')
				->from('#__eb_field_events')
				->where('event_id = ' . $row->id);
			$db->setQuery($query);
			$selectedFieldIds = $db->loadColumn();
		}

		require PluginHelper::getLayoutPath($this->_type, $this->_name, 'form');
	}

	/**
	 * Method to check to see whether the plugin should run
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return bool
	 */
	private function canRun($row)
	{
		if ($row->parent_id > 0)
		{
			return false;
		}

		if ($this->app->isClient('site') && !$this->params->get('show_on_frontend'))
		{
			return false;
		}

		$config = EventbookingHelper::getConfig();

		if ($config->custom_field_by_category)
		{
			return false;
		}

		return true;
	}
}
