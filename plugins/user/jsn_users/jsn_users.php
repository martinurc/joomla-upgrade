<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

class PlgUserJsn_Users extends JPlugin
{
	private $config=null;

	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		// Include Field Class & Field Model
		foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.php') as $filename) {
			require_once $filename;
		}
		JFormHelper::addFieldPath(JPATH_ADMINISTRATOR.'/components/com_jsn/models/fields');
		JFormHelper::addRulePath(JPATH_ADMINISTRATOR.'/components/com_jsn/models/rule');
		
		// Load Config
		$this->config = JComponentHelper::getParams('com_jsn');

		// Activate email when user change it
		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if($this->config->get('activatenewmail',0) && JSN_TYPE != 'free')
		{
			$lang = JFactory::getLanguage();
			$lang->load('com_jsn');
			$this->activateNewEmail();
		}
	}

	public function onContentPrepareData($context, $data)
	{
		// Check we are manipulating a valid form.
		if (!in_array($context, array('com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile')))
		{
			return true;
		}

		if (is_object($data))
		{
			$userId = isset($data->id) ? $data->id : 0;

			if (!isset($data->firstname) and $userId > 0)
			{	
				$db = JFactory::getDbo();
				
				// Load the profile data from the database.
				static $user_cache=null;
				if(!isset($user_cache[$userId]))
				{
					$query = $db->getQuery(true);
					$query->select('a.*')->from('#__jsn_users AS a')->where('a.id = '.$userId);
					$db->setQuery( $query );
					$user_cache[$userId] = $db->loadObject();
				}
				$user=$user_cache[$userId];
				
				if(empty($user)) $user=new stdClass();
				
				if(!isset($user->firstname)) $user->firstname=$data->name;
				
				static $fields_cache=null;
				if($fields_cache==null)
				{
					$query = $db->getQuery(true);
					$query->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.published = 1')/*->order($db->escape('a.lft') . ' ASC')*/;
					$db->setQuery( $query );
					$fields_cache = $db->loadObjectList();
				}
				$fields=$fields_cache;
					
				// Privacy
				if(isset($user->privacy))
				{
					if(is_string($user->privacy)) $user->privacy=(array) json_decode($user->privacy);
					if((is_array($user->privacy) || is_object($user->privacy)) && count($user->privacy)) foreach($user->privacy as $key => $value)
					{
						$data->$key=$value;
					}
				}

				foreach($fields as $field)
				{
					// Load Field Registry
					$registry = new JRegistry;
					$registry->loadString($field->params);
					$field->params = $registry;
					
					// Privacy
					$privacy_name='privacy_'.$field->alias;
					if($field->params->get('privacy',0))
					{
						if(!isset($data->$privacy_name)) $data->$privacy_name=$field->params->get('privacy_default',0);
					}
					else
					{
						if(isset($data->$privacy_name)) unset($data->$privacy_name);
					}
					
					// Field Class
					$class='Jsn'.ucfirst($field->type).'FieldHelper';
					if(class_exists($class)) $class::loadData($field, $user, $data);

					// Register Function to Profile Display
					if (is_callable(array($class, $field->type)) && !JHtml::isRegistered('jsn.'.$field->type))
					{
						JHtml::register('jsn.'.$field->type, array($class, $field->type));
					}

				}
				if (is_callable(array('JsnUsermailFieldHelper', 'usermail')) && !JHtml::isRegistered('jsn.email'))
				{
					JHtml::register('jsn.email', array('JsnUsermailFieldHelper', 'usermail'));
				}
				
				// Social Connect
				if(isset($user->facebook_id)) $data->facebook_id=$user->facebook_id;
				if(isset($user->twitter_id)) $data->twitter_id=$user->twitter_id;
				if(isset($user->google_id)) $data->google_id=$user->google_id;
				if(isset($user->linkedin_id)) $data->linkedin_id=$user->linkedin_id;
				if(isset($user->instagram_id)) $data->instagram_id=$user->instagram_id;
			}
		}
		return true;
	}
	
	public function onUserBeforeSave($user, $isnew, $data)
	{
		// Activate email when user change it
		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if($this->config->get('activatenewmail',0) && JSN_TYPE != 'free' && JFactory::getApplication()->input->get('option')!='com_privacy')
		{
			$this->changeEmailBeforeSave($user, $isnew, $data);
		}
		return true;
	}

	public function onUserAfterSave($data, $isNew, $result, $error)
	{
		$userId = \Joomla\Utilities\ArrayHelper::getValue($data, 'id', 0, 'int');

		// Activate email when user change it
		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if($this->config->get('activatenewmail',0) && JSN_TYPE != 'free')
		{
			$this->changeEmailAfterSave($userId, $data, $isNew, $result, $error);
		}
		
		if($this->config->get('logintype', 'USERNAME')=='MAIL' && (JFactory::getApplication()->input->get('task')=='registration.activate' || JFactory::getApplication()->input->get('task')=='activate'))
		{
			$user = JFactory::getUser($userId);
			if(isset($user->tmp_username))
			{
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->update("#__users");
				$query->set($db->quoteName('username').' = '.$db->quote($user->tmp_username));
				$query->where('id = '. $userId);
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		if ($userId && $result && isset($data['firstname']))
		{
			try
			{
				$db = JFactory::getDbo();

				// Compile new Format Name
				$logintype=$this->config->get('logintype', 'USERNAME');
				if( $isNew && $logintype=='MAIL' )
				{
					if(JFactory::getApplication()->isClient('site')) $data['username']=$data['email1'];
					else $data['username']=$data['email'];
				}
				// Format Name
				$namestyle=$this->config->get('namestyle', 'FIRSTNAME_LASTNAME');
				switch($namestyle){
					case 'FIRSTNAME_LASTNAME':
						$name=trim(trim($data['firstname']).' '.trim($data['lastname']));
					break;
					case 'FIRSTNAME_SECONDNAME_LASTNAME':
						$name=trim(trim(trim($data['firstname']).' '.trim($data['secondname'])).' '.trim($data['lastname']));
					break;
					case 'FIRSTNAME':
						$name=trim($data['firstname']);
					break;
				}
				
				// Write new Format Name
				$query = $db->getQuery(true);
				$query->update("#__users");
				$query->set($db->quoteName('name').' = '.$db->quote($name));
				if( $isNew && $logintype=='MAIL' ){
					$username=\Joomla\CMS\Filter\OutputFilter::stringURLSafe( $name );
					if( ! trim($username,'-_ ') ){
						$username='user_'.date('YmdHis');
					}
					$queryCheckUsername = $db->getQuery(true);
					$queryCheckUsername->select('a.email')->from('#__users AS a')->where('a.username = '.$db->quote($username))->where('id <> ' . (int) $userId);
					$db->setQuery( $queryCheckUsername );
					if($userCheckUsername=$db->loadResult()){
						$username=$username.'_'.rand(0,2000);
					}
					$query->set($db->quoteName('username').' = '.$db->quote($username));
				}
				$query->where('id = '. $userId);
				$db->setQuery($query);
				$db->execute();

				// Check if user exist
				$query = $db->getQuery(true);
				$query->select('a.id')->from('#__jsn_users AS a')->where('a.id = '.$userId);
				$db->setQuery( $query );
				$isUpdate = $db->loadObjectList();
				
				// Load Fields
				require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
				$query = $db->getQuery(true);
				$query->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.published = 1')->order($db->escape('a.lft') . ' ASC');
				
				$db->setQuery( $query );
				$fields = $db->loadObjectList();
				$storeData=array();
				$jsnUser=JsnHelper::getUser($userId);
				$no_edit_fields=array();

				$session = JFactory::getSession();
				$original_id=$session->get('jsn_original_id',0);
				if($this->config->get('admin_frontend', 0) && ($original_id!=$jsnUser->id || $jsnUser->authorise('core.edit', 'com_users')))
					$allow_no_edit_fields=true;
				else
					$allow_no_edit_fields=false;

				foreach($fields as $field)
				{
					// Load Field Registry
					$registry = new JRegistry;
					$registry->loadString($field->params);
					$field->params = $registry;
					
					$class='Jsn'.ucfirst($field->type).'FieldHelper';
					if(class_exists($class)) $class::storeData($field, $data, $storeData);
					
					// Not edit fields filled only for conditions
					if(JFactory::getApplication()->isClient('site') && JFactory::getApplication()->input->get('option')=='com_users' && (JFactory::getApplication()->input->get('task')=='profile.save' || JFactory::getApplication()->input->get('task')=='save') && $field->edit==0 && $allow_no_edit_fields==false)
					{
						$alias=$field->alias;
						$no_edit_fields[]=$alias;
						$storeData[$alias]=$jsnUser->$alias;
						if(is_array($storeData[$alias])) $storeData[$alias]=json_encode($storeData[$alias]);
					}
				}
				
				// Check if columns exist
				$query = $db->getQuery(true);
				$query = "SHOW COLUMNS FROM #__jsn_users";
				$db->setQuery( $query );
				$result=$db->loadObjectList();
				$columns=array();
				foreach($result as $column)
				{
					$columns[]=$column->Field;
				}
				foreach($storeData as $key=>$value)
				{
					if(!in_array($key,$columns))
					{
						unset($storeData[$key]);
						if(JFactory::getApplication()->isClient('administrator'))
						{
							JFactory::getApplication()->enqueueMessage('Error on save "'.$key.'" field, try to recreate a field!','warning');
						}
					}
				}
				
				// Privacy Field
				$data['privacy']=array();
				foreach($data as $key => $value)
				{
					if(substr($key,0,8)=='privacy_') $data['privacy'][$key]=$value;
				}
				$storeData['privacy']=json_encode($data['privacy']);
				
				// Social Connect
				if(isset($data['facebook_id'])) $storeData['facebook_id']=$data['facebook_id'];
				if(isset($data['twitter_id'])) $storeData['twitter_id']=$data['twitter_id'];
				if(isset($data['google_id'])) $storeData['google_id']=$data['google_id'];
				if(isset($data['linkedin_id'])) $storeData['linkedin_id']=$data['linkedin_id'];
				if(isset($data['instagram_id'])) $storeData['instagram_id']=$data['instagram_id'];
				
				// Reset field Hidden By Condition
				$conditions_check=(object)$storeData;
				foreach($conditions_check as $key=>$val)
				{
					if(!is_array($conditions_check->$key) && !is_null(json_decode($conditions_check->$key))) $conditions_check->$key=json_decode($conditions_check->$key);
				}
				$conditions_check->id=$userId;
				$removedByConditions=JsnHelper::excludeFromProfile($conditions_check,true);
				foreach($removedByConditions as $field){
					if(isset($storeData[$field])) $storeData[$field]='';
				}
				
				// Unset fields not in edit form

				foreach($no_edit_fields as $field){
					if(isset($storeData[$field])) unset($storeData[$field]);
				}
				
				// Trigger Profile and Field Update
				JPluginHelper::importPlugin('jsn');
				
				$changed=array();
				$storeData['email']=$data[ 'email' ];
				$storeData['username']=$data[ 'username' ];
				$storeData['password']=$data[ 'password' ];
				$storeData['name']=$data[ 'name' ];
				
				$dispatcher	= JEventDispatcher::getInstance();
				foreach($storeData as $key=>$value)
				{
					if($key!='privacy'){
						$key_clean=$key.'_clean';
						if(isset($jsnUser->$key_clean)) $jsnUser->$key=$jsnUser->$key_clean;
						if(isset($jsnUser->$key) && is_array($jsnUser->$key)) $value=json_decode($value);
						if(!isset($jsnUser->$key) || $jsnUser->$key==null) $jsnUser->$key='';
						if((isset($jsnUser->$key) && $jsnUser->$key!=$value) || !isset($jsnUser->$key)){
							$changed[]=$key;
							$dispatcher->trigger('triggerField'.ucfirst(str_replace('-','_',$key)).'Update',array($jsnUser,&$storeData,$changed,$isNew));
						}
					}
				}
				if(count($changed))
				{
					$dispatcher->trigger('triggerProfileUpdate',array($jsnUser,&$storeData,$changed,$isNew));
				}
				unset($storeData['email']);
				unset($storeData['username']);
				unset($storeData['password']);
				unset($storeData['name']);
				
				
				// Write Jsn User
				if(count($isUpdate))
				{
					// Update User
					$query = $db->getQuery(true);
					$query->update("#__jsn_users");
					foreach($storeData as $key => $value)
					{
						$query->set($db->quoteName($key).' = '.$db->quote($value));
					}
					$query->where('id = '. $userId);
					$db->setQuery($query);
					$db->execute();
				}
				else{
					// New User
					$fields=array();
					$values=array();
					foreach($storeData as $key => $value)
					{
						$fields[]=$db->quoteName($key);
						$values[]=$db->quote($value);
					}
					
					$query="INSERT INTO #__jsn_users(id,".implode(', ',$fields).") VALUES(".$userId.", ".implode(', ',$values).")";
					$db->setQuery($query);
					$db->execute();
					
				}
				
				//Update Session
				$session = JFactory::getSession();
				if($userId==$session->get('user')->id){
					$session->set('user', new JUser($userId));
				}
				
				//Redirect on Profile Page
				if(!$isNew) {
					$Itemid = $session->get('jsn_profile_item_id_'.$userId,false);
					if(!$Itemid){
						$profileMenu=JFactory::getApplication()->getMenu()->getItems('link','index.php?option=com_jsn&view=profile',true);
						if(isset($profileMenu->id)) $Itemid=$profileMenu->id;
						else $Itemid='';
					}
					if(JFactory::getSession()->get('redirectAfterLogin',null))
						JFactory::getApplication()->setUserState('com_users.edit.profile.redirect',JFactory::getSession()->get('redirectAfterLogin',null));
					else
						JFactory::getApplication()->setUserState('com_users.edit.profile.redirect', 'index.php?option=com_jsn&Itemid='.$Itemid.'&view=profile&id='. (int) $userId);
				}
			}
			catch (RuntimeException $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}

		return true;
	}

	public function onUserBeforeDelete($user)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.published = 1');
		$db->setQuery( $query );
		$fields = $db->loadObjectList();

		foreach($fields as $field)
		{
			// Load Field Registry
			$registry = new JRegistry;
			$registry->loadString($field->params);
			$field->params = $registry;
			
			// Field Class
			$class='Jsn'.ucfirst($field->type).'FieldHelper';
			if(class_exists($class) && method_exists($class, 'deleteUser')) $class::deleteUser($field, $user);
		}
	}

	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		$userId = \Joomla\Utilities\ArrayHelper::getValue($user, 'id', 0, 'int');

		if ($userId)
		{
			try
			{
				$db = JFactory::getDbo();
				$db->setQuery(
					'DELETE FROM #__jsn_users WHERE id = ' . $userId
				);

				$db->execute();
			}
			catch (Exception $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}
		return true;
	}
	
	public function onUserLogin($user, $options = array())
	{
		if(JFactory::getApplication()->isClient('administrator')) return true;
		
		// Retrieve User
		$instance = JUser::getInstance();
		$id = (int) JUserHelper::getUserId($user['username']);
		if ($id)
		{
			$instance->load($id);
		}
		else 
		{
			return true;
		}
		
		// Redirect on First Login
		if($this->config->get('firstLoginUrl') && $instance->lastvisitDate=='0000-00-00 00:00:00')
		{
			$router = JFactory::getApplication()->getRouter();
			if ($router->getMode() == JROUTER_MODE_SEF)
			{
				JFactory::getApplication()->setUserState('users.login.form.return', 'index.php?Itemid='.$this->config->get('firstLoginUrl') );
			}
			else {
				JFactory::getApplication()->setUserState('users.login.form.return', JFactory::getApplication()->getMenu()->getItem($this->config->get('firstLoginUrl'))->link.'&Itemid='.$this->config->get('firstLoginUrl') );
			}
			JFactory::getSession()->set('redirectAfterLogin',JFactory::getApplication()->getUserState('users.login.form.return'));
			return true;
		}
		// Redirect on Login
		if($this->config->get('loginUrl'))
		{
			$router = JFactory::getApplication()->getRouter();
			if ($router->getMode() == JROUTER_MODE_SEF)
			{
				JFactory::getApplication()->setUserState('users.login.form.return', 'index.php?Itemid='.$this->config->get('loginUrl') );
			}
			else {
				JFactory::getApplication()->setUserState('users.login.form.return', JFactory::getApplication()->getMenu()->getItem($this->config->get('loginUrl'))->link.'&Itemid='.$this->config->get('loginUrl') );
			}
			JFactory::getSession()->set('redirectAfterLogin',JFactory::getApplication()->getUserState('users.login.form.return'));
			return true;
		}
		return true;
	}

	private function activateNewEmail()
	{
		if (JFactory::getApplication()->input->getInt('emailactivation')) {
            $userId = JFactory::getApplication()->input->getInt('u');
            $app    = JFactory::getApplication();
            $user   = JFactory::getUser($userId);

            if ($user->guest) {
                // Undelegate wrong users and guests
                $app->redirect(JRoute::_(''));
            } else {

                // need to load fresh instance
                $table = JTable::getInstance('user', 'JTable');
                $table->load($userId);

                if (empty($table->id)) {
                    throw new Exception('User cannot be found');
                }

                $params = new JRegistry($table->params);

                // get token from user parameters
                $token = $params->get('emailactivation_token');
                $token = md5($token);

                // check that the token is in a valid format.
                if (!empty($token) && strlen($token) === 32 && JFactory::getApplication()->input->getInt($token, 0, 'get') === 1) {

                        // get user's new email from parameters
                        $email = $params->get('emailactivation');
                        $email_old = $table->email;

                        if (!empty($email)) {

                            // remove token and old email from params
                            $params->set('emailactivation', null);
                            $params->set('emailactivation_token', null);

                            // store user table                    
                            $table->params = $params->toString();
                            $table->email = $email;

                            if ($this->params->get('block', null)) {
                                // block user account
                                $table->block = 0;
                            }
    
                            // save user data
                            if(!$table->store()) {
                                throw new RuntimeException($table->getError());
                            } else {
                                $user->email = $email;

                                $app->enqueueMessage(JText::_('COM_JSN_EMAILACTIVATION_ACTIVATED'));

                                // Reirect afterwords
                                $app->redirect(JRoute::_($this->params->get('redirect_url', '')), false);
                            }
                        }

                } else {
                    jexit('Invalid activation token');
                }
            }
        }
	}

	private function changeEmailBeforeSave($user, $isnew, $data)
	{
		// check whether we are going to update user's email
    	if ($isnew || !isset($user['email']) || $user['email'] == $data['email']) {
        	return true;
    	}

    	// Check if user is admin
    	if(isset($data->id))  $id=JFactory::getApplication()->getUserState('com_users.edit.profile.id',$data->id);
		else $id=JFactory::getApplication()->getUserState('com_users.edit.profile.id',JFactory::getApplication()->input->get('id',null));
		
		$user_obj=JsnHelper::getUser($id);

		$session = JFactory::getSession();
		$original_id=$session->get('jsn_original_id',0);
		if($original_id!=$user_obj->id || $user_obj->authorise('core.edit', 'com_users'))
		{
			return true;
		}

    	$session->set('jsn_emailactivation.old', $user['email']);
	}

	private function changeEmailAfterSave($userId, $data, $isNew, $result, $error)
	{
		if(isset($data['email']))
		{
			$session = JFactory::getSession();
    		$old = $session->get('jsn_emailactivation.old', null);
    		if (!empty($old)) {
    			$user = JFactory::getUser($userId);
    			$activation = md5(mt_rand());
    			// get the user and store new email in User's Parameters
	            $user->setParam('emailactivation', $data['email']);
	            $user->setParam('emailactivation_token', $activation);
	            $user->email = $old;

	            // get the raw User Parameters
	            $params = $user->getParameters();

	            // force old email until new one is activated
	            $table = JTable::getInstance('user', 'JTable');
	            $table->load($userId);
	            $table->params = $params->toString();
	            $table->email = $old;

	            // save user data
	            if (!$table->store()) {
	                throw new RuntimeException($table->getError());
	            }
	            // store activation in session
	            $user->setParam('emailactivation_token', $activation);

	            // Send activation email
	            if ($this->sendActivationEmail($user->getProperties(), $activation, $data['email'])) {
	            	JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_JSN_EMAILACTIVATION_SENT', htmlspecialchars($data['email'])));
	            }
    		}
		}
	}

	private function sendActivationEmail($data, $token, $email)
    {
        $userID = (int) $data['id'];
        $baseURL = rtrim(JURI::root(), '/');
        $md5Token = md5($token);
        $data['siteurl'] = $baseURL . '/index.php?option=com_users&task=edit&emailactivation=1&u='. $userID .'&'. $md5Token .'=1';

        // Compile the user activated notification mail values.
        $config = JFactory::getConfig();
        $mailer = JFactory::getMailer();

        $emailSubject    = JText::sprintf(
            'COM_JSN_EMAILACTIVATION_SUBJECT',
            $data['name'],
            $config->get('sitename')
        );

        $emailBody = JText::sprintf(
            'COM_JSN_EMAILACTIVATION_BODY',
            $data['name'],
            $config->get('sitename'),
            $data['siteurl']
        );

        $mailer->setSender(array($config->get('mailfrom'), $config->get('fromname')));
        $mailer->addRecipient($email);
        $mailer->setSubject($emailSubject);
        $mailer->setBody($emailBody);
        
        if ($mailer->Send() !== true) {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_JSN_EMAILACTIVATION_FAILED') .': '. $send->message, 'error');
            return false;
        }

        return true;
    }
	
}
