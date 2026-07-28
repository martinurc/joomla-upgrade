<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnViewFields extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		$this->checklist();
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');

		foreach($this->items as $item)
		{
			$registry = new JRegistry;
			$registry->loadString($item->params);
			$item->params = $registry;
		}
		
		$db=JFactory::getDbo();
		$query = $db->getQuery(true);
		$query = "SHOW COLUMNS FROM #__jsn_users";
		$db->setQuery( $query );
		$result=$db->loadObjectList();
		$columns=array();
		foreach($result as $column)
		{
			$columns[]=$column->Field;
		}
		$this->columns=$columns;

		JsnHelperAdmin::addSubmenu('fields');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors));
			return false;
		}
		// Preprocess the list of items to find ordering divisions.
		foreach ($this->items as &$item)
		{
			$this->ordering[$item->parent_id][] = $item->id;
		}

		// Levels filter.
		$options	= array();
		$options[]	= JHtml::_('select.option', '1', JText::_('J1'));
		$options[]	= JHtml::_('select.option', '2', JText::_('J2'));
		$options[]	= JHtml::_('select.option', '3', JText::_('J3'));
		$options[]	= JHtml::_('select.option', '4', JText::_('J4'));
		$options[]	= JHtml::_('select.option', '5', JText::_('J5'));
		$options[]	= JHtml::_('select.option', '6', JText::_('J6'));
		$options[]	= JHtml::_('select.option', '7', JText::_('J7'));
		$options[]	= JHtml::_('select.option', '8', JText::_('J8'));
		$options[]	= JHtml::_('select.option', '9', JText::_('J9'));
		$options[]	= JHtml::_('select.option', '10', JText::_('J10'));

		$this->f_levels = $options;

		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since   3.1
	 */
	protected function addToolbar()
	{
		$state	= $this->get('State');
		$canDo	= JsnHelperAdmin::getActions($state->get('filter.parent_id'));
		$user	= JFactory::getUser();
		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');

		JToolbarHelper::title(JText::_('COM_JSN_MANAGER_FIELDS'), 'modules.png');

		if ($canDo->get('core.create'))
		{
			JToolbarHelper::custom('field.addgroup','new','new',JText::_('COM_JSN_NEW_FIELDGROUP'),false,false);
			JToolbarHelper::addNew('field.add',JText::_('COM_JSN_NEW_FIELD'));
		}

		if ($canDo->get('core.edit'))
		{
			//JToolbarHelper::editList('field.edit'); Not work with fieldgroups
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('fields.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('fields.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::archiveList('fields.archive');
		}
		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::checkin('fields.checkin');
		}
		if ($state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'fields.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('fields.trash');
		}
		// Add a batch button
		if ($user->authorise('core.edit'))
		{
			JHtml::_('bootstrap.modal', 'collapseModal');
			$title = JText::_('JTOOLBAR_BATCH');
			$dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
						<i class=\"icon-checkbox-partial\" title=\"$title\"></i>
						$title</button>";
			$bar->appendButton('Custom', $dhtml, 'batch');
		}
		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::custom('syncuser','refresh','','Sync User',false);
		}
		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::preferences('com_jsn');
		}
		//JToolbarHelper::help('JHELP_COMPONENTS_FIELDS_MANAGER');

		JHtmlSidebar::setAction('index.php?option=com_jsn&view=fields');

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_PUBLISHED'),
			'filter_published',
			JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
		);

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);

		/*JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_LANGUAGE'),
			'filter_language',
			JHtml::_('select.options', JHtml::_('contentlanguage.existing', true, true), 'value', 'text', $this->state->get('filter.language'))
		);*/
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		return array(
			'a.lft' => JText::_('JGRID_HEADING_ORDERING'),
			'a.state' => JText::_('JSTATUS'),
			'a.title' => JText::_('JGLOBAL_TITLE'),
			'a.access' => JText::_('JGRID_HEADING_ACCESS'),
			'language' => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}
	
	protected function checklist()
	{
		$app=JFactory::getApplication();
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->select('id')->from('#__menu')->where('link = "index.php?option=com_jsn&view=profile" AND published = 1');
		$db->setQuery($query);
		if(!$db->loadResult()) $app->enqueueMessage(JText::_('COM_JSN_CHECKLIST_ALERT_EASYPROFILE_MENU_ITEM'),'notice');
		$query=$db->getQuery(true);
		$query->select('id')->from('#__jsn_fields')->where('published = 1 AND parent_id = 2 AND edit = 1 AND register = 1');
		$db->setQuery($query);
		if(!$db->loadResult()) $app->enqueueMessage(JText::_('COM_JSN_CHECKLIST_ALERT_DEFAULT_FIELDGROUP'),'error');
		$query=$db->getQuery(true);
		$query->select('id')->from('#__menu')->where('link = "index.php?option=com_users&view=registration" AND published = 1 AND access = 1');
		$db->setQuery($query);
		if($db->loadResult()) $app->enqueueMessage(JText::_('COM_JSN_CHECKLIST_ALERT_REGISTRATION_MENU_ITEM'),'error');
		
		$jsnConfig=JComponentHelper::getParams('com_jsn');
		if($jsnConfig->get('googlemaps_apikey','')=='')
		{
			$query=$db->getQuery(true);
			$query->select('id')->from('#__jsn_fields')->where('published = 1 AND type = "gmap"');
			$db->setQuery($query);
			if($db->loadResult()) $app->enqueueMessage(JText::_('COM_JSN_CHECKLIST_ALERT_GOOGLEMAPS_KEY'),'error');
		}
		if($jsnConfig->get('download_id','')=='')
		{
			$app->enqueueMessage(JText::_('COM_JSN_CHECKLIST_ALERT_DOWNLOADID'),'notice');
		}

	}
}
