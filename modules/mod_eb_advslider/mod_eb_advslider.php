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
$baseUrl  = Uri::base(true);

// Load component language
EventbookingHelper::loadLanguage();

$itemId = (int) $params->get('item_id', 0) ?: EventbookingHelper::getItemid();

$rows = modEBAdvSliderHelper::getData($params);

$numberEvents = count($rows);

$sliderSettings = EventbookingHelperSlider::getSliderSettings($params, $numberEvents);

require ModuleHelper::getLayoutPath('mod_eb_advslider', $params->get('layout', 'default'));
