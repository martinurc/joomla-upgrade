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
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.core') ;

$document = Factory::getApplication()->getDocument();
$rootUri  = Uri::root(true);
$document->addScript($rootUri . '/media/com_eventbooking/assets/js/eventbookingjq.min.js');
EventbookingHelperJquery::validateForm();

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper   = EventbookingHelperBootstrap::getInstance();
$controlGroupClass = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass = $bootstrapHelper->getClassMapping('control-label');
$controlsClass     = $bootstrapHelper->getClassMapping('controls');
$rowFluid          = $bootstrapHelper->getClassMapping('row-fluid');
$span7Class        = $bootstrapHelper->getClassMapping('span7');
$span5Class        = $bootstrapHelper->getClassMapping('span5');
$btnPrimary        = $bootstrapHelper->getClassMapping('btn btn-primary');

$config    = $this->config;
$mapApiKey = $config->get('map_api_key', '');
$zoomLevel = (int) $config->get('zoom_level') ?: 14;

if (trim($config->center_coordinates))
{
	$coordinates = $config->center_coordinates;
}
else
{
	$http     = HttpFactory::getHttp();
	$url      = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . str_replace(' ', '+', $config->default_country) . '&key=' . $mapApiKey;
	$response = $http->get($url);

	if ($response->code == 200)
	{
		$output_deals = json_decode($response->body);
		$latLng       = $output_deals->results[0]->geometry->location;
		$coordinates  = $latLng->lat . ',' . $latLng->lng;
	}
	else
	{
		$coordinates = '37.09024,-95.712891';
	}

	if (trim($coordinates) == ',')
	{
		$coordinates = '37.09024,-95.712891';
	}
}

$document->addScript('https://maps.google.com/maps/api/js?key=' . $mapApiKey . '&v=quarterly')
	->addScript($rootUri . '/media/com_eventbooking/js/admin-location-popup.min.js')
	->addScriptOptions('coordinates', explode(',', $coordinates))
	->addScriptOptions('zoomLevel', $zoomLevel)
	->addScriptOptions('baseUri', Uri::base(true));
?>
<form action="index.php?option=com_eventbooking&view=location" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
<h1 class="eb-page-heading"><?php echo $this->escape(Text::_('EB_ADD_EDIT_LOCATION')); ?></h1>
	<div class="<?php echo $rowFluid; ?>">
		<div  class="<?php echo $span5Class ?>">
			<div class="<?php echo $controlGroupClass;  ?>">
				<label class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_NAME'); ?>
					<span class="required">*</span>
				</label>
				<div class="<?php echo $controlsClass; ?>">
					<input class="form-control validate[required]" type="text" name="name" id="name" size="50" maxlength="250" value="<?php echo $this->escape($this->item->name); ?>" />
				</div>
			</div>

			<?php
			if (PluginHelper::isEnabled('gsd', 'eventbooking'))
			{
			?>
	            <div class="control-group">
	                <div class="control-label">
						<?php echo EventbookingHelperHtml::getFieldLabel('gsd_venue_mapping', Text::_('EB_GSD_VENUE_MAPPING'), Text::_('EB_GSD_VENUE_MAPPING_EXPLAIN')); ?>
	                </div>
	                <div class="controls">
	                    <input class="form-control" type="text" name="gsd_venue_mapping" id="gsd_venue_mapping" size="50" maxlength="250" value="<?php echo $this->item->gsd_venue_mapping;?>" />
	                </div>
	            </div>
			<?php
			}
			?>

			<div class="<?php echo $controlGroupClass;  ?>">
				<label class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_ADDRESS'); ?>
					<span class="required">*</span>
				</label>
				<div class="<?php echo $controlsClass; ?>">
					 <input class="form-control validate[required]" type="text" name="address" id="address" size="70" autocomplete="off" maxlength="250" value="<?php echo $this->escape($this->item->address);?>" />
					<ul id="eventmaps_results" style="display:none;"></ul>
				</div>
			</div>
			<?php
			if (EventbookingHelper::isModuleEnabled('mod_eb_cities'))
			{
			?>
				<div class="<?php echo $controlGroupClass;  ?>">
					<label class="<?php echo $controlLabelClass; ?>">
						<?php echo Text::_('EB_CITY'); ?>
					</label>
					<div class="<?php echo $controlsClass; ?>">
						<input class="form-control" type="text" name="city" id="city" size="30" maxlength="250" value="<?php echo $this->escape($this->item->city);?>" />
					</div>
				</div>
			<?php
			}

			if (EventbookingHelper::isModuleEnabled('mod_eb_states'))
			{
			?>
				<div class="<?php echo $controlGroupClass;  ?>">
					<label class="<?php echo $controlLabelClass; ?>">
						<?php echo Text::_('EB_STATE'); ?>
					</label>
					<div class="<?php echo $controlsClass; ?>">
						<input class="form-control" type="text" name="state" id="state" size="30" maxlength="250" value="<?php echo $this->escape($this->item->state);?>" />
					</div>
				</div>
			<?php
			}
			?>
			<div class="<?php echo $controlGroupClass;  ?>">
				<label class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_COORDINATES'); ?>
				</label>
				<div class="<?php echo $controlsClass; ?>">
					<input class="form-control validate[required]" type="text" name="coordinates" id="coordinates" size="30" maxlength="250" value="" />
				</div>
			</div>
			<div class="<?php echo $controlGroupClass;  ?>">
				<label class="<?php echo $controlLabelClass; ?>">
					<?php echo Text::_('EB_PUBLISHED') ; ?>
				</label>
				<?php echo $this->lists['published']; ?>
			</div>
		 </div>
		 <div class="<?php echo $span7Class ?>">
			<div class="<?php echo $controlGroupClass;  ?>">
				<div id="map-canvas" style="width: 95%; height: 350px"></div><br>
			</div>
		 </div>
	 </div>
	 <div class="<?php echo $rowFluid; ?>">
		 <button id="save_location" class="<?php echo $btnPrimary; ?>" type="submit"><span class="icon-save"></span><?php echo Text::_('EB_SAVE'); ?></button>
		<input type="button" id="btn-get-location-from-address" class="btn btn-info" value="<?php echo Text::_('EB_PINPOINT'); ?> &raquo;" />
	</div>
	<input type="hidden" name="published" value="1" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>