<?php
/**
* @version 3.0.0
* @package RSForm! Pro
* @copyright (C) 2007-2021 www.rsjoomla.com
* @license GPL, https://www.gnu.org/copyleft/gpl.html
*/

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

// Check if the helper exists
$helper = JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/rsform.php';

if (file_exists($helper))
{
	// Load Helper functions
	require_once $helper;

	// Params
	$formId			 = (int) $params->def('formId', 1);
	$moduleclass_sfx = $params->def('moduleclass_sfx', '');

	Factory::getLanguage()->load('com_rsform', JPATH_SITE);

	// Display template
	require ModuleHelper::getLayoutPath('mod_rsform', $params->get('layout', 'default'));
}