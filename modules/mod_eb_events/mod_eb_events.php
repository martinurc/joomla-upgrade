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
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Variables
 *
 * @var \Joomla\Registry\Registry $params
 */

// Require library + register autoloader
require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

if (!EventbookingHelper::needToShowModule($params->get('show_on_pages', '')))
{
	return;
}

// Require module helper
require_once __DIR__ . '/helper.php';

$document = Factory::getApplication()->getDocument();
$user     = Factory::getUser();
$config   = EventbookingHelper::getConfig();
$baseUrl  = Uri::root(true);

// Load component language
EventbookingHelper::loadLanguage();

if ($params->get('show_location', 0))
{
	// Load javascript files
	EventbookingHelperModal::iframeModal('a.eb-colorbox-map', 'eb-map-modal');
}

// Load CSS
$layout = $params->get('layout', 'default');

$document->addStyleSheet($baseUrl . '/media/mod_eb_events/css/style.css', ['version' => EventbookingHelper::getInstalledVersion()]);

EventbookingHelper::loadComponentCssForModules();

$numberEventPerRow      = $params->get('event_per_row', 2);
$showCategory           = $params->get('show_category', 1);
$showLocation           = $params->get('show_location', 0);
$showThumb              = $params->get('show_thumb', 0);
$showShortDescription   = $params->get('show_short_description', 1);
$showPrice              = $params->get('show_price', 0);
$titleLinkable          = $params->get('title_linkable', 1);
$itemId                 = (int) $params->get('item_id', 0) ?: EventbookingHelper::getItemid();
$linkToRegistrationForm = (int) $params->get('link_event_to_registration_form', 0);

$params->set('item_id', $itemId);

$rows = modEBEventsHelper::getData($params);

require ModuleHelper::getLayoutPath('mod_eb_events', $layout);
