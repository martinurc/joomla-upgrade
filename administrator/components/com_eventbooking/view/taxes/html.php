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

class EventbookingViewTaxesHtml extends RADViewList
{
	/**
	 * The flag to mark whether the vies column should be shown
	 *
	 * @var bool
	 */
	protected $showVies;

	/**
	 * Prepare view data
	 *
	 * @return void
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();

		$filters = [];

		if ($config->hide_disable_registration_events)
		{
			$filters[] = 'registration_type != 3';
		}

		$rows      = EventbookingHelperDatabase::getAllEvents(
			$config->sort_events_dropdown,
			$config->hide_past_events_from_events_dropdown,
			$filters
		);
		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_ALL'), 'id', 'title');

		if ($config->show_event_date)
		{
			foreach ($rows as $row)
			{
				$options[] = HTMLHelper::_(
					'select.option',
					$row->id,
					$row->title . ' (' . HTMLHelper::_('date', $row->event_date, $config->date_format) . ')',
					'id',
					'title'
				);
			}
		}
		else
		{
			$options = array_merge($options, $rows);
		}

		$this->lists['filter_event_id'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_event_id',
			'class="form-select" onchange="submit();" ',
			'id',
			'title',
			$this->state->filter_event_id
		);

		// Build countries dropdown
		$options   = [];
		$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_SELECT_COUNTRY'));

		$query = $db->getQuery(true)
			->select('name AS value, name AS text')
			->from('#__eb_countries')
			->where('published = 1')
			->order('name');
		$db->setQuery($query);

		$options                       = array_merge($options, $db->loadObjectList());
		$this->lists['filter_country'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'filter_country',
			'class="form-select" onchange="submit();" ',
			'value',
			'text',
			$this->state->filter_country
		);

		$defaultCountry = $config->default_country;
		$countryCode    = EventbookingHelper::getCountryCode($defaultCountry);

		if (EventbookingHelperEuvat::isEUCountry($countryCode) && $config->eu_vat_number_field)
		{
			$this->showVies             = true;
			$options                    = [];
			$options[]                  = HTMLHelper::_('select.option', -1, Text::_('EB_VIES'));
			$options[]                  = HTMLHelper::_('select.option', 0, Text::_('JNO'));
			$options[]                  = HTMLHelper::_('select.option', 1, Text::_('JYES'));
			$this->lists['filter_vies'] = HTMLHelper::_(
				'select.genericlist',
				$options,
				'filter_vies',
				'class="form-select" onchange="submit();" ',
				'value',
				'text',
				$this->state->filter_vies
			);
		}
		else
		{
			$this->showVies = false;
		}

		// Category
		$this->lists['filter_category_id'] = EventbookingHelperHtml::getCategoryListDropdown(
			'filter_category_id',
			$this->state->filter_category_id,
			'class="input-xlarge form-select" onchange="submit();"',
			null,
			[],
			0,
			'EB_ALL_CATEGORIES'
		);

		$this->lists['filter_event_id'] = EventbookingHelperHtml::getChoicesJsSelect($this->lists['filter_event_id']);
		$this->lists['filter_country']  = EventbookingHelperHtml::getChoicesJsSelect($this->lists['filter_country']);
	}
}
