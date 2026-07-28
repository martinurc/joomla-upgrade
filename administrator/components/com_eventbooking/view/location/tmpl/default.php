<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.core');

if (EventbookingHelper::isJoomla4())
{
	$tabApiPrefix = 'uitab.';
}
else
{
	$tabApiPrefix = 'bootstrap.';
}

$rootUri = Uri::root(true);

$config      = EventbookingHelper::getConfig();
$mapProvider = $config->get('map_provider', 'googlemap');
$mapApiKey   = $config->get('map_api_key', '');
$zoomLevel   = (int) $config->get('zoom_level') ?: 14;

if ($this->item->id)
{
	$coordinates = $this->item->lat . ',' . $this->item->long;
}
elseif (trim($config->center_coordinates))
{
	$coordinates = trim($config->center_coordinates);
}
else
{
	if ($mapProvider === 'googlemap')
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
	}
	else
	{
		$coordinates = '37.09024,-95.712891';
	}
}

$coordinates = explode(',', $coordinates);

$editor       = Editor::getInstance(Factory::getApplication()->get('editor', 'none'));
$translatable = Multilanguage::isEnabled() && count($this->languages);

if ($translatable && !EventbookingHelper::isJoomla4())
{
	HTMLHelper::_('behavior.tabstate');
}

$document = Factory::getApplication()->getDocument();
$rootUri  = Uri::root(true);

if ($mapProvider === 'googlemap')
{
	$document->addScript('https://maps.googleapis.com/maps/api/js?key=' . $mapApiKey . '&v=quarterly')
		->addScript($rootUri . '/media/com_eventbooking/js/admin-location-default.min.js');
}
else
{
	$document->addScript($rootUri . '/media/com_eventbooking/assets/js/leaflet/leaflet.js')
		->addScript($rootUri . '/media/com_eventbooking/assets/js/autocomplete/jquery.autocomplete.min.js')
		->addStyleSheet($rootUri . '/media/com_eventbooking/assets/js/leaflet/leaflet.css')
		->addScript($rootUri . '/media/com_eventbooking/js/admin-location-openstreetmap.min.js')
		->addScriptOptions('baseUri', Uri::base(true));
}

$document->addScriptOptions('coordinates', $coordinates)
	->addScriptOptions('zoomLevel', $zoomLevel);

Text::script('EB_ENTER_LOCATION_NAME', true);

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();

