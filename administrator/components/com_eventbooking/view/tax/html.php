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

class EventbookingViewTaxHtml extends RADViewItem
{
	protected function prepareView()
	{
		parent::prepareView();

		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();

		// Category
		$this->lists['category_id'] = EventbookingHelperHtml::getCategoryListDropdown(
			'category_id',
			$this->item->category_id,
			'class="input-xlarge form-select"',
			null,
			[],
			0,
			'EB_ALL'
		);

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

		$this->lists['event_id'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'event_id',
			'class="form-select"',
			'id',
			'title',
			$this->state->filter_event_id
		);

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', Text::_('EB_ALL'));

		$query = $db->getQuery(true)
			->select('name AS value, name AS text')
			->from('#__eb_countries')
			->where('published = 1')
			->order('name');
		$db->setQuery($query);
		$options                = array_merge($options, $db->loadObjectList());
		$this->lists['country'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'country',
			'class="form-select"',
			'value',
			'text',
			$this->item->country,
			'country'
		);

		$defaultCountry = $config->default_country;
		$countryCode    = EventbookingHelper::getCountryCode($defaultCountry);

		if (EventbookingHelperEuvat::isEUCountry($countryCode) && $config->eu_vat_number_field)
		{
			$this->lists['vies'] = EventbookingHelperHtml::getBooleanInput('vies', $this->item->vies);
		}

		// States
		$options = [];

		$stateCountries = [
			'US', //United States
			'CA', //Canada
			'IN', //India
			'ES', //Spain
		];

		$options[] = HTMLHelper::_('select.option', '', Text::_('EB_ALL'));

		foreach ($stateCountries as $countryCode)
		{
			$query->clear()
				->select('id, name')
				->from('#__eb_countries')
				->where('country_2_code = ' . $db->quote($countryCode))
				->where('published = 1');
			$db->setQuery($query);
			$rowCountry = $db->loadObject();

			if (!$rowCountry)
			{
				continue;
			}

			$options[] = HTMLHelper::_('select.option', '<OPTGROUP>', $rowCountry->name);

			// Get list of states belong to this country
			$query->clear()
				->select('state_2_code AS value, state_name AS text')
				->from('#__eb_states AS a')
				->innerJoin('#__eb_countries AS b ON a.country_id = b.id ')
				->where('a.country_id = ' . $rowCountry->id);
			$db->setQuery($query);
			$options   = array_merge($options, $db->loadObjectList());
			$options[] = HTMLHelper::_('select.option', '</OPTGROUP>');
		}

		$this->lists['state'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'state',
			' class="form-control" ',
			'value',
			'text',
			$this->item->state,
			'state'
		);

		$keys = ['event_id', 'country', 'state'];

		foreach ($keys as $key)
		{
			$this->lists[$key] = EventbookingHelperHtml::getChoicesJsSelect($this->lists[$key]);
		}

		return true;
	}
}
