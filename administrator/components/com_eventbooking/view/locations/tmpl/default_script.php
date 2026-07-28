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

if (EventbookingHelper::JoomlaVersionGreaterThan('4.2.0'))
{
	Factory::getApplication()->getDocument()->getWebAssetManager()
		->useScript('table.columns')
		->useScript('multiselect');
}