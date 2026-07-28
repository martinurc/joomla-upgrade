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

class EventbookingViewDiscountsHtml extends RADViewList
{
	/**
	 * The database null date
	 *
	 * @var string
	 */
	protected $nullDate;

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

		$this->nullDate = Factory::getDbo()->getNullDate();
		$this->config   = EventbookingHelper::getConfig();
	}
}
