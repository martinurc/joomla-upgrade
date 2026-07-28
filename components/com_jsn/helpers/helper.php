<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class JsnHelper
{

	public static function getUser($id=null)
	{
		static $currentId=null;
		if($id==null) $id=$currentId;
		
		static $cache_users=array();
		if(is_int($id) && $id>0 && isset($cache_users[$id]))
		{
			return $cache_users[$id];
		}
		$user=new JsnUser($id);
		$cache_users[$user->id]=$user;
		if($id==null) $currentId=$user->id;
		
		return $user;
	}

	public static function getUserProfile($id=null,$alt=false)
	{
		$user=JsnHelper::getUser($id);
		$app=JFactory::getApplication();
		$lang=JFactory::getLanguage();
		$lang->load('com_users');
		
		// Override Current Var
		$currentId=$app->getUserState('com_users.edit.profile.id','');
		$app->setUserState('com_users.edit.profile.id',$user->id);

		if (file_exists(JPATH_SITE . '/components/com_users/views/profile/view.html.php')) {
			require_once(JPATH_SITE . '/components/com_users/views/profile/view.html.php');
		}
		if (file_exists(JPATH_SITE . '/components/com_users/models/profile.php')) {
			require_once(JPATH_SITE . '/components/com_users/models/profile.php');
		}
		if (file_exists(JPATH_SITE . '/components/com_users/helpers/html/users.php')) {
			require_once(JPATH_SITE . '/components/com_users/helpers/html/users.php');
		}

		$document	= JFactory::getDocument();

		$vName   = 'profile';
		$vFormat = $document->getType();
		($alt ? $lName   = $alt : $lName   = JFactory::getApplication()->input->get('layout','default'));

		$model=new UsersModelProfile();
		$view=new UsersViewProfile();
		
		$view->setModel($model, true);
		$view->setLayout($lName);

		$session = JFactory::getSession();
		$session->set('jsn_profile_item_id_'.$user->id, JFactory::getApplication()->input->get('Itemid','') );
		
		$view->excludeFromProfile = JsnHelper::excludeFromProfile($user); // Conditions
		$view->config=JComponentHelper::getParams('com_jsn');
		

		// Push document object into the view.
		$view->document = $document;

		$view->display();

		// Replace Original Current Var
		$app->setUserState('com_users.edit.profile.id',$currentId);
		
		return true;
	}
	
	public static function isOnline($id) {
		static $loggedin=array();
		if(isset($loggedin[$id])) return $loggedin[$id];
		else
		{
			$config = JFactory::getConfig();
			$shared = $config->get('shared_session');
			$db     = JFactory::getDBO();
			if($shared) $query      = 'SELECT COUNT(userid) FROM #__session AS s WHERE s.userid = '.(int)$id;
			else $query      = 'SELECT COUNT(userid) FROM #__session AS s WHERE s.client_id=0 AND s.userid = '.(int)$id;
			$db->setQuery($query);
			$loggedin[$id]   = $db->loadResult();
			return $loggedin[$id];
		}
	}
	
	public static function getFormatName($user){
		if(!($user instanceof JsnUser)){
			$user = JsnHelper::getUser($user->id);
		}
		$config = JComponentHelper::getParams('com_jsn');
		$formatName=$config->get('formatname', 'NAME');
		$formatNameCustom=$config->get('formatnamecustom', '{firstname} {lastname}');
		switch($formatName){
			case 'NAME':
				return $user->name;
			break;
			case 'USERNAME':
				return $user->username;
			break;
			case 'NAMEUSERNAME':
				return $user->name.' ('.$user->username.')';
			break;
			case 'USERNAMENAME':
				return $user->username.' ('.$user->name.')';
			break;
			case 'CUSTOM':
				$return = '';
				$regex		= '/{+(.*?)}/i';
				preg_match_all($regex, $formatNameCustom, $matches, PREG_SET_ORDER);
				foreach ($matches as $match) {
					$formatNameCustom = preg_replace("|$match[0]|", $user->getField($match[1],true), $formatNameCustom, 1);
				}
				$formatNameCustom = str_replace('  ',' ',$formatNameCustom);// Remove multiple white space between fields
				$formatNameCustom = str_replace('  ',' ',$formatNameCustom);// Remove multiple white space between fields
				return trim($formatNameCustom);
			break;
		}
	}
	
	public static function addUserToGroup($user, $groupId)
	{
		$userId=$user->id;
		// Add the user to the group if necessary.
		if (!in_array($groupId, $user->groups))
		{
			// Get the title of the group.
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('title'))
				->from($db->quoteName('#__usergroups'))
				->where($db->quoteName('id') . ' = ' . (int) $groupId);
			$db->setQuery($query);
			$title = $db->loadResult();

			// If the group does not exist, return an exception.
			if (!$title)
			{
				//throw new RuntimeException('Access Usergroup Invalid');
			}
			else{
				$columns = array('user_id', 'group_id');
				$values = array($userId,$groupId);
				$query = $db->getQuery(true);
				$query
				    ->insert($db->quoteName('#__user_usergroup_map'))
				    ->columns($db->quoteName($columns))
				    ->values(implode(',', $values));
				$db->setQuery($query);
				try{
					$db->query();
				}
				catch (Exception $e){}
			}
		}

		if (session_id())
		{
			// Set the group data for any preloaded user objects.
			$temp = JFactory::getUser((int) $userId);
			$temp->groups = $user->groups;

			// Set the group data for the user object in the session.
			$temp = JFactory::getUser();

			if ($temp->id == $userId)
			{
				$temp->groups = $user->groups;
			}
		}

		
	}
	
	public static function removeUserFromGroup($user, $groupId)
	{
		$userId=$user->id;
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$conditions = array(
		    $db->quoteName('user_id') . ' = '. $userId, 
		    $db->quoteName('group_id') . ' = ' . $groupId
		);
		$query->delete($db->quoteName('#__user_usergroup_map'));
		$query->where($conditions);
		$db->setQuery($query);
		$db->query();
		

		// Set the group data for any preloaded user objects.
		$temp = JFactory::getUser((int) $userId);
		$temp->groups = $user->groups;

		// Set the group data for the user object in the session.
		$temp = JFactory::getUser();

		if ($temp->id == $userId)
		{
			$temp->groups = $user->groups;
		}

		
	}
	
	public static function excludeFromProfile($data, $aliasMode=false){
		static $fields=null;
		if(!$fields)
		{
			$db=JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('a.*,b.access as parent_access')->from('#__jsn_fields AS a')->join('LEFT','#__jsn_fields AS b ON a.parent_id = b.id')->where('a.level = 2')->where('a.published = 1')->order($db->escape('a.lft') . ' ASC');
			$db->setQuery( $query );
			$fields = $db->loadObjectList('alias');
		}
		$userData=$data;
		$excludeFromProfile=array();
		$access=(get_class($userData)=='JsnUser' ? $userData->getAuthorisedViewLevels() : JFactory::getUser($userData->id)->getAuthorisedViewLevels());
		foreach($fields as $field)
		{
			// Load Conditions
			if(empty($field->conditions)) $field->conditions = array();
			elseif(is_string($field->conditions)) $field->conditions=json_decode($field->conditions);
			
			foreach($field->conditions as $condition)
			{
				if( !in_array($condition->action,array('fields_show','fields_hide') ) || empty($condition->fields_target) ) continue;
				$twoWays = $condition->two_ways;
				$actionShowFields = ($condition->action == 'fields_show' ? true : false);

				if($condition->to) $value=$condition->custom_value;
				else 
				{
					$alias=$condition->to;
					if(isset($userData->$alias)) $value=$userData->$alias;
					else $value='';
					if(is_array($value)) $value=implode(',',$value);
				}
				$alias=$field->alias;
				if(!isset($userData->$alias)) $userValue='';
				else $userValue=$userData->$alias;
				
				if(is_array($userValue)) $userValue=implode(',',$userValue);

				if($aliasMode)
				{
					$before='';
					$after='';
				}
				else
				{
					$before='jform[';
					$after=']';
				}
				
				switch($condition->operator)
				{
					case 1:
						if($twoWays || $userValue==$value)
							if((!$actionShowFields && $userValue==$value) || ($actionShowFields && $userValue!=$value)   )
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									$excludeFromProfile[]=$before.$fieldToHide.$after;
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap'){
										$excludeFromProfile[]=$before.$fieldToHide.'_lat'.$after;
										$excludeFromProfile[]=$before.$fieldToHide.'_lng'.$after;
									}
									if(!$aliasMode) $excludeFromProfile[]=$before.$fieldToHide.$after.'[]';
								}
							}
							else
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									if(($key = array_search($before.$fieldToHide.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(!$aliasMode && ($key = array_search($before.$fieldToHide.$after.'[]', $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lat'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lng'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
								}
							}
					break;
					case 2:
						if($twoWays || $userValue>$value)
							if((!$actionShowFields && $userValue>$value) || ($actionShowFields && $userValue<=$value)   )
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									$excludeFromProfile[]=$before.$fieldToHide.$after;
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap'){
										$excludeFromProfile[]=$before.$fieldToHide.'_lat'.$after;
										$excludeFromProfile[]=$before.$fieldToHide.'_lng'.$after;
									}
									if(!$aliasMode) $excludeFromProfile[]=$before.$fieldToHide.$after.'[]';
								}
							}
							else
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									if(($key = array_search($before.$fieldToHide.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(!$aliasMode && ($key = array_search($before.$fieldToHide.$after.'[]', $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lat'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lng'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
								}
							}
					break;
					case 3:
						if($twoWays || $userValue<$value)
							if((!$actionShowFields && $userValue<$value) || ($actionShowFields && $userValue>=$value)   )
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									$excludeFromProfile[]=$before.$fieldToHide.$after;
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap'){
										$excludeFromProfile[]=$before.$fieldToHide.'_lat'.$after;
										$excludeFromProfile[]=$before.$fieldToHide.'_lng'.$after;
									}
									if(!$aliasMode) $excludeFromProfile[]=$before.$fieldToHide.$after.'[]';
								}
							}
							else
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									if(($key = array_search($before.$fieldToHide.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(!$aliasMode && ($key = array_search($before.$fieldToHide.$after.'[]', $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lat'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lng'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
								}
							}
					break;
					case 4:
						if($twoWays || strpos(' '.$userValue,$value)>0)
							if((!$actionShowFields && strpos(' '.$userValue,$value)>0) || ($actionShowFields && !(strpos(' '.$userValue,$value)>0))   )
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									$excludeFromProfile[]=$before.$fieldToHide.$after;
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap'){
										$excludeFromProfile[]=$before.$fieldToHide.'_lat'.$after;
										$excludeFromProfile[]=$before.$fieldToHide.'_lng'.$after;
									}
									if(!$aliasMode) $excludeFromProfile[]=$before.$fieldToHide.$after.'[]';
								}
							}
							else
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									if(($key = array_search($before.$fieldToHide.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(!$aliasMode && ($key = array_search($before.$fieldToHide.$after.'[]', $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lat'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lng'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
								}
							}
					break;
					case 5:
						if($twoWays || $userValue!=$value)
							if((!$actionShowFields && $userValue!=$value) || ($actionShowFields && $userValue==$value)   )
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									$excludeFromProfile[]=$before.$fieldToHide.$after;
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap'){
										$excludeFromProfile[]=$before.$fieldToHide.'_lat'.$after;
										$excludeFromProfile[]=$before.$fieldToHide.'_lng'.$after;
									}
									if(!$aliasMode) $excludeFromProfile[]=$before.$fieldToHide.$after.'[]';
								}
							}
							else
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									if(($key = array_search($before.$fieldToHide.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(!$aliasMode && ($key = array_search($before.$fieldToHide.$after.'[]', $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lat'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lng'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
								}
							}
					break;
					case 6:
						if($twoWays || !(strpos(' '.$userValue,$value)>0))
							if((!$actionShowFields && !(strpos(' '.$userValue,$value)>0)) || ($actionShowFields && strpos(' '.$userValue,$value)>0)   )
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									$excludeFromProfile[]=$before.$fieldToHide.$after;
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap'){
										$excludeFromProfile[]=$before.$fieldToHide.'_lat'.$after;
										$excludeFromProfile[]=$before.$fieldToHide.'_lng'.$after;
									}
									if(!$aliasMode) $excludeFromProfile[]=$before.$fieldToHide.$after.'[]';
								}
							}
							else
							{
								foreach($condition->fields_target as $fieldToHide)
								{
									if(($key = array_search($before.$fieldToHide.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(!$aliasMode && ($key = array_search($before.$fieldToHide.$after.'[]', $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lat'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
									if(isset($fields[$fieldToHide]) && $fields[$fieldToHide]->type=='gmap' && ($key = array_search($before.$fieldToHide.'_lng'.$after, $excludeFromProfile)) !== false) {
									    unset($excludeFromProfile[$key]);
									}
								}
							}
					break;
				}
			}
			
		}
		return $excludeFromProfile;
	}

	public static function getFieldOptions($alias) {
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')->from('#__jsn_fields')->where('alias='.$db->quote($alias));
		$db->setQuery($query);
		$field   = $db->loadAssoc();
		$registry = new \Joomla\Registry\Registry;
		$registry->loadString($field['params']);
		$field['params'] = $registry->toArray();
		$field['options'] = array();
		foreach ($field['params'] as $key => $value) {
			if(strpos($key,'_options') && !empty($value)){
				$options = [];
				$optTxt=explode("\n",$value);
				foreach($optTxt as $opt)
				{
					$opt=trim($opt);
					if($opt!=''){
						$opt=explode('|',$opt);
						if(count($opt)==1) $options[trim($opt[0])]=trim($opt[0]);
						if(count($opt)==2) $options[trim($opt[0])]=trim($opt[1]);
					}
				}
				$field['options'] = $options;
			}
		}
		return $field['options'];
	}

	public static function defaultAvatar() {
		static $defaultAvatar = '';
		if(empty($defaultAvatar)) {
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('params')->from('#__jsn_fields')->where('alias=\'avatar\'');
			$db->setQuery($query);
			$params   = $db->loadResult();
			$registry = new \Joomla\Registry\Registry;
			$registry->loadString($params);
			$params = $registry->toArray();
			if (!empty($params['image_defaultvalue']))
			{
				$defaultAvatar = $params['image_defaultvalue'];
			}
			else
			{
				$defaultAvatar = 'components/com_jsn/assets/img/default.jpg';
			}
		}
		return $defaultAvatar;
	}
	
	public static function xmlentities($s) {
		if(substr_count($s,'<p>')==1 && substr_count($s,' ')==0) $s=strip_tags($s);
	    static $patterns = null;
	    static $reps = null;
	    static $tbl = null;
	    if ($tbl === null) {
	        $tbl = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
	        foreach ($tbl as $k => $v) {
	            $patterns[] = "/$v/";
	            $reps[] = '&#' . ord($k) . ';';
	        }
	   }
	  return preg_replace($patterns, $reps, htmlspecialchars($s, ENT_QUOTES, 'UTF-8'));
	}
	
}

class JsnUser extends JUser
{
	private $excludeFromProfile;
	
	function __construct( $id = null ){
		$exclude = array();
		$user=JFactory::getUser($id);
		foreach($user as $key => $value)
		{
			if(!isset($this->$key) && !in_array($key, $exclude)) $this->$key=$value;
		}


		//$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('user');
		// Código corregido para Joomla 5
		$app = \Joomla\CMS\Factory::getApplication();
		// 1. Asegurar que $userData no sea null (convertir a objeto si está vacío)
        if (empty($userData) || !is_object($userData)) {
          $userData = (object) [];
        }

// 2. Disparar el evento con la estructura de parámetros requerida por Joomla 5
        $results = (array) $app->triggerEvent('onContentPrepareData', ['com_users.profile', $userData]);
		$this->excludeFromProfile=JsnHelper::excludeFromProfile($this,true);
		
	}
	
public function getLink($params = array()) 
{
    $userId = (int) ($this->id ?? 0);

    if ($userId === 0) {
        return '#';
    }

    $itemId = !empty($params['Itemid']) ? (int) $params['Itemid'] : '';
    $back   = !empty($params['back']) ? 1 : 0;

    $url = 'index.php?option=com_jsn&view=profile&id=' . $userId;

    if ($itemId) {
        $url .= '&Itemid=' . $itemId;
    }

    if ($back) {
        $url .= '&back=1';
    }

    return \Joomla\CMS\Router\Route::_($url);
}
	
	public function getValue($name) {
		if(($name == 'avatar' || $name == 'avatar_mini') && empty($this->$name)) return JsnHelper::defaultAvatar();
		if(isset($this->$name)) return $this->$name;
		else return null;
	}
	
	public function setValue($name,$value) {
		$this->$name=$value;
		return true;
	}
	
	public function getField($name,$privacy=false) {

		if(!is_string($name) ) {
			/* $name contain JFormField object */
			if (JHtml::isRegistered('users.'.$name->id)) return JHtml::_('users.'.$name->id, $name->value);
			elseif (JHtml::isRegistered('users.'.$name->fieldname)) return JHtml::_('users.'.$name->fieldname, $name->value);
			elseif (JHtml::isRegistered('users.'.$name->type)) return JHtml::_('users.'.$name->type, $name->value);
			elseif (JHtml::isRegistered('jsn.'.$name->type)) return JHtml::_('jsn.'.$name->type, $name,$this);
			elseif (is_string($name->value) && trim($name->value)=="0") return $name->value;
			else return JHtml::_('users.value', $name->value);
		}

		if($name=='email1') $name='email';
		if(in_array($name,$this->excludeFromProfile)) return;
		if($name=='socialconnect' && class_exists('PlgJsnSocialconnect')) { $config = JComponentHelper::getParams('com_jsn'); return PlgJsnSocialconnect::getSocialLink($this,$config);}
		if($name=='name' || $name=='formatname') return $this->getFormatName();
		if($name=='status')
		{
			if($this->isOnline())
				return '<div data-toggle="tooltip" title="'.JText::_('COM_JSN_ONLINE').'" class="status label label-success">'.JText::_('COM_JSN_ONLINE').'</div>';
			else
				return '<div data-toggle="tooltip" title="'.JText::_('COM_JSN_OFFLINE').'" class="status label label-important label-danger">'.JText::_('COM_JSN_OFFLINE').'</div>';
		}
		if($name != 'avatar' && $name != 'avatar_mini' && (!isset($this->$name) || (empty($this->$name) && $this->$name!="0"))) return '';
		if(strpos($name, '_mini')==0)
			$fieldname=$name;
		else 
			$fieldname=substr($name,0,-5);
		
		static $fields_cache=null;
		if(empty($fields_cache))
		{
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select('a.*,b.access as parent_access')->from('#__jsn_fields AS a')->join('LEFT','#__jsn_fields AS b ON a.parent_id = b.id')->where('a.level = 2')->where('a.published = 1');/*->where('a.alias = '.$db->quote($fieldname));*/
			$db->setQuery( $query );
			$fields_cache = $db->loadAssocList('alias');
		}
		if(isset($fields_cache[$fieldname]))
			$field=(object) $fields_cache[$fieldname];
		
		if($field)
		{
			if (file_exists(JPATH_SITE . '/components/com_users/helpers/html/users.php')) {
				require_once(JPATH_SITE . '/components/com_users/helpers/html/users.php');
			}
			if(file_exists(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/'.$field->type.'.php')) require_once(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/'.$field->type.'.php');
			
			// Load Options
			if(is_string($field->params))
			{
				$registry = new JRegistry;
				$registry->loadString($field->params);
				$field->params = $registry;
			}
			
			// Access to view this field
			$userVisitor=JFactory::getUser();
			$accessVisitor=$userVisitor->getAuthorisedViewLevels();
			if(!($userVisitor->id==$this->id || in_array($field->accessview, $accessVisitor) || $userVisitor->authorise('core.admin'))) return '';
			
			// Check if Field is available for User
			$availableUser=$this->getAuthorisedViewLevels();
			if(!in_array($field->access, $availableUser) || !in_array($field->parent_access, $availableUser)) return '';
			
			// Check Privacy
			if($privacy && $field->params->get('privacy',0)){
				$privacy_name='privacy_'.$fieldname;
				if($userVisitor->id==$this->id) $privacy_auth=99;
				elseif($userVisitor->id) $privacy_auth=1;
				else $privacy_auth=0;

				global $JSNSOCIAL;
				if($privacy_auth==1 && $JSNSOCIAL)
				{
					$db = JFactory::getDBO();
					$db->setQuery("SELECT friend_id FROM #__jsnsocial_friends WHERE user_id = ".$this->id." AND friend_id = ".$userVisitor->id);
					$isFriends = $db->loadResult();
					if(!$isFriends) $privacy_auth=0;
				}
				
				// Privacy Skip for Admin
				if($userVisitor->authorise('core.edit', 'com_users')) $privacy_auth=99;
				
				$privacy_value=(isset($this->$privacy_name) && strlen($this->$privacy_name) ? $this->$privacy_name : $field->params->get('privacy_default',0));
				if($privacy_auth<$privacy_value) 
				{
					if($fieldname == 'avatar') $this->avatar = $this->avatar_mini = $this->avatar_clean = '';
					else return '';
				}
			}
			if($name=='email' || $field->type == 'email') {
				//JPluginHelper::importPlugin('content');
				//return JHtml::_('content.prepare', '<a href="mailto:'.$this->email.'">'.$this->email.'</a>', '', 'jsn_content.content');
				return '<a href="mailto:'.$this->$name.'">'.$this->$name.'</a>';
			}
			
			// Get Xml
			$class='Jsn'.ucfirst($field->type).'FieldHelper';
			if(class_exists($class)) 
			{
				$fieldXml=$class::getXml($field);
				$form=new JForm(null);
				$form->load('<form>'.$fieldXml.'</form>');
				if(!isset($this->$name)) $this->$name = '';
				$form->setValue($fieldname,null,$this->$name);
				$method=$field->type;
				if (JHtml::isRegistered('users.'.$field->id)) return JHtml::_('users.'.$field->id, $this->$name);
				elseif (JHtml::isRegistered('users.'.$field->alias)) return JHtml::_('users.'.$field->alias, $this->$name);
				elseif (JHtml::isRegistered('users.'.$field->type)) return JHtml::_('users.'.$field->type, $this->$name);
				elseif (JHtml::isRegistered('jsn.'.$field->type)) return JHtml::_('jsn.'.$field->type, $form->getField($fieldname),$this);
				elseif (JHtml::isRegistered('users.value')) return JHtml::_('users.value', $this->$name);
				else return $this->$name;
			}
		}
		return '';
	}
	
	public function isOnline() {
		return JsnHelper::isOnline($this->id);
	}
	
	public function getFormatName() {
		return JsnHelper::getFormatName($this);
	}
	
	public function getUserError() {
		return implode("\n",$this->_errors);
	}

}