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
	/**
	 * Method to display a view.
	 *
	 * @param   boolean			If true, the view output will be cached
	 * @param   array  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController		This object to support chaining.
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$config = JComponentHelper::getParams('com_jsn');
		
		$vName=JFactory::getApplication()->input->get('view','profile');
		
		if(JFactory::getApplication()->input->get('format','')!='raw') echo('<div id="easyprofile" class="view_'.$vName.'">');
		
		switch($vName){
			case 'profile':
				$user=JFactory::getUser();
				$profileAcl=$config->get('profileACL',2);
				
				if(JFactory::getApplication()->input->get('id')==null && $user->guest)
					JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_users&view=login&task=&return='.base64_encode( JURI::getInstance()->toString()) ,false));
				else
				{
					if(JFactory::getUser(JFactory::getApplication()->input->get('id'))->block)
					{
						$lang = JFactory::getLanguage();
						$lang->load('com_users');
						JFactory::getApplication()->enqueueMessage(JText::_('COM_USERS_USER_BLOCKED'));
					}
					else
					switch($profileAcl){
						case 0: // Private
							if(JFactory::getApplication()->input->get('id')==$user->id || JFactory::getApplication()->input->get('id')==null || $user->authorise('core.edit', 'com_users'))
							{
								JsnHelper::getUserProfile(JFactory::getApplication()->input->get('id'));
							}
							else
							{
								JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NOTVIEWPROFILE'));
							}
						break;
						case 1: // Only Registered
							if(!$user->guest)
							{
								JsnHelper::getUserProfile(JFactory::getApplication()->input->get('id'));
							}
							else
							{
								JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NOTVIEWPROFILE').' - '.JText::_('COM_JSN_LOGINPLEASE'));
								JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_users&view=login&task=&return='.base64_encode( JURI::getInstance()->toString() ),false));
							}
						break;
						case 2: // Public
							JsnHelper::getUserProfile(JFactory::getApplication()->input->get('id'));
						break;
						case 3: // Custom
							$access=$user->getAuthorisedViewLevels();
							$profileAclCustom=$config->get('profileACLcustom','');
							if(JFactory::getApplication()->input->get('id')==$user->id || JFactory::getApplication()->input->get('id')==null || in_array($profileAclCustom,$access) || $user->authorise('core.edit', 'com_users'))
							{
								JsnHelper::getUserProfile(JFactory::getApplication()->input->get('id'));
							}
							else
							{
								JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_NOTVIEWPROFILE'));
							}
						break;
					}
				}
			break;
			case 'search':
			case 'list':
				$app = JFactory::getApplication();
				$menu = $app->getMenu();
				$item = $menu->getActive();
				if(isset($item->link) && (strpos($item->link,'option=com_jsn&view=list')>0 || strpos($item->link,'option=com_jsn&view=search'))>0)
				{
					if(JFactory::getApplication()->input->get('search',0) && !\Joomla\CMS\Session\Session::checkToken('get')){die('Not Valid Token');}
					$document	= JFactory::getDocument();
					$vName=($vName=='search' ? 'list' : $vName);
					$lName   = $this->input->getCmd('layout', (isset($item->query['layout']) ? $item->query['layout'] : 'default'));
					$vFormat = $document->getType();
					$model = $this->getModel($vName);
				
					$view=$this->getView($vName, $vFormat);
					$view->setModel($model, true);
					$view->setLayout($lName);

					// Push document object into the view.
					$view->document = $document;
					$view->display();
				}
				else echo('<h1>'.JText::_('JLIB_RULES_NOT_ALLOWED').'</h1>');
			break;
			case 'getField':
				$user=JsnHelper::getUser();
				if(!$user->guest && JFactory::getApplication()->input->get('alias','')!=''){
					echo $user->getField(JFactory::getApplication()->input->get('alias',''));
				}
			break;
			case 'setField':
				$user=JsnHelper::getUser();
				if(!$user->guest && JFactory::getApplication()->input->get('alias','')!=''){
					$user->setValue(JFactory::getApplication()->input->get('alias',''),JFactory::getApplication()->input->get('value',''));
					$user->save();
				}
			break;
			case 'opField':
				foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.php') as $filename) {
					require_once $filename;
				}
				if(JFactory::getApplication()->input->get('type','')!=''){
					$class='Jsn'.ucfirst(JFactory::getApplication()->input->get('type','')).'FieldHelper';
					if(class_exists($class))
					{
						$class::operations();
					}
				}
			break;
			default:
				$dispatcher	= JEventDispatcher::getInstance();
				echo(implode(' ',$dispatcher->trigger('renderPlugin',array())));
			break;

		}
		
		if(JFactory::getApplication()->input->get('format','')!='raw') echo('</div>');
		
	}
}
