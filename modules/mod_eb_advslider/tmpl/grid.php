<?php
/**
 * @package        Joomla
 * @subpackage     Events Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 - 2024 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var stdClass                  $module
 * @var \Joomla\Registry\Registry $params
 * @var RADConfig                 $config
 * @var array                     $rows
 * @var int                       $itemId
 * @var array                     $sliderSettings
 */

$rootUri  = Uri::root(true);
$document = Factory::getApplication()->getDocument()
	->addStyleSheet($rootUri . '/media/com_eventbooking/assets/js/splide/css/themes/' . $params->get('theme', 'splide-default.min.css'))
	->addScript($rootUri . '/media/com_eventbooking/assets/js/splide/js/splide.min.js')
	->addStyleSheet(
		$rootUri . '/media/com_eventbooking/assets/css/eventgrid.min.css',
		['version' => EventbookingHelper::getInstalledVersion()]
	);

EventbookingHelper::loadComponentCssForModules();

$cssVariables = [];

if ($params->get('category_bg_color'))
{
	$cssVariables[] = '--eb-grid-default-main-category-color: '.$params->get('category_bg_color');
}

if ($params->get('event_datetime_color'))
{
	$cssVariables[] = '--eb-grid-default-datetime-color: '.$params->get('event_datetime_color');
}

if (count($cssVariables))
{
	$inlineStyles = ' style="' . implode(';', $cssVariables) . '"';
}
else
{
	$inlineStyles = '';
}

if (EventbookingHelper::isValidMessage($params->get('pre_text')))
{
	echo $params->get('pre_text');
}

EventbookingHelper::callOverridableHelperMethod('Data', 'prepareDisplayData', [$rows, 0, $config, $itemId]);

if ($params->get('show_register_buttons', 1) && $config->multiple_booking)
{
	$deviceType = EventbookingHelper::getDeviceType();

	if ($deviceType == 'mobile')
	{
		EventbookingHelperJquery::colorbox('eb-colorbox-addcart', '100%', '450px', 'false', 'false');
	}
	else
	{
		EventbookingHelperJquery::colorbox('eb-colorbox-addcart', '800px', 'false', 'false', 'false', 'false');
	}

	$baseAjaxUrl = Uri::root(true) . '/index.php?option=com_eventbooking' . EventbookingHelper::getLangLink() . '&time=' . time();
	$document->addScriptDeclaration('var EBBaseAjaxUrl = "' . $baseAjaxUrl . '";');
}
?>
<div class="eb-events-slider-container_<?php echo $module->id; ?> splide eb-events-grid-items"<?php echo $inlineStyles; ?>>
	<div class="splide__track">
		<ul class="splide__list">
			<?php
			foreach ($rows as $i => $item)
			{
				$layoutData = [
					'item'    => $item,
					'params'  => $params,
					'Itemid'  => $itemId,
					'context' => 'mod_eb_advslider',
				];
				?>
				<li class="splide__slide">
					<?php
					echo EventbookingHelperHtml::loadSharedLayout(
						'eventgrid/' . $params->get('event_item_layout', 'default') . '.php',
						$layoutData
					);
					?>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
</div>
<?php
if (EventbookingHelper::isValidMessage($params->get('post_text')))
{
	echo $params->get('post_text');
}
?>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		var splide = new Splide('.eb-events-slider-container_<?php echo $module->id; ?>', <?php echo json_encode($sliderSettings) ?>);
		splide.mount();
	});
</script>