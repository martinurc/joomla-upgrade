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

class EventbookingViewPluginsHtml extends RADViewList
{
	/**
	 * Override add toolbar method to add custom toolbar
	 */
	protected function addToolbar()
	{
		ToolbarHelper::title(Text::_('EB_PAYMENT_PLUGIN_MANAGEMENT'), 'generic.png');
		ToolbarHelper::publishList('publish');
		ToolbarHelper::unpublishList('unpublish');
		ToolbarHelper::deleteList(Text::_('Do you really want to uninstall the selected payment plugin?'), 'uninstall', 'Uninstall');
	}
}
