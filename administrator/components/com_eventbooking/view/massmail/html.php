<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class EventbookingViewMassmailHtml extends RADViewHtml
{
	/**
	 * List of select lists
	 *
	 * @var array<string, mixed>
	 */
	protected $lists;

	/**
	 * Component config data
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Component messages
	 *
	 * @var RADConfig
	 */
	protected $message;

	/**
	 * Set data and display the view
	 *
	 * @return void
	 */
	public function display()
	{
		$config = EventbookingHelper::getConfig();

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
			'class="form-select input-xlarge"',
			'value',
			'text',
			$this->input->getInt('published', -1)
		);

		$lists['event_id']  = EventbookingHelperHtml::getEventsDropdown(
			EventbookingHelperDatabase::getAllEvents(),
			'event_id',
			'class="form-select input-xlarge"'
		);

		$db = $this->model->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_mmtemplates')
			->where('published = 1')
			->order('ordering');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		if (count($rows))
		{
			$options   = [];
			$options[] = HTMLHelper::_('select.option', '0', Text::_('EB_SELECT'), 'id', 'title');
			$options   = array_merge($options, $rows);

			$lists['mm_template_id'] = HTMLHelper::_(
				'select.genericlist',
				$options,
				'mm_template_id',
				'class="form-select"',
				'id',
				'title',
				0
			);
		}

		$this->lists   = $lists;
		$this->config  = $config;
		$this->message = EventbookingHelper::getMessages();

		parent::display();
	}
}
