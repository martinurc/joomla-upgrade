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
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.core');

$config          = EventbookingHelper::getConfig();
$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rootUri         = Uri::root(true);
$siteUrl         = Uri::root();
$interval        = (int) Factory::getApplication()->getParams()->get('checkin_interval', 15) ?: 15;

Factory::getApplication()->getDocument()->addScript($rootUri . '/media/com_eventbooking/assets/js/html5-qrcode/html5-qrcode.min.js')
	->addScript($rootUri . '/media/com_eventbooking/js/site-checkin-default.min.js')
	->addScript($rootUri . '/media/com_eventbooking/assets/js/tingle/tingle.min.js')
	->addStyleSheet($rootUri . '/media/com_eventbooking/assets/js/tingle/tingle.min.css')
	->addScriptOptions(
		'checkinUrl',
		$rootUri . '/index.php?option=com_eventbooking&task=scan.qr_code_checkin&api_key=' . $config->get('checkin_api_key')
	)
	->addScriptOptions('checkInInterval', $interval*1000)
	->addScriptOptions('btn', $bootstrapHelper->getClassMapping('btn'))
	->addScriptOptions('btnPrimaryClass', $bootstrapHelper->getClassMapping('btn-primary'))
	->addScriptOptions('textSuccessClass', $bootstrapHelper->getClassMapping('text-success'))
	->addScriptOptions('textWarningClass', $bootstrapHelper->getClassMapping('text-warning'))
	->addScriptOptions('successAudioUrl', $siteUrl . 'media/com_eventbooking/audios/success.mp3')
	->addScriptOptions('failAudioUrl', $siteUrl . 'media/com_eventbooking/audios/fail.mp3');
?>
<div id="eb-checkin-page" class="eb-container">
	<?php
	if ($this->params->get('show_page_heading', 1))
	{
	?>
		<h1 class="eb-page-heading"><?php echo $this->escape(Text::_('EB_CHECKIN_REGISTRANT'));?></h1>
	<?php
	}
	?>
    <div id="reader"></div>
</div>