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

if (!EventbookingHelper::isJoomla4())
{
	HTMLHelper::_('formbehavior.chosen', 'select:not(#mm_template_id)');
}
elseif (EventbookingHelper::JoomlaVersionGreaterThan('4.2.0'))
{
	Factory::getApplication()->getDocument()->getWebAssetManager()
		->useScript('table.columns')
		->useScript('multiselect');
}

Factory::getApplication()->getDocument()
	->addScript(Uri::root(true) . '/media/com_eventbooking/js/admin-registrants-default.min.js')
	->addScriptOptions('siteUrl', Uri::base(true));

Text::script('EB_SELECT_EVENT_TO_ADD_REGISTRANT', true);