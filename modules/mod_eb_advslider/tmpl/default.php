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
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var stdClass                  $module
 * @var \Joomla\Registry\Registry $params
 * @var array                     $rows
 * @var int                       $itemId
 * @var array                     $sliderSettings
 */

$rootUri  = Uri::root(true);
$document = Factory::getApplication()->getDocument()
	->addStyleSheet($rootUri . '/media/com_eventbooking/assets/js/splide/css/themes/' . $params->get('theme', 'splide-default.min.css'))
	->addStyleSheet($rootUri . '/media/mod_eb_advslider/assets/css/styles.css')
	->addScript($rootUri . '/media/com_eventbooking/assets/js/splide/js/splide.min.js');

EventbookingHelper::loadComponentCssForModules();

$config = EventbookingHelper::getConfig();
$return     = base64_encode(Uri::getInstance()->toString());
$timeFormat = $config->event_time_format ?: 'g:i a';
$dateFormat = $config->date_format;

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluidClass     = $bootstrapHelper->getClassMapping('row-fluid');
$btnClass          = $bootstrapHelper->getClassMapping('btn');
$btnInverseClass   = $bootstrapHelper->getClassMapping('btn-inverse');
$iconOkClass       = $bootstrapHelper->getClassMapping('icon-ok');
$iconRemoveClass   = $bootstrapHelper->getClassMapping('icon-remove');
$iconPencilClass   = $bootstrapHelper->getClassMapping('icon-pencil');
$iconDownloadClass = $bootstrapHelper->getClassMapping('icon-download');
$iconCalendarClass = $bootstrapHelper->getClassMapping('icon-calendar');
$iconMapMakerClass = $bootstrapHelper->getClassMapping('icon-map-marker');
$clearfixClass     = $bootstrapHelper->getClassMapping('clearfix');
$btnPrimaryClass   = $bootstrapHelper->getClassMapping('btn-primary');
$btnBtnPrimary     = $bootstrapHelper->getClassMapping('btn btn-primary');

$linkThumbToEvent   = $config->get('link_thumb_to_event_detail_page', 1);

$activeCategoryId = 0;

EventbookingHelper::callOverridableHelperMethod('Data', 'prepareDisplayData', [$rows, $activeCategoryId, $config, $itemId]);

if (EventbookingHelper::isValidMessage($params->get('pre_text')))
{
	echo $params->get('pre_text');
}
?>
<div class="eb-slider-container eb-events-slider-container_<?php echo $module->id; ?> splide">
    <div class="splide__track">
        <ul class="splide__list">
	        <?php
	        foreach ($rows as $event)
	        {
		        require ModuleHelper::getLayoutPath('mod_eb_advslider', 'default_item');
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