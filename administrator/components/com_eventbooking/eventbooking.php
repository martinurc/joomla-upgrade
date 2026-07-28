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
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;

//Basic ACL support
if (!Factory::getUser()->authorise('core.manage', 'com_eventbooking'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Require library + register autoloader
require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

// Make sure common language file for the current language exists
EventbookingHelper::ensureCommonLanguageFileExist(Factory::getLanguage()->getTag());

// Load common language file, it is not loaded automatically by Joomla
Factory::getLanguage()->load('com_eventbookingcommon', JPATH_ADMINISTRATOR);
Factory::getLanguage()->load('com_eventbooking', JPATH_ADMINISTRATOR, null, true);

if (Multilanguage::isEnabled() && !EventbookingHelper::isSynchronized())
{
	EventbookingHelper::callOverridableHelperMethod('Helper', 'setupMultilingual');
}

if (isset($_POST['language']))
{
	$_REQUEST['language'] = $_POST['language'];
}

$config = require JPATH_ADMINISTRATOR . '/components/com_eventbooking/config.php';
$input  = new RADInput();
RADController::getInstance($input->getCmd('option'), $input, $config)
	->execute()
	->redirect();
