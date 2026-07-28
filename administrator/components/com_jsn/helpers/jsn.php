<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnHelperAdmin
{
	/**
	 * Configure the Submenu links.
	 *
	 * @param   string  The extension.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public static function addSubmenu($extension)
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_JSN_SUBMENU_FIELDS'),
			'index.php?option=com_jsn&view=fields'
		);
		/*JHtmlSidebar::addEntry(
			JText::_('COM_JSN_SUBMENU_USERTYPE'),
			'index.php?option=com_jsn&view=usertypes'
		);*/
		JHtmlSidebar::addEntry(
			JText::_('COM_JSN_SUBMENU_USERS'),
			'index.php?option=com_users&view=users'
		);
		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if(JSN_TYPE == 'free'){
			JHtmlSidebar::addEntry(
				JText::_('COM_JSN_IMPORT_USERS').' (not available in free version)',
				'#'
			);
		}
		elseif(JFactory::getUser()->authorise('core.admin.import', 'com_jsn')){
			JHtmlSidebar::addEntry(
				JText::_('COM_JSN_IMPORT_USERS'),
				'index.php?option=com_jsn&view=import'
		);
		}
		
		
		$parts = explode('.', $extension);
		$component = $parts[0];

		if (count($parts) > 1)
		{
			$section = $parts[1];
		}

		// Try to find the component helper.
		$file = JPath::clean(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/jsn.php');

		if (file_exists($file))
		{
			require_once $file;

			$cName = 'JsnHelperAdmin';

			if (class_exists($cName))
			{
				if (is_callable(array($cName, 'addSubmenu')))
				{
					$lang = JFactory::getLanguage();
					// loading language file from the administrator/language directory then
					// loading language file from the administrator/components/*extension*/language directory
						$lang->load($component, JPATH_BASE, null, false, false)
					||	$lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), null, false, false)
					||	$lang->load($component, JPATH_BASE, $lang->getDefault(), false, false)
					||	$lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), $lang->getDefault(), false, false);

				}
			}
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return  JObject
	 *
	 * @since   3.1
	 */
	public static function getActions()
	{
		$user   = JFactory::getUser();
		$result = new JObject;

		$assetName = 'com_jsn';
		$level     = 'component';
		$actions   = JAccess::getActions('com_jsn', $level);

		foreach ($actions as $action)
		{
			$result->set($action->name, $user->authorise($action->name, $assetName));
		}

		return $result;
	}
	
	/*public static function excludeFromProfile($data){
		$db=JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.published = 1')->order($db->escape('a.lft') . ' ASC');
		$db->setQuery( $query );
		$fields = $db->loadObjectList('alias');
		$userData=$data;
		$excludeFromProfile=array();
		foreach($fields as $field)
		{
			// Load Options
			$registry = new JRegistry;
			$registry->loadString($field->params);
			$field->params = $registry->toArray();
			
			$condition_suffix=array('','1','2','3','4');
			foreach($condition_suffix as $suffix)
			{
				if(isset($field->params['condition_operator'.$suffix]) && $field->params['condition_operator'.$suffix]!=0 && count($field->params['condition_hide'.$suffix])>0){
					if($field->params['condition_field'.$suffix]=='_custom') $value=$field->params['condition_custom'.$suffix];
					else 
					{
						$alias=$field->params['condition_field'.$suffix];
						if(isset($userData->$alias)) $value=$userData->$alias;
						else $value='';
						if(is_array($value)) $value=implode(',',$value);
					}
					$alias=$field->alias;
					if(!isset($userData->$alias)) $userData->$alias='';
					
					if(is_array($userData->$alias))
					{
						foreach($userData->$alias as $userValue)
						{
							switch($field->params['condition_operator'.$suffix])
							{
								case 1:
									if($userValue==$value)
									{
										foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
										{
											$excludeFromProfile[]='jform['.$fieldToHide.']';
										}
									}
								break;
								case 2:
									if($userValue>$value)
									{
										foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
										{
											$excludeFromProfile[]='jform['.$fieldToHide.']';
										}
									}
								break;
								case 3:
									if($userValue<$value)
									{
										foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
										{
											$excludeFromProfile[]='jform['.$fieldToHide.']';
										}
									}
								break;
								case 4:
									if(strpos(' '.$userValue,$value)>0)
									{
										foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
										{
											$excludeFromProfile[]='jform['.$fieldToHide.']';
										}
									}
								break;
								case 5:
									if($userValue!=$value)
									{
										foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
										{
											$excludeFromProfile[]='jform['.$fieldToHide.']';
										}
									}
								break;
							}
						}
					}
					else
					{
						switch($field->params['condition_operator'.$suffix])
						{
							case 1:
								if($userData->$alias==$value)
								{
									foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
									{
										$excludeFromProfile[]='jform['.$fieldToHide.']';
									}
								}
							break;
							case 2:
								if($userData->$alias>$value)
								{
									foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
									{
										$excludeFromProfile[]='jform['.$fieldToHide.']';
									}
								}
							break;
							case 3:
								if($userData->$alias<$value)
								{
									foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
									{
										$excludeFromProfile[]='jform['.$fieldToHide.']';
									}
								}
							break;
							case 4:
								if(strpos(' '.$userData->$alias,$value)>0)
								{
									foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
									{
										$excludeFromProfile[]='jform['.$fieldToHide.']';
									}
								}
							break;
							case 5:
								if($userData->$alias!=$value)
								{
									foreach($field->params['condition_hide'.$suffix] as $fieldToHide)
									{
										$excludeFromProfile[]='jform['.$fieldToHide.']';
									}
								}
							break;
						}
					}
				}
			}
		}
		return $excludeFromProfile;
	}*/

}
