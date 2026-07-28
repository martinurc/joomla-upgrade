<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnController extends JControllerLegacy
{
	protected $default_view = 'fields';
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JControllerLegacy  This object to support chaining.
	 *
	 * @since   3.1
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/jsn.php';

		$view   = $this->input->get('view', 'fields');
		$layout = $this->input->get('layout', 'default');
		$id     = $this->input->getInt('id');

		// Check for edit form.
		if ($view == 'field' && $layout == 'edit' && !$this->checkEditId('com_jsn.edit.field', $id))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_jsn&view=fields', false));

			return false;
		}
		if($view == 'opField'){
			foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.php') as $filename) {
				require_once $filename;
			}
			//$user=JsnHelper::getUser();
			if(/*!$user->guest && */JFactory::getApplication()->input->get('type','')!=''){
				$class='Jsn'.ucfirst(JFactory::getApplication()->input->get('type','')).'FieldHelper';
				if(class_exists($class))
				{
					$class::operations();
				}
			}
		}
		else
			parent::display();

		return $this;

	}
	
	public function install()
	{
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->update('#__extensions')->set($db->quoteName('enabled').'=1')
			->where($db->quoteName('element').' IN ('.
			$db->quote('jsn_author').','.
			$db->quote('jsn_auth').','.
			$db->quote('jsn_system').','.
			$db->quote('jsn_content').','.
			$db->quote('jsn_users').','.
			$db->quote('usergroups').','.
			$db->quote('socialconnect').')');
		$db->setQuery($query);
		
		$result = $db->query();
		$this->setMessage(JText::_('COM_JSN_INSTALL'));
		$this->syncuserRun();
		$this->setRedirect(JRoute::_('index.php?option=com_jsn&view=fields', false));
	}
	
	public function update()
	{
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->update('#__extensions')->set($db->quoteName('enabled').'=1')
			->where($db->quoteName('element').' IN ('.
			$db->quote('jsn_auth').','.
			$db->quote('jsn_system').','.
			$db->quote('jsn_content').','.
			$db->quote('jsn_users').','.
			$db->quote('usergroups').','.
			$db->quote('socialconnect').')');
		$db->setQuery($query);
		
		$result = $db->query();
		$this->setMessage(JText::_('COM_JSN_UPDATE'));
		$this->syncuserRun();
		$this->setRedirect(JRoute::_('index.php?option=com_jsn&view=fields', false));
	}
	
	public function syncuser()
	{
		$this->syncuserRun();
		$this->setMessage(JText::_('COM_JSN_SYNCUSER'));
		$this->setRedirect(JRoute::_('index.php?option=com_jsn&view=fields', false));
	}
	
	public function syncuserRun()
	{
		$config = JComponentHelper::getParams('com_jsn');
		$namestyle=$config->get('namestyle','FIRSTNAME_LASTNAME');
		
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->select('a.id,a.name,b.id as bid')->from('#__users as a')->join('left','#__jsn_users as b ON a.id=b.id');
		
		$db->setQuery($query);
		$users=$db->loadObjectList();
		
		
		foreach($users as $user){
			if($user->bid==null || $user->bid==''){
				$query=$db->getQuery(true);
				$fields=array();
				$values=array();
				switch($namestyle){
					case 'FIRSTNAME':
						$fields[]=$db->quoteName('firstname');
						$values[]=$db->quote($user->name);
					break;
					default:
						$fields[]=$db->quoteName('firstname');
						$fields[]=$db->quoteName('lastname');
						$name=explode(' ',$user->name,2);
						$values[]=$db->quote((isset($name[0]) ? $name[0] : ''));
						$values[]=$db->quote((isset($name[1]) ? $name[1] : ''));
					break;
				}
				$query="INSERT INTO #__jsn_users(id,".implode(', ',$fields).") VALUES(".$user->id.", ".implode(', ',$values).")";
				$db->setQuery($query);
				$db->execute();
			}
			
		}
		
		
	}
}
