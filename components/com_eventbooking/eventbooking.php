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

// Require library + register autoloader
require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

// Make sure common language file for the current language exists
EventbookingHelper::ensureCommonLanguageFileExist(Factory::getLanguage()->getTag());

// Load common language file, it is not loaded automatically by Joomla
Factory::getLanguage()->load('com_eventbookingcommon', JPATH_ADMINISTRATOR);
Factory::getLanguage()->load('com_eventbooking', JPATH_ROOT, null, true);

if (version_compare(JVERSION, '4.0.0', 'ge'))
{
	$source = Factory::getApplication()->input;
}
else
{
	$source = null;
}

EventbookingHelper::prepareRequestData();

$input = new RADInput($source);

$config = require JPATH_ADMINISTRATOR . '/components/com_eventbooking/config.php';

RADController::getInstance($input->getCmd('option', null), $input, $config)
	->execute()
	->redirect();
