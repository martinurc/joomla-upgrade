<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

class EventbookingViewThemesHtml extends RADViewList
{
	/**
	 * Override add toolbar method to add custom toolbar
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('EB_THEME_MANAGEMENT'), 'generic.png');
		ToolbarHelper::publishList('publish', Text::_('EB_SET_DEFAULT'));
		ToolbarHelper::deleteList(Text::_('EB_THEME_DELETE_CONFIRM'), 'uninstall', 'Uninstall');
	}
}
