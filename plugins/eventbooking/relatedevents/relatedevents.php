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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

class plgEventBookingRelatedEvents extends CMSPlugin
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
	 * Constructor.
	 *
	 * @param $subject
	 * @param $config
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		Factory::getLanguage()->load('plg_eventbooking_relatedevents', JPATH_ADMINISTRATOR);
	}

	/**
	 * Render setting form
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return mixed
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
			'title' => Text::_('PLG_EB_RELATED_EVENTS'),
			'form'  => ob_get_clean(),
		];
	}

	/**
	 * Store setting into database, in this case, use params field of events table
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   bool                    $isNew  true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		$params = new Registry($row->params);

		$params->set('related_event_ids', $data['related_event_ids'] ?? '');

		$row->params = $params->toString();

		$row->store();
	}


	/**
	 * Display event speakers
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return array|void
	 */
	public function onEventDisplay($row)
	{
		$params = new Registry($row->params);
		$eventIds = $params->get('related_event_ids', '');

		if (!$eventIds)
		{
			return;
		}

		$eventIds =  array_filter(ArrayHelper::toInteger(explode(',', $eventIds)));

		if (count($eventIds) === 0)
		{
			return;
		}

		// Get list of events
		/* @var EventbookingModelList $model */
		$model = RADModel::getTempInstance('List', 'EventbookingModel', ['table' => '#__eb_events']);

		$model->setState('event_ids', $params->get('related_event_ids', ''));
		$events = $model->getData();

		if (empty($events))
		{
			return;
		}

		// Prepare display data
		$config = EventbookingHelper::getConfig();
		$Itemid = EventbookingHelper::getItemid();

		EventbookingHelper::callOverridableHelperMethod('Data', 'prepareDisplayData', [$events, 0, $config, $Itemid]);

		return [
			'title'    => Text::_('EB_EVENT_RELATED_EVENTS'),
			'form'     => EventbookingHelperHtml::loadCommonLayout('plugins/relatedevents.php', ['events' => $events, 'params' => $this->params]),
			'position' => $this->params->get('output_position', 'before_register_buttons'),
			'name'     => $this->_name,
		];
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param   object  $row
	 */
	private function drawSettingForm($row)
	{

		if ($row->id)
		{
			$params             = new Registry($row->params);
			$relatedEventIds = $params->get('related_event_ids');
		}
		else
		{
			$relatedEventIds = '';
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
		if ($this->app->isClient('site') && !$this->params->get('show_on_frontend'))
		{
			return false;
		}

		return true;
	}
}
