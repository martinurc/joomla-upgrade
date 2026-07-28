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
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 * -----------------
 * @var   int $w
 */

$document = Factory::getApplication()->getDocument();

$document->addScript(Uri::root(true) . '/media/com_eventbooking/assets/js/responsive-auto-height.min.js', [], ['defer' => true]);

if ($this->config->show_thumb_in_calendar)
{
	$equalHeightScript[] = 'window.addEventListener("load", function() {';
}
else
{
	$equalHeightScript[] = 'document.addEventListener("DOMContentLoaded", function() {';
}

for ($i = 0 ; $i < $w; $i++)
{
	$equalHeightScript[] = 'new ResponsiveAutoHeight("li.eb-calendar-row-' . $i . '");';
}

$equalHeightScript[] = '});';

$document->addScriptDeclaration(implode("\r\n", $equalHeightScript));

if ($this->config->display_event_in_tooltip)
{
	$document->addScriptDeclaration("
		document.addEventListener('DOMContentLoaded', function () {
            var tooltipOptions = {'html' : true, 'sanitize': false};      
                if (window.jQuery && window.jQuery().tooltip){
                    window.jQuery('#eb-calendar-page').find('.eb-calendar-tooltip').tooltip(tooltipOptions);
                } else if (bootstrap.Tooltip) {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('.eb-calendar-tooltip'));
					var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
					  return new bootstrap.Tooltip(tooltipTriggerEl, tooltipOptions);
					});                                     
                }     
        });      
    ");
}
