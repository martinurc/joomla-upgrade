<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnViewImport extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{	

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors));
			return false;
		}
		JToolbarHelper::title(JText::_('COM_JSN_IMPORT_USERS'), 'modules.png');
		JsnHelperAdmin::addSubmenu('fields');
		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

}