if (EventbookingHelper::isJoomla4())
{
	$loadEditorButtons = true;
}
else
{
	$loadEditorButtons = false;
}
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
<div class="<?php echo $bootstrapHelper->getClassMapping('row-fluid'); ?>">
	<?php
	if ($translatable)
	{
		echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'field', ['active' => 'general-page', 'recall' => true]);
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'field', 'general-page', Text::_('EB_GENERAL'));
	}
	?>
	<div class="<?php echo $bootstrapHelper->getClassMapping('span6'); ?>">
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_NAME'); ?>
			</div>
			<div class="controls">
				<input class="form-control" type="text" name="name" id="name" size="50" maxlength="250" value="<?php echo $this->item->name;?>" />
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

		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_ALIAS'); ?>
			</div>
			<div class="controls">
				<input class="form-control" type="text" name="alias" id="alias" size="50" maxlength="250" value="<?php echo $this->item->alias;?>" />
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_ADDRESS'); ?>
			</div>
			<div class="controls">
				<input class="input-xxlarge form-control" type="text" name="address" id="address" size="50" autocomplete="off" maxlength="250" value="<?php echo $this->item->address;?>" />
				<ul id="eventmaps_results" style="display:none;"></ul>
			</div>
		</div>

		<?php
			if (EventbookingHelper::isModuleEnabled('mod_eb_cities'))
			{
			?>
				<div class="control-group">
					<div class="control-label">
						<?php echo Text::_('EB_CITY'); ?>
					</div>
					<div class="controls">
						<input class="form-control" type="text" name="city" id="city" size="30" maxlength="250" value="<?php echo $this->item->city;?>" />
					</div>
				</div>
			<?php
			}

			if (EventbookingHelper::isModuleEnabled('mod_eb_states'))
			{
			?>
				<div class="control-group">
					<div class="control-label">
						<?php echo Text::_('EB_STATE'); ?>
					</div>
					<div class="controls">
						<input class="form-control" type="text" name="state" id="state" size="30" maxlength="250" value="<?php echo $this->item->state;?>" />
					</div>
				</div>
			<?php
			}
		?>

		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_COORDINATES'); ?>
			</div>
			<div class="controls">
				<input class="form-control" type="text" name="coordinates" id="coordinates" size="30" maxlength="250" value="<?php echo $this->item->lat . ',' . $this->item->long;?>" />
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_LAYOUT'); ?>
			</div>
			<div class="controls">
				<?php echo $this->lists['layout']; ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_CREATED_BY'); ?>
			</div>
			<div class="controls">
				<?php echo EventbookingHelperHtml::getUserInput($this->item->user_id, 'user_id', 100) ; ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label"><?php echo Text::_('EB_IMAGE'); ?></div>
			<div class="controls">
				<?php echo EventbookingHelperHtml::getMediaInput($this->item->image, 'image'); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_DESCRIPTION'); ?>
			</div>
			<div class="controls">
				<?php echo $editor->display('description', $this->item->description, '100%', '400', '90', '10', $loadEditorButtons) ; ?>
			</div>
		</div>
		<?php
			if (Multilanguage::isEnabled())
			{
			?>
				<div class="control-group">
					<div class="control-label">
						<?php echo Text::_('EB_LANGUAGE'); ?>
					</div>
					<div class="controls">
						<?php echo $this->lists['language'] ; ?>
					</div>
				</div>
			<?php
			}
		?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_PUBLISHED') ; ?>
			</div>
			<div class="controls">
				<?php echo $this->lists['published']; ?>
			</div>
		</div>
	</div>
	<div class="<?php echo $bootstrapHelper->getClassMapping('span6'); ?>">
		<div class="control-group">
			<?php
			if ($mapProvider === 'googlemap')
			{
			?>
				<input type="button" id="btn-get-location-from-address" value="<?php echo Text::_('EB_PINPOINT'); ?> &raquo;" />
				<br/><br/>
			<?php
			}
			?>
			<div id="map-canvas" style="width: 95%; height: 400px"></div>
		</div>
	</div>

	<?php
	if ($translatable)
	{
		echo HTMLHelper::_($tabApiPrefix . 'endTab');
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'field', 'translation-page', Text::_('EB_TRANSLATION'));
		echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'field-translation',
			['active' => 'translation-page-' . $this->languages[0]->sef, 'recall' => true]);

		foreach ($this->languages as $language)
		{
			$sef = $language->sef;
			echo HTMLHelper::_($tabApiPrefix . 'addTab', 'field-translation', 'translation-page-' . $sef, $language->title . ' <img src="' . $rootUri . '/media/mod_languages/images/' . $language->image . '.gif" />');
			?>
			<div class="control-group">
				<div class="control-label">
					<?php echo  Text::_('EB_NAME'); ?>
				</div>
				<div class="controls">
					<input class="input-xlarge form-control" type="text" name="name_<?php echo $sef; ?>" id="title_<?php echo $sef; ?>" size="" maxlength="250" value="<?php echo $this->item->{'name_' . $sef}; ?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo  Text::_('EB_ALIAS'); ?>
				</div>
				<div class="controls">
					<input class="input-xlarge form-control" type="text" name="alias_<?php echo $sef; ?>" id="alias_<?php echo $sef; ?>" size="" maxlength="250" value="<?php echo $this->item->{'alias_' . $sef}; ?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<?php echo Text::_('EB_DESCRIPTION'); ?>
				</div>
				<div class="controls">
					<?php echo $editor->display('description_' . $sef, $this->item->{'description_' . $sef}, '100%', '400', '75', '10', $loadEditorButtons); ?>
				</div>
			</div>
			<?php
			echo HTMLHelper::_($tabApiPrefix . 'endTab');
		}
		echo HTMLHelper::_($tabApiPrefix . 'endTabSet');
		echo HTMLHelper::_($tabApiPrefix . 'endTab');
		echo HTMLHelper::_($tabApiPrefix . 'endTabSet');
	}
	?>
</div>
<div class="clearfix"></div>
	<input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>"/>
	<input type="hidden" name="task" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<style>
	#map-canvas img{
		max-width:none !important;
	}
</style>