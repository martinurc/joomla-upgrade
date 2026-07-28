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
use Joomla\CMS\Toolbar\ToolbarHelper;

class EventbookingViewCouponsHtml extends RADViewList
{
	/**
	 * Discount Types
	 *
	 * @var array
	 */
	protected $discountTypes;

	/**
	 * The database null date
	 *
	 * @var string
	 */
	protected $nullDate;

	/**
	 * Teh date format
	 *
	 * @var string
	 */
	protected $dateFormat;

	/**
	 * The component config data
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * Prepare view data
	 *
	 * @return void
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$config = EventbookingHelper::getConfig();

		$filters = [];

		if ($config->hide_disable_registration_events)
		{
			$filters[] = 'registration_type != 3';
		}

		$rows = EventbookingHelperDatabase::getAllEvents($config->sort_events_dropdown, $config->hide_past_events_from_events_dropdown, $filters);

		$this->lists['filter_event_id'] = EventbookingHelperHtml::getEventsDropdown(
			$rows,
			'filter_event_id',
			'onchange="submit();" class="form-select" ',
			$this->state->filter_event_id
		);

		$discountTypes       = [0 => '%', 1 => $config->get('currency_symbol', '$'), 2 => Text::_('EB_VOUCHER')];
		$this->discountTypes = $discountTypes;
		$this->nullDate      = Factory::getDbo()->getNullDate();
		$this->dateFormat    = $config->get('date_format', 'Y-m-d');
		$this->config        = $config;
	}

	protected function addCustomToolbarButtons()
	{
		ToolbarHelper::custom('export', 'download', 'download', 'EB_EXPORT_COUPONS', false);
	}
}
