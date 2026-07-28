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
 * @var stdClass                  $module
 * @var \Joomla\Registry\Registry $params
 */

// Require library + register autoloader
require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

if (!EventbookingHelper::needToShowModule($params->get('show_on_pages', '')))
{
	return;
}

require_once dirname(__FILE__) . '/helper.php';

// Load component language
EventbookingHelper::loadLanguage();

$document = Factory::getApplication()->getDocument();
$rootUri  = Uri::root(true);

// Load css
$document->addStyleSheet($rootUri . '/media/mod_eb_googlemap/asset/style.css');
EventbookingHelper::loadComponentCssForModules();

$config = EventbookingHelper::getConfig();

// Module parameters
$width     = $params->get('width', 100);
$height    = $params->get('height', 400);
$zoomLevel = (int) $params->get('zoom_level', 14) ?: 14;
$Itemid    = (int) $params->get('Itemid') ?: EventbookingHelper::getItemid();

if (file_exists(JPATH_ROOT . '/modules/mod_eb_googlemap/asset/marker/map_marker.png'))
{
	$markerUri = $rootUri . '/modules/mod_eb_googlemap/asset/marker/map_marker.png';
}
elseif (file_exists(JPATH_ROOT . '/media/mod_eb_googlemap/asset/marker/map_marker.png'))
{
	$markerUri = $rootUri . '/media/mod_eb_googlemap/asset/marker/map_marker.png';
}
else
{
	$markerUri = $rootUri . '/media/mod_eb_googlemap/asset/marker/marker.png';
}

$locations = modEventBookingGoogleMapHelper::loadAllLocations($params, $Itemid);

// Calculate center location of the map
$option = Factory::getApplication()->input->getCmd('option');
$view   = Factory::getApplication()->input->getCmd('view');

if ($option == 'com_eventbooking' && $view == 'location')
{
	$activeLocation = EventbookingHelperDatabase::getLocation(Factory::getApplication()->input->getInt('location_id'));

	if ($activeLocation)
	{
		$homeCoordinates = $activeLocation->lat . ',' . $activeLocation->long;
	}
}

if (empty($homeCoordinates))
{
	if (trim($params->get('center_coordinates', '')))
	{
		$homeCoordinates = trim($params->get('center_coordinates'));
	}
	elseif (count($locations))
	{
		$homeCoordinates = $locations[0]->lat . ',' . $locations[0]->long;
	}
	elseif ($config->center_coordinates)
	{
		$homeCoordinates = $config->center_coordinates;
	}
	else
	{
		$homeCoordinates = '37.09024,-95.712891';
	}
}

if ($config->get('map_provider', 'googlemap') == 'googlemap')
{
	$layout = 'default';
	$document->addScript('https://maps.googleapis.com/maps/api/js?key=' . $config->get('map_api_key', '') . '&v=quarterly')
		->addScript($rootUri . '/media/com_eventbooking/js/mod-eb-googlemap.min.js');
}
else
{
	$layout = 'openstreetmap';
	$document->addScript($rootUri . '/media/com_eventbooking/assets/js/leaflet/leaflet.js')
		->addStyleSheet($rootUri . '/media/com_eventbooking/assets/js/leaflet/leaflet.css')
		->addScript($rootUri . '/media/com_eventbooking/js/mod-eb-openstreetmap.min.js');
}

$document->addScriptOptions('mapLocations', $locations)
	->addScriptOptions('homeCoordinates', explode(',', $homeCoordinates))
	->addScriptOptions('zoomLevel', $zoomLevel)
	->addScriptOptions('moduleId', $module->id)
	->addScriptOptions('markerUri', $markerUri);

require ModuleHelper::getLayoutPath('mod_eb_googlemap');
