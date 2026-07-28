<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingViewMapHtml extends RADViewHtml
{
	/**
	 * The location to be displayed
	 *
	 * @var stdClass
	 */
	protected $location;

	/**
	 * The component's config
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * The flag to mark that this view does not have associate model
	 *
	 * @var bool
	 */
	public $hasModel = false;

	/**
	 * Prepare view data
	 *
	 * @return void
	 */
	protected function prepareView()
	{
		parent::prepareView();

		$locationId = $this->input->getInt('location_id', 0);
		$location   = EventbookingHelperDatabase::getLocation($locationId);

		if ($location->image)
		{
			$location->image = EventbookingHelperHtml::getCleanImagePath($location->image);
		}

		$this->location = $location;
		$this->config   = EventbookingHelper::getConfig();

		if ($this->config->get('map_provider', 'googlemap') === 'googlemap')
		{
			$this->setLayout('default');
		}
		else
		{
			$this->setLayout('openstreetmap');
		}
	}
}
