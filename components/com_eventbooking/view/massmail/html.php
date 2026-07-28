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
use Joomla\CMS\Uri\Uri;

class EventbookingViewMassmailHtml extends RADViewHtml
{
	/**
	 * The list of selects on form
	 *
	 * @var array
	 */
	protected $lists;

	/**
	 * The component's config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Prepare view data
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		// Only users with registrants management permission can access to massmail function
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		if (!$user->authorise('eventbooking.registrantsmanagement', 'com_eventbooking'))
		{
			if ($user->guest)
			{
				$this->requestLogin();
			}
			else
			{
				$app->enqueueMessage(Text::_('NOT_AUTHORIZED'), 'error');
				$app->redirect(Uri::root(), 403);
			}
		}

		$config      = EventbookingHelper::getConfig();
		$db          = Factory::getDbo();
		$query       = $db->getQuery(true);
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		if ($fieldSuffix)
		{
			$query->select($db->quoteName(['id', 'title' . $fieldSuffix, 'event_date'], [null, 'title', null]));
		}
		else
		{
			$query->select($db->quoteName(['id', 'title', 'event_date']));
		}

		$query->from('#__eb_events')
			->where('published = 1')
			->order($config->sort_events_dropdown);

		if ($config->hide_past_events_from_events_dropdown)
		{
			$currentDate = $db->quote(HTMLHelper::_('date', 'Now', 'Y-m-d'));
			$query->where('(DATE(event_date) >= ' . $currentDate . ' OR DATE(event_end_date) >= ' . $currentDate . ')');
		}

		if ($config->only_show_registrants_of_event_owner)
		{
			$query->where('created_by = ' . (int) $user->id);
		}

		$db->setQuery($query);

		$lists['event_id'] = EventbookingHelperHtml::getEventsDropdown($db->loadObjectList(), 'event_id', ' class="input-xlarge" ');

		$options   = [];
		$options[] = HTMLHelper::_('select.option', -1, Text::_('EB_DEFAULT_STATUS'));
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_PENDING'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('EB_PAID'));

		if ($config->activate_waitinglist_feature)
		{
			$options[] = HTMLHelper::_('select.option', 3, Text::_('EB_WAITING_LIST'));
		}

		$options[] = HTMLHelper::_('select.option', 2, Text::_('EB_CANCELLED'));

		$lists['published'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'published',
			' class="form-select input-xlarge" ',
			'value',
			'text',
			$this->input->getInt('published', -1)
		);

		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
		$options[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));

		$lists['send_to_group_billing']               = HTMLHelper::_(
			'select.genericlist',
			$options,
			'send_to_group_billing',
			'class="form-select input-xlarge"',
			'value',
			'text',
			$this->input->getInt('send_to_group_billing', 1)
		);
		$lists['send_to_group_members']               = HTMLHelper::_(
			'select.genericlist',
			$options,
			'send_to_group_members',
			'class="form-select input-xlarge"',
			'value',
			'text',
			$this->input->getInt('send_to_group_members', 1)
		);
		$lists['only_send_to_checked_in_registrants'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'only_send_to_checked_in_registrants',
			'class="form-select input-xlarge"',
			'value',
			'text',
			$this->input->getInt('only_send_to_checked_in_registrants', 0)
		);

		$this->lists  = $lists;
		$this->config = $config;
	}
}
