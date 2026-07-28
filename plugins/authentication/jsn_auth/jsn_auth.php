<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


class PlgAuthenticationJsn_Auth extends JPlugin
{
	/**
	 * This method should handle any authentication and report back to the subject
	 *
	 * @access	public
	 * @param   array  Array holding the user credentials
	 * @param   array  Array of extra options
	 * @param   object	Authentication response object
	 * @return  boolean
	 * @since 1.5
	 */
	public function onUserAuthenticate(&$credentials, $options, &$response)
	{
		if(isset($options['type']) && $options['type'] == 'jsnconnect'){
			self::_setResponse($options, $response);
			$response->status 	= JAuthentication::STATUS_SUCCESS;
		}
		else//if (JFactory::getApplication()->isSite())
		{
			// Get a database object
			$db		= JFactory::getDbo();
			$query	= $db->getQuery(true)
				->select('username')
				->from('#__users')
				->where('email=' . $db->quote($credentials['username']));

			$db->setQuery($query);
			$result = $db->loadObject();

			$config = JComponentHelper::getParams('com_jsn');
			$logintype=$config->get('logintype', 'USERNAME');
			switch($logintype){
				case 'USERNAME':
					
				break;
				case 'USERNAMEMAIL':
					if(isset($result->username)) $credentials['username']=$result->username;
				break;
				case 'MAIL':
					if(isset($result->username)) $credentials['username']=$result->username;
					//else $credentials['username']='';
				break;
			}
		}
		
	}
	
	
	protected static function _setResponse($options, &$response)
	{
		$user = JUser::getInstance($options['user_id']); // Bring this in line with the rest of the system
		$response->email 			= $user->email;
		$response->fullname 	= $user->name;
		$response->username 	= $user->username;
		$response->language 	= $user->getParam('language');
		$response->error_message = '';
		//fix for j35
		$response->type		= 'socialconnect';
	}
}
