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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class EventbookingViewEventsHtml extends RADViewList
{
	/**
	 * Component config data
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * The date picker format
	 *
	 * @var string
	 */
	protected $datePickerFormat;

	/**
	 * Prepare view data
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$user    = Factory::getUser();
		$filters = [];

		if (!$user->authorise('core.admin', 'com_eventbooking'))
		{
			$filters[] = 'submit_event_access IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')';
		}

		$this->lists['filter_category_id'] = EventbookingHelperHtml::getCategoryListDropdown(
			'filter_category_id',
			$this->state->filter_category_id,
			'class="form-select" onchange="submit();"',
			null,
			$filters
		);

		$options                           = [];
		$options[]                         = HTMLHelper::_(
			'select.option',
			0,
			Text::_('EB_SELECT_LOCATION'),
			'id',
			'name'
		);
		$options                           = array_merge($options, EventbookingHelperDatabase::getAllLocations());
		$this->lists['filter_location_id'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_location_id',
			' class="form-select" onchange="submit();" ',
			'id',
			'name',
			$this->state->filter_location_id
		);

		$options                      = [];
		$options[]                    = HTMLHelper::_('select.option', 0, Text::_('EB_EVENTS_FILTER'));
		$options[]                    = HTMLHelper::_('select.option', 1, Text::_('EB_HIDE_PAST_EVENTS'));
		$options[]                    = HTMLHelper::_('select.option', 2, Text::_('EB_HIDE_CHILDREN_EVENTS'));
		$this->lists['filter_events'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_events',
			' class="input-medium form-select" onchange="submit();" ',
			'value',
			'text',
			$this->state->filter_events
		);

		$options                      = [];
		$options[]                    = HTMLHelper::_('select.option', '', Text::_('EB_FEATURED'));
		$options[]                    = HTMLHelper::_('select.option', 1, Text::_('JYES'));
		$options[]                    = HTMLHelper::_('select.option', 0, Text::_('JNO'));

		$this->lists['filter_featured'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_featured',
			' class="input-medium form-select" onchange="submit();" ',
			'value',
			'text',
			$this->state->filter_featured
		);

		$this->config = EventbookingHelper::getConfig();

		$this->datePickerFormat = $this->config->get('date_field_format', '%Y-%m-%d');

		EventbookingHelper::displayPHPVersionWarning();
	}

	protected function addCustomToolbarButtons()
	{
		if (EventbookingHelper::isJoomla4())
		{
			// Get the toolbar object instance
			$toolbar = Toolbar::getInstance('toolbar');

			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('icon-ellipsis-h')
				->buttonClass('btn btn-action');

			$childBar = $dropdown->getChildToolbar();

			$childBar->basicButton('cancel_event')
				->text('EB_CANCEL_EVENT')
				->icon('icon-cancel')
				->task('cancel_event')
				->listCheck(true);

			$childBar->basicButton('export')
				->text('EB_EXPORT_EVENTS')
				->icon('icon-download')
				->task('export');

			$childBar->basicButton('send_registrants_list')
				->text('EB_SEND_REGISTRANTS')
				->icon('icon-envelope')
				->task('send_registrants_list');
		}
		else
		{
			ToolbarHelper::custom('cancel_event', 'cancel', 'cancel', 'EB_CANCEL_EVENT', true);
			ToolbarHelper::custom('export', 'download', 'download', 'EB_EXPORT_EVENTS', false);
			ToolbarHelper::custom('send_registrants_list', 'envelope', 'envelope', 'EB_SEND_REGISTRANTS', false);
		}
	}
}
