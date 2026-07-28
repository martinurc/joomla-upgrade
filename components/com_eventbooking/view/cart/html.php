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
use Joomla\CMS\Router\Route;

class EventbookingViewCartHtml extends RADViewHtml
{
	/**
	 * URL users will be redirected to while clicking on continue URL
	 *
	 * @var string
	 */
	protected $continueUrl;

	/**
	 * Events in cart
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Component's config data
	 *
	 * @var RADConfig
	 */
	protected $config;

	/**
	 * The category id which is last accessed
	 *
	 * @var int
	 */
	protected $categoryId;

	/**
	 * The javascript string
	 *
	 * @var string
	 */
	protected $jsString;

	/**
	 * The bootstrap helper class
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * Prepare view data
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function prepareView()
	{
		parent::prepareView();

		if ($this->getLayout() != 'mini')
		{
			EventbookingHelperHtml::addOverridableScript('media/com_eventbooking/js/site-cart-default.min.js');

			$this->setLayout('default');
		}
		else
		{
			// The mini layout need jQuery, so we load it here
			EventbookingHelperJquery::loadjQuery();
		}

		$config     = EventbookingHelper::getConfig();
		$session    = Factory::getApplication()->getSession();
		$categoryId = (int) $session->get('last_category_id', 0);

		if (!$categoryId)
		{
			//Get category ID of the current event
			$cart     = new EventbookingHelperCart();
			$eventIds = $cart->getItems();

			if (count($eventIds))
			{
				$db          = Factory::getDbo();
				$query       = $db->getQuery(true);
				$lastEventId = $eventIds[count($eventIds) - 1];
				$query->select('category_id')
					->from('#__eb_event_categories')
					->where('event_id = ' . (int) $lastEventId);
				$db->setQuery($query);
				$categoryId = (int) $db->loadResult();
			}
		}

		$items = $this->model->getData();

		EventbookingHelper::callOverridableHelperMethod('Html', 'antiXSS', [$items, ['title', 'price_text']]);

		//Generate javascript string
		$jsString = " var arrEventIds = new Array() \n; var arrQuantities = new Array();\n";

		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = $items[$i];

			if ($item->event_capacity == 0)
			{
				$availableQuantity = -1;
			}
			else
			{
				$availableQuantity = $item->event_capacity - $item->total_registrants;
			}

			$jsString .= "arrEventIds[$i] = $item->id ;\n";
			$jsString .= "arrQuantities[$i] = $availableQuantity ;\n";
		}

		// Continue shopping url
		if ($session->get('add_more_events_url'))
		{
			$this->continueUrl = $session->get('add_more_events_url');
		}
		elseif ($categoryId)
		{
			$this->continueUrl = Route::_(EventbookingHelperRoute::getCategoryRoute($categoryId, $this->Itemid));
		}
		else
		{
			$this->continueUrl = Route::_('index.php?Itemid=' . EventbookingHelper::getItemid());
		}

		$query = [
			'view'   => 'register',
			'layout' => 'cart',
		];

		$menuItem = EventbookingHelperRoute::findMenuItemByQuery($query);

		if ($menuItem)
		{
			$this->Itemid = $menuItem->id;
		}

		$this->items           = $items;
		$this->config          = $config;
		$this->categoryId      = $categoryId;
		$this->jsString        = $jsString;
		$this->bootstrapHelper = EventbookingHelperBootstrap::getInstance();
	}
}
