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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;

class plgEventBookingMap extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @param $subject
	 * @param $config
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		Factory::getLanguage()->load('plg_eventbooking_map', JPATH_ADMINISTRATOR);
	}

	/**
	 * Display event location in a map
	 *
	 * @param $row
	 *
	 * @return array|string
	 */
	public function onEventDisplay($row)
	{
		$db    = $this->db;
		$query = $db->getQuery(true);
		$query->select('a.*')
			->from('#__eb_locations AS a')
			->innerJoin('#__eb_events AS b ON a.id = b.location_id')
			->where('b.id = ' . (int) $row->id);

		if ($fieldSuffix = EventbookingHelper::getFieldSuffix())
		{
			EventbookingHelperDatabase::getMultilingualFields($query, ['a.name', 'a.alias', 'a.description'], $fieldSuffix);
		}

		$db->setQuery($query);
		$location = $db->loadObject();

		$print = $this->app->input->getInt('print', 0);

		if (
			$location === null
			|| empty($location->address)
			|| ($location->lat == 0 && $location->long == 0)
			|| $print
		)
		{
			return '';
		}
		ob_start();

		HTMLHelper::_('behavior.core');

		$config = EventbookingHelper::getConfig();

		if ($config->get('map_provider', 'googlemap') == 'googlemap')
		{
			$this->drawMap($location);
		}
		else
		{
			$this->drawOpenStreetMap($location);
		}

		$form = ob_get_clean();

		return [
			'title'    => Text::_('PLG_EB_MAP'),
			'form'     => $form,
			'name'     => $this->_name,
			'position' => $this->params->get('output_position', 'after_register_buttons'),
		];
	}

	/**
	 * Display event location in a map
	 *
	 * @param $location
	 */
	private function drawMap($location)
	{
		$config           = EventbookingHelper::getConfig();
		$rootUri          = Uri::root(true);
		$zoomLevel        = (int) $config->zoom_level ?: 14;
		$disableZoom      = $this->params->get('disable_zoom', 1) == 1 ? 'false' : 'true';
		$mapHeight        = $this->params->def('map_height', 500);
		$getDirectionLink = 'https://maps.google.com/maps?daddr=' . str_replace(' ', '+', $location->address);
		$getDirectText    = Text::_('EB_GET_DIRECTION');

		$bubbleText = <<<HTML
            <ul class="bubble">
                <li class="location_name"><h4>$location->name</h4></li>
                <li class="address">$location->address</li>
                <li class="address getdirection"><a href="$getDirectionLink" target="_blank">$getDirectText</a></li>
            </ul>
</ul>
HTML;

		Factory::getApplication()->getDocument()
			->addScript('https://maps.googleapis.com/maps/api/js?key=' . $config->get('map_api_key', '') . '&v=quarterly')
			->addScript($rootUri . '/media/com_eventbooking/js/plg-eventbooking-map-googlemap.min.js')
			->addScriptOptions('mapZoomLevel', $zoomLevel)
			->addScriptOptions('mapLocation', $location)
			->addScriptOptions('scrollwheel', (bool) $disableZoom)
			->addScriptOptions('bubbleText', $bubbleText);
		?>
		<div id="mapform">
			<div id="map_canvas" style="width: 100%; height: <?php
			echo $mapHeight; ?>px"></div>
		</div>
		<?php
	}

	/**
	 * Display location on openstreetmap
	 *
	 * @param   EventbookingTableLocation  $location
	 */
	private function drawOpenStreetMap($location)
	{
		$rootUri   = Uri::root(true);
		$config    = EventbookingHelper::getConfig();
		$zoomLevel = (int) $config->zoom_level ?: 14;
		$mapHeight = $this->params->def('map_height', 500);

		$popupContent           = [];
		$popupContent[]         = '<h4 class="eb-location-name">' . $location->name . '</h4>';
		$popupContent[]         = '<p class="eb-location-address">' . $location->address . '</p>';
		$location->popupContent = implode('', $popupContent);

		Factory::getApplication()->getDocument()
			->addScript($rootUri . '/media/com_eventbooking/assets/js/leaflet/leaflet.js')
			->addScript($rootUri . '/media/com_eventbooking/js/plg-eventbooking-map-openstreetmap.min.js')
			->addStyleSheet($rootUri . '/media/com_eventbooking/assets/js/leaflet/leaflet.css')
			->addScriptOptions('mapZoomLevel', $zoomLevel)
			->addScriptOptions('mapLocation', $location);
		?>
		<div id="mapform">
			<div id="map_canvas" style="width: 100%; height: <?php
			echo $mapHeight; ?>px"></div>
		</div>
		<?php
	}
}
