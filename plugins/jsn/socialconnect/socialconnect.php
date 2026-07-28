<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

class PlgJsnSocialconnect extends JPlugin
{
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		if(!(JFactory::getApplication()->input->get('option',false)=='com_jsn' && JFactory::getApplication()->input->get('tmplsocial',false)))
		{
			$excludeMenuItems = $this->params->get('excludeItemId','');
			if(is_array($excludeMenuItems) && in_array(JFactory::getApplication()->input->get('Itemid',false), $excludeMenuItems)) return;

			require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');

			$includeJsItemId = array_filter($this->params->get('includeJsItemId',array()));
			$js = true;
			if(is_array($includeJsItemId) && count($includeJsItemId) && !in_array(JFactory::getApplication()->input->get('Itemid',false), $includeJsItemId)) $js=false;
			
			if (JSN_TYPE == 'pro') SocialConnect::init($js);
		}
		else
		{
			JFactory::getApplication()->input->set('format','raw');
			JFactory::$document = JDocument::getInstance('raw');
		}
	}
	
	public function renderPlugin()
	{
		$excludeMenuItems = $this->params->get('excludeItemId','');
		if(is_array($excludeMenuItems) && in_array(JFactory::getApplication()->input->get('Itemid',false), $excludeMenuItems)) return;

		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if (JSN_TYPE == 'pro') SocialConnect::renderPlugin();
	}
	
	public static function getSocialLink($data, $config){
		$output=array();
		if($config->get('socialconnect_profilelink',1) && ((isset($data->facebook_id) && $data->facebook_id!='' && $config->get('facebook_enabled',0)) || (isset($data->twitter_id) && $data->twitter_id!='' && $config->get('twitter_enabled',0)) || (isset($data->google_id) && $data->google_id!='' && $config->get('google_enabled',0)) || (isset($data->linkedin_id) && $data->linkedin_id!='' && $config->get('linkedin_enabled',0)) ||  (isset($data->instagram_id) && $data->instagram_id!='' && $config->get('instagram_enabled',0)))) {
			$output[] = '<div class="jsn_profilesocial'.($config->get('socialconnect_profilelink_btnicon',0) ? ' icon' : '').'">';
			$output[] = '	<div>';
			if(isset($data->facebook_id) && $data->facebook_id && $config->get('facebook_enabled',0)) {
				if(strlen($data->facebook_id)<12) {
					$output[] = '			<a href="http://www.facebook.com/profile.php?id='.htmlspecialchars( urlencode( $data->facebook_id ) ).'" target="_blank"><button class="zocial facebook">Facebook</button></a>';
				}
				else {
					$output[] = '			<a href="http://facebook.com/'.htmlspecialchars( urlencode( $data->facebook_id ) ).'" target="_blank"><button class="zocial facebook">Facebook</button></a>';
				}
			}
			if(isset($data->twitter_id) && $data->twitter_id && $config->get('twitter_enabled',0)) {
				$output[] = '			<a href="https://twitter.com/intent/user?user_id='.htmlspecialchars( urlencode( $data->twitter_id ) ).'" target="_blank"><button class="zocial twitter">Twitter</button></a>';
			}
			// Google+ no more available :(
			/*if(isset($data->google_id) && $data->google_id && $config->get('google_enabled',0)) {
				$output[] = '			<a href="https://plus.google.com/'.htmlspecialchars( urlencode( $data->google_id ) ).'" target="_blank"><button class="zocial googleplus">Google+</button></a>';
			}*/
			if(isset($data->linkedin_id) && $data->linkedin_id && $config->get('linkedin_enabled',0)) {
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select('a.params')->from('#__jsn_users AS a')->where('a.id = '. (int) $data->id);
				$db->setQuery( $query );
				$params=$db->loadResult();
				$params=json_decode($params);
				if(isset($params->linkedin_url)) {
					$output[] =	'				<a href="'.htmlspecialchars(  $params->linkedin_url  ).'" target="_blank"><button class="zocial linkedin">LinkedIn</button></a>';
				}
			}
			if(isset($data->instagram_id) && $data->instagram_id && $config->get('instagram_enabled',0)) {
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select('a.params')->from('#__jsn_users AS a')->where('a.id = '. (int) $data->id);
				$db->setQuery( $query );
				$params=$db->loadResult();
				$params=json_decode($params);
				if(isset($params->instagram_url)) {
					$output[] =	'				<a href="https://www.instagram.com/'.htmlspecialchars(  $params->instagram_url  ).'" target="_blank"><button class="zocial instagram">Instagram</button></a>';
				}	
			}
			$output[] = '	</div>';
			$output[] = '</div>';
		}
		return implode('',$output);
	}

	public function renderBeforeFields($data, $config){
		require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
		if (JSN_TYPE == 'pro') echo PlgJsnSocialconnect::getSocialLink($data, $config);
	}

}

class SocialConnect
{
	public static function init($js = true){
		
		$enabled=false;
		if(SocialConnectFacebook::getInstance()->api->enabled) $enabled=true;
		if(SocialConnectTwitter::getInstance()->api->enabled) $enabled=true;
		if(SocialConnectGoogle::getInstance()->api->enabled) $enabled=true;
		if(SocialConnectLinkedIn::getInstance()->api->enabled) $enabled=true;
		if(SocialConnectInstagram::getInstance()->api->enabled) $enabled=true;
		
		if($enabled)
		{
			$xml = simplexml_load_file(JPATH_ADMINISTRATOR .'/components/com_jsn/jsn.xml');
			$version = (string)$xml->version;
			$config=JComponentHelper::getParams('com_jsn');
			$lang=JFactory::getLanguage();
			$lang->load('com_jsn');
			JHtml::_('bootstrap.framework');
			if($js) {
				$doc = JFactory::getDocument();
				$doc->addScript(JURI::root().'components/com_jsn/assets/js/socialconnect.min.js?v='.$version);
				$doc->addStylesheet(JURI::root().'components/com_jsn/assets/css/connect.min.css?v='.$version);
				
				if($config->get('socialconnect_loginlink',1))
				{
					$script='jQuery(document).ready(function(){
						jQuery(".login").append(\'<div class="socialconnect'.($config->get('socialconnect_loginlink_btnicon',0) ? ' icon': '').'">'.($config->get('socialconnect_loginlink_btnicon',0) ? '<b>'.JText::sprintf('COM_JSN_LOGINWITH','').'</b>: ': '').'</div>\');
					});';
					$doc->addScriptDeclaration( $script );
				}
				SocialConnectFacebook::getInstance()->getJS();
				SocialConnectTwitter::getInstance()->getJS();
				SocialConnectGoogle::getInstance()->getJS();
				SocialConnectLinkedIn::getInstance()->getJS();
				SocialConnectInstagram::getInstance()->getJS();
			}
			
		}
	}
	
	public static function renderPlugin()
	{
		$view=JFactory::getApplication()->input->get('view',false);
		$task=JFactory::getApplication()->input->get('task',false);

		switch ( $view ) {
			case 'facebook':
				switch ( $task ) {
					case 'accesstoken':
						SocialConnectFacebook::getInstance()->accessToken();
						break;
					case 'registration':
						SocialConnectFacebook::getInstance()->showRegistration();
						break;
					case 'storeregistration':
						SocialConnectFacebook::getInstance()->storeRegistration();
						break;
					case 'reset':
						SocialConnectFacebook::getInstance()->resetSession();
						break;
					case 'unset':
						SocialConnectFacebook::getInstance()->unSetConnectId();
						break;
					default:
						SocialConnectFacebook::getInstance()->syncUser();
						break;
				}
			break;
			case 'twitter':
				switch ( $task ) {
					case 'requesttoken':
						header('Access-Control-Allow-Origin: *');
						SocialConnectTwitter::getInstance()->requestToken();
						break;
					case 'accesstoken':
						SocialConnectTwitter::getInstance()->accessToken();
						break;
					case 'registration':
						SocialConnectTwitter::getInstance()->showRegistration();
						break;
					case 'storeregistration':
						SocialConnectTwitter::getInstance()->storeRegistration();
						break;
					case 'reset':
						SocialConnectTwitter::getInstance()->resetSession();
						break;
					case 'unset':
						SocialConnectTwitter::getInstance()->unSetConnectId();
						break;
					default:
						SocialConnectTwitter::getInstance()->syncUser();
						break;
				}
			break;
			case 'google':
				switch ( $task ) {
					case 'accesstoken':
						SocialConnectGoogle::getInstance()->accessToken();
						break;
					case 'registration':
						SocialConnectGoogle::getInstance()->showRegistration();
						break;
					case 'storeregistration':
						SocialConnectGoogle::getInstance()->storeRegistration();
						break;
					case 'reset':
						SocialConnectGoogle::getInstance()->resetSession();
						break;
					case 'unset':
						SocialConnectGoogle::getInstance()->unSetConnectId();
						break;
					default:
						SocialConnectGoogle::getInstance()->syncUser();
						break;
				}
			break;
			case 'linkedin':
				switch ( $task ) {
					case 'accesstoken':
						SocialConnectLinkedIn::getInstance()->accessToken();
						break;
					case 'registration':
						SocialConnectLinkedIn::getInstance()->showRegistration();
						break;
					case 'storeregistration':
						SocialConnectLinkedIn::getInstance()->storeRegistration();
						break;
					case 'reset':
						SocialConnectLinkedIn::getInstance()->resetSession();
						break;
					case 'unset':
						SocialConnectLinkedIn::getInstance()->unSetConnectId();
						break;
					default:
						SocialConnectLinkedIn::getInstance()->syncUser();
						break;
				}
			break;
			case 'instagram':
				switch ( $task ) {
					case 'accesstoken':
						SocialConnectInstagram::getInstance()->accessToken();
						break;
					case 'registration':
						SocialConnectInstagram::getInstance()->showRegistration();
						break;
					case 'storeregistration':
						SocialConnectInstagram::getInstance()->storeRegistration();
						break;
					case 'reset':
						SocialConnectInstagram::getInstance()->resetSession();
						break;
					case 'unset':
						SocialConnectInstagram::getInstance()->unSetConnectId();
						break;
					default:
						SocialConnectInstagram::getInstance()->syncUser();
						break;
				}
			break;
			case 'socialactivation':
				$lang=JFactory::getLanguage();
				$lang->load('com_users');
				$app=JFactory::getApplication();
				require_once(JPATH_SITE.'/components/com_users/models/registration.php');
				$model=new UsersModelRegistration();
				$input 	 = JFactory::getApplication()->input;
				$token = $input->getAlnum('token');
				if ($token === null || strlen($token) !== 32)
				{
					JError::raiseError(403, JText::_('JINVALID_TOKEN'));

					return false;
				}
				$user = $model->activate($token);

				// Check for errors.
				if ($user === false)
				{
					$app->enqueueMessage(JText::sprintf('COM_USERS_REGISTRATION_SAVE_FAILED', $model->getError()), 'warning');
					return false;
				}

				if($user->block)
				{
					$app->enqueueMessage(JText::sprintf('COM_USERS_REGISTRATION_VERIFY_SUCCESS'), 'success');
					$app->redirect(JRoute::_('index.php?option=com_users&view=login'));
					return false;
				}

				/* Login */
				$credentials=array('username'=>$user->username,'password'=> 'BLANK', 'password_clear'=>$user->password);
				$options=array('user_id'=>$user->id,'type'=>'jsnconnect', 'autoregister'=>false);
				
				// Redirect After Login
				$config = JComponentHelper::getParams('com_jsn');
				$app->login($credentials, $options);
				$app->enqueueMessage(JText::sprintf('COM_JSN_SOCIALACTIVATION'), 'success');
				$app->redirect(JRoute::_(JFactory::getApplication()->getUserState('users.login.form.return','index.php?option=com_users&view=registration&layout=complete')));
			break;
		}
		if(JFactory::getApplication()->input->get('tmplsocial',false)) exit;
	}
	
	public function unSetConnectId(){
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
		$user=JsnHelper::getUser();
		$app=JFactory::getApplication();
		if($user->id)
		{
			$db=JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->update("#__jsn_users");
			$query->set($db->quoteName($this->gen->fieldname).' = \'\'');
			$query->where('id = '. $user->id);
			$db->setQuery($query);
			if ( !$db->execute())
			{
				return false;
			}
			$user->setValue($this->gen->fieldname,'');
			$jsnConfig = JComponentHelper::getParams('com_jsn');
			$usergroups=$jsnConfig->get($this->name.'_usergroups', array());
			if(count($usergroups)>0)
			{
				foreach($usergroups as $usergroup)
				{
					JUserHelper::removeUserFromGroup($user->id,$usergroup);
				}
			}
		}
		$app->enqueueMessage(JText::sprintf('COM_JSN_UNLINKSUCCESS',ucfirst($this->name)),'success');
		$profileMenu=JFactory::getApplication()->getMenu()->getItems('link','index.php?option=com_jsn&view=profile',true);
		if(isset($profileMenu->id)) $Itemid=$profileMenu->id;
		else $Itemid='';
		$app->redirect(JRoute::_('index.php?option=com_jsn&view=profile&Itemid='.$Itemid,false));
	}
	
	static public function getParam($var,$index,$default=null){
		if(is_array($var))
		{
			if(isset($var[$index])) return $var[$index];
			else return $default;
		}
		if(is_object($var))
		{
			if(isset($var->$index)) return $var->$index;
			else return $default;
		}
		return $var;
	}
	
	public static function getIParray() {
		global $_SERVER;

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip_adr_array		=	explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
		} else {
			$ip_adr_array		=	array();
		}
		$ip_adr_array[]			=	$_SERVER['REMOTE_ADDR'];
		return $ip_adr_array;
	}
	
	static public function getUrl($view,$task=null,$loadSite=false)
	{
		if($task==null)
		{
			if($loadSite) return JURI::root().'index.php?option=com_jsn&view='.$view;
			else return JURI::root().'index.php?option=com_jsn&tmplsocial=1&view='.$view;
		}
		else
		{
			if($loadSite) return JURI::root().'index.php?option=com_jsn&view='.$view.'&task='.$task;
			else return JURI::root().'index.php?option=com_jsn&tmplsocial=1&view='.$view.'&task='.$task;
		}
	}
	
	static public function httpRequest( $url, $method = 'GET', $body = array(), $headers = array(), $login = null, $agent = null ) {
		$response											=	null;

		if ( function_exists( 'curl_init' ) ) {
			$ch												=	curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url );

			if ( $method == 'POST' ) {
				curl_setopt( $ch, CURLOPT_POST, true );
			}

			if ( $body ) {
				if ( $method == 'POST' ) {
					curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
				} else {
					curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $body, null, '&' ) );
				}
			}

			if ( $headers ) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
			} else {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Expect:' ) );
			}

			if ( $login ) {
				curl_setopt( $ch, CURLOPT_USERPWD, $login );
				curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			}

			if ( $agent ) {
				curl_setopt( $ch, CURLOPT_USERAGENT, $agent );
			}

			curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

			if ( ( ! ini_get( 'safe_mode' ) ) && ( ! ini_get( 'open_basedir' ) ) ) {
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
			}

			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_HEADER, true );
			curl_setopt( $ch, CURLINFO_HEADER_OUT, true );

			
			if ( version_compare( phpversion(), '5.3.0', '>=' ) ) {
				$curlVersion							=	curl_version();

				if ( isset( $curlVersion['version'] ) && version_compare( $curlVersion['version'], '7.10.8', '>=' ) ) {
					curl_setopt( $ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
				}
			}
			

			$result											=	curl_exec( $ch );
			$httpCode										=	curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$headerOut										=	curl_getinfo( $ch, CURLINFO_HEADER_OUT );
			$error											=	curl_error( $ch );

			if ( $result ) {
				list( $rawResponseHeaders, $results )		=	explode( "\r\n\r\n", $result, 2 );
			} else {
				$rawResponseHeaders							=	null;
				$results									=	null;
			}

			$responseHeaders								=	array();

			if ( $rawResponseHeaders ) {
				$responseHeaderLines						=	explode( "\r\n", $rawResponseHeaders );

				foreach ( $responseHeaderLines as $headerLine ) {
					if ( $headerLine ) {
						$headerParts						=	explode( ': ', $headerLine, 2 );

						if ( count( $headerParts ) > 1 ) {
							list( $header, $value )			=	$headerParts;
						} else {
							$header							=	'Other';
							$value							=	implode( "\n", $headerParts );
						}

						if ( isset( $responseHeaders[$header] ) ) {
							$responseHeaders[$header]		.=	"\n" . $value;
						} else {
							$responseHeaders[$header]		=	$value;
						}
					}
				}
			}

			$sentHeaders									=	array();

			if ( $headerOut ) {
				$sentHeaderLines							=	explode( "\r\n", $headerOut );

				foreach ( $sentHeaderLines as $headerLine ) {
					if ( $headerLine ) {
						$headerParts						=	explode( ': ', $headerLine, 2 );

						if ( count( $headerParts ) > 1 ) {
							list( $header, $value )			=	$headerParts;
						} else {
							$header							=	'Other';
							$value							=	$headerParts[0];
						}

						if ( isset( $sentHeaders[$header] ) ) {
							$sentHeaders[$header]			.=	"\n" . $value;
						} else {
							$sentHeaders[$header]			=	$value;
						}
					}
				}
			}

			curl_close( $ch );

			$response										=	array(	'http_code' => $httpCode,
																		'results' => $results,
																		'error' => $error,
																		'headers' => $responseHeaders,
																		'headers_out' => $sentHeaders
																	);
		} else {
			trigger_error( 'cURL not installed', E_USER_ERROR );
		}

		return $response;
	}
	
	public function getUserID( $id = null ) {
		static $cache		=	array();

		if ( $id === null ) {
			$id				=	$this->getConnectID();
		}

		if ( ! isset( $cache[$id] ) ) {
			$userId			=	null;

			if ( $id ) {
				$db = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query->select('a.id')->from('#__jsn_users AS a')->where('a.'.$this->gen->fieldname.' = '.$db->quote($id));
				$db->setQuery( $query );
				$userId=$db->loadResult();
			}

			$cache[$id]		=	$userId;
		}

		return $cache[$id];
	}
	
	public function getUser( $id = null ) {
		static $cache		=	array();

		if ( $id === null ) {
			$id				=	$this->getUserID();
		}

		if ( ! isset( $cache[$id] ) ) {
			require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
			$cache[$id]=JsnHelper::getUser( (int) $id);
		}

		return $cache[$id];
	}

	public function getToken() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$session	=	$this->getSession();

			if ( $session ) {
				$cache	=	stripslashes( SocialConnect::getParam( $session, 'access_token', null ) );
			}
		}

		return $cache;
	}

	public function getConnectID() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$session	=	$this->getSession();

			if ( $session ) {
				$cache	=	stripslashes( SocialConnect::getParam( $session, 'user_id', null ) );
			}
		}

		return $cache;
	}

	public function getRawSession() {
		static $cache		=	null;

		if ( ! isset( $cache ) ) {
			$session		=	$this->getSession();

			if ( $session ) {
				if ( isset( $session['raw'] ) ) {
					$cache	=	(array) json_decode( $session['raw'] );
				} else {
					$cache	=	array();
				}
			}
		}

		return $cache;
	}

	public function setSession( $userId = null, $accessToken = null, $raw = null ) {
		$cookie				=	array(	'user_id' => $userId,
										'access_token' => $accessToken,
										'raw' => ( $raw ? json_encode( $raw ) : null ),
										'signature' => md5( $userId . $this->gen->secret )
									);

		$session		=	JFactory::getSession();

		$session->set( $this->gen->session_id, $cookie );
	}

	public function resetSession() {
		$this->setSession();
	}

	public function getSession() {
		static $cache			=	null;

		if ( ! isset( $cache ) ) {
			$sessions		=	JFactory::getSession();
			$session		=	$sessions->get( $this->gen->session_id );

			if ( $session ) {
				$signature		=	md5( stripslashes( SocialConnect::getParam( $session, 'user_id', null ) ) . $this->gen->secret );

				if ( $signature === stripslashes( SocialConnect::getParam( $session, 'signature', null ) ) ) {
					$cache		=	$session;
				}
			}
		}

		return $cache;
	}
	
	
	
	public function syncUser( $userId = null, $userVars = array() ) {
		
		$app=JFactory::getApplication();
		if ( ! $this->getConnectID() ) {
			$app->enqueueMessage(JText::_('COM_JSN_IDNOTFOUND'),'error');
			$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
			return false;
		}
		
		$myId=JFactory::getUser()->id;
		
		if ( ! $userId ) {
			$userId=$this->getUserID();
		}
		
		if ( $myId ) {
			if ( $userId ) {
				if ( $userId != $myId ) {
					$app->enqueueMessage(JText::_('COM_JSN_IDALREADYUSED'),'error');
					$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
					return false;
				}
			} else {
				$userId=$myId;
			}
		}
		require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
		$user=JsnHelper::getUser( (int) $userId);
		if ( $userVars ) foreach ( $userVars as $k => $v ) {
			$user->set( $k, $v );
		}
		
		if ( $user->id ) {
			if ( ! $this->updateUser( $user ) ) {
				$app->enqueueMessage($user->getUserError(),'error');
				$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
				return false;
			} elseif ( $myId ) {
				$app->enqueueMessage(JText::_('COM_JSN_ACCOUNTLINKED'),'message');
				$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
			}
			if ( ! $myId  ) {
				$login							=	$this->loginUser( $user );
				if ( $login ) {
					return true;
				} else {
					$app->enqueueMessage($user->getUserError(),'error');
					$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
					return false;
				}
			}
		} else {

			$registration					=	$this->registerUser( $user );
			
			if ( $registration ) {
				$app->redirect(SocialConnect::getUrl( $this->name ).'&return='.JFactory::getApplication()->input->get('return','','raw'));
			} else {
				$app->enqueueMessage($user->getUserError(),'error');
				$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
				return false;
			}
		}
		return true;
	}
	
	private function registerUser( &$user ) {
		$app=JFactory::getApplication();
		$userConfig = JComponentHelper::getParams('com_users');
		$jsnConfig = JComponentHelper::getParams('com_jsn');
		$connectUser			=	$this->getConnectUser( null, $user );
		if ( ! $connectUser ) {
			if($this->name=='google') $app->enqueueMessage(JText::_('COM_JSN_GOOGLEUNAVAILABLE'),'error');
			else $app->enqueueMessage(JText::_('COM_JSN_USERFAILEDTOINITIATE'),'error');
			$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
			return false;
		}
		
		$username				=	$connectUser->username;
		
		if( ! trim($username,'-_ ') ){
			$username='user_'.date('YmdHis');
		}
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.email')->from('#__users AS a')->where('a.username = '.$db->quote($username));
		$db->setQuery( $query );
		if($userCheckUsername=$db->loadResult()){
			
			$username=$username.'_'.$connectUser->id;
			$query = $db->getQuery(true);
			$query->select('a.email')->from('#__users AS a')->where('a.username = '.$db->quote($username));
			$db->setQuery( $query );
			if($userCheckUsername=$db->loadResult()){
				//echo($db->loadResult());die();
				$app->enqueueMessage(JText::_('COM_JSN_USERNAMEALREADYUSED'),'error');
				$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
				return false;
			}
		}
		
		$connectEmail			=	$user->email;
		if ( ! $connectEmail ) {
			$connectEmail		=	$connectUser->email;
		}
		
		$query = $db->getQuery(true);
		$query->select('a.id')->from('#__users AS a')->where('a.email = '.$db->quote($connectEmail));
		$db->setQuery( $query );
		$emailExists=$db->loadResult();
		$emailInvalid			=	preg_match( '/@invalid(?:\.com)?|mail\.invalid$/', $connectEmail );
		
		if ( $emailExists || $emailInvalid ) {
			$error				=	null;

			if ( $emailExists ) {
				if($jsnConfig->get('socialconnect_skipconfirm', 0)){
					$this->syncUser($emailExists);
				}
				else{
					$error			=	JText::_( 'COM_JSN_EMAILALREADYUSED' );
					$error		.=	' ' . JText::_( 'COM_JSN_EMAILALREADYUSED_DESC' );
				}
				
			} else {
				$error			=	JText::_( 'COM_JSN_REGISTRATIONNOTCOMPLETE' );
				$error	.=	' ' . JText::_( 'COM_JSN_REGISTRATIONNOTCOMPLETE_DESC' );
			}
			$app->enqueueMessage($error,'message');
			$app->redirect(SocialConnect::getUrl( $this->name, 'registration', true ).'&return='.JFactory::getApplication()->input->get('return','','raw'));

			return false;
		}
		
		$user->username=$username;//echo($username);die();
		$user->name=$connectUser->name;
		$user->email=$connectEmail;
		$user->setValue($this->gen->fieldname,$connectUser->id);
		//die($this->gen->fieldname.$connectUser->id);
		//print_r($user);die();
		$namestyle=$jsnConfig->get('namestyle', 'FIRSTNAME_LASTNAME');
		switch($namestyle){
			case 'FIRSTNAME_LASTNAME':
				$parts=explode(' ',$user->name,2);
				//print_r($parts);die();
				if(isset($parts[0])) $user->firstname=$parts[0];
				if(isset($parts[1])) $user->lastname=$parts[1];
				else $user->lastname='';
			break;
			case 'FIRSTNAME_SECONDNAME_LASTNAME':
				$parts=explode(' ',$user->name,2);
				if(isset($parts[0])) $user->firstname=$parts[0];
				if(isset($parts[1]) && isset($parts[2]))
				{
					$user->secondname=$parts[1];
					$user->lastname=$parts[2];
				}
				elseif(isset($parts[1])) $user->lastname=$parts[1];
				else $user->lastname=''; 
			break;
			case 'FIRSTNAME':
				$user->firstname=$user->name;
			break;
		}
		$salt=JUserHelper::getSalt();
		$password=JUserHelper::genRandomPassword();
		$password_cripted=JUserHelper::getCryptedPassword($password,$salt);
		$user->password=$password_cripted;
		$this->mapFields($user,$connectUser->user);

		if($jsnConfig->get('socialconnect_confirmemail',0)==1 || isset($user->_EmailNotConfirmed)){
			// Activation
			$user->activation = JApplication::getHash(JUserHelper::genRandomPassword());
			$user->block = 1;
		}
		//print_r($user);die();
		if($user->save())
		{
			// Set Groups
			JUserHelper::addUserToGroup($user->id,$userConfig->get('new_usertype',2));
			$usergroups=$jsnConfig->get($this->name.'_usergroups', array());
			if(count($usergroups)>0)
			{
				foreach($usergroups as $usergroup)
				{
					JUserHelper::addUserToGroup($user->id,$usergroup);
				}
			}
			
			$user->avatar=$this->setAvatar($connectUser,$user->id);
			$user->save();
			
			// Activation Mail to User
			$config = JFactory::getConfig();
			$data=array();
			$data['fromname'] = $config->get('fromname');
			$data['mailfrom'] = $config->get('mailfrom');
			$data['sitename'] = $config->get('sitename');
			$data['siteurl'] = JUri::root();
			$data['name']=$user->name;
			if($jsnConfig->get('logintype','USERNAME')=='MAIL')
				$data['username']=$user->email;
			else
				$data['username']=$user->username;
			$data['password']=$password;
			$lang=JFactory::getLanguage();
			$lang->load('com_users');
			$params = JComponentHelper::getParams('com_users');

			// Notification Mail to Admin
			if ($params->get('mail_to_admin') == 1)
			{
				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				).' (with '.ucfirst($this->name).')';

				$emailBodyAdmin = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY',
					$data['name'],
					$data['username'],
					$data['siteurl']
				);

				// Get all admin users
				$query = $db->getQuery(true);
				$query->select($db->quoteName(array('name', 'email', 'sendEmail')))->from($db->quoteName('#__users'))->where($db->quoteName('sendEmail') . ' = ' . 1);

				$db->setQuery($query);

				$rows = $db->loadObjectList();
				
				// Send mail to all superadministrators id
				foreach ($rows as $row)
				{
					$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $row->email, $emailSubject, $emailBodyAdmin);
				}
			}

			if($jsnConfig->get('socialconnect_confirmemail',0)==1 || isset($user->_EmailNotConfirmed)){
				if(isset($user->_EmailNotConfirmed)) unset($user->_EmailNotConfirmed);
				// Activation Mail
				$uri = JUri::getInstance();
				$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));

				$data['activate'] = $base . JRoute::_('index.php?option=com_jsn&view=socialactivation&token=' . $user->activation, false);
				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				).' (with '.ucfirst($this->name).')';
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username'],
					$data['password']
				).JText::sprintf(
					'COM_JSN_SOCIALEMAIL_REGISTERED_WITH_ACTIVATION_ADDS',
					ucfirst($this->name)
				);
				$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $user->email, $emailSubject, $emailBody);
				// Activation Redirect+Message
				$app=JFactory::getApplication();
				if($userConfig->get('useractivation') == 2) $app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_COMPLETE_VERIFY'));
				else $app->enqueueMessage(JText::_('COM_USERS_REGISTRATION_COMPLETE_ACTIVATE'));
				$app->redirect(JRoute::_('index.php?option=com_users&view=registration&layout=complete', false));
			}
			else{
				// Confirm Mail to User
				$emailSubject = JText::sprintf(
					'COM_USERS_EMAIL_ACCOUNT_DETAILS',
					$data['name'],
					$data['sitename']
				).' (with '.ucfirst($this->name).')';
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_BODY',
					$data['name'],
					$data['sitename'],
					$data['siteurl'],
					$data['username'],
					$data['password']
				).JText::sprintf(
					'COM_JSN_SOCIALEMAIL_REGISTERED_WITH_ACTIVATION_ADDS',
					ucfirst($this->name)
				);
				$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $user->email, $emailSubject, $emailBody);
			}

			return true;
		}
		
		return false;
		
	}
	
	
	private function updateUser( &$user ) {
		
		$connectUserid		=	$this->getConnectID();
		if ( $user->getValue( $this->gen->fieldname ) != $connectUserid ) {
			$db=JFactory::getDbo();
			$db->setQuery('INSERT IGNORE INTO #__jsn_users(id) VALUES('.$user->id.');');
			$db->execute();
			$query = $db->getQuery(true);
			$query->update("#__jsn_users");
			$query->set($db->quoteName($this->gen->fieldname).' = '.$db->quote($connectUserid));
			$query->where('id = '. $user->id);
			$db->setQuery($query);
			if ( !$db->execute())
			{
				return false;
			}
			$user->setValue($this->gen->fieldname,$connectUserid);
			/* ADD URL ID FOR LINKEDIN */
			if($this->gen->fieldname=='linkedin_id'){
				$this->profileUrlFix($user->id);
			}
			/* ADD URL ID FOR INSTAGRAM */
			if($this->gen->fieldname=='instagram_id'){
				$this->profileUrlFix($user->id);
			}
			$jsnConfig = JComponentHelper::getParams('com_jsn');
			$usergroups=$jsnConfig->get($this->name.'_usergroups', array());
			if(count($usergroups)>0)
			{
				foreach($usergroups as $usergroup)
				{
					JUserHelper::addUserToGroup($user->id,$usergroup);
				}
			}
		}
		return true;
	}
	
	private function loginUser( &$user  ) {
		if ( $user->getValue( $this->gen->fieldname ) == $this->getConnectID() )
		{
			/* ADD URL ID FOR LINKEDIN */
			if($this->gen->fieldname=='linkedin_id'){
				$this->profileUrlFix($user->id);
			}
			/* ADD URL ID FOR INSTAGRAM */
			if($this->gen->fieldname=='instagram_id'){
				$this->profileUrlFix($user->id);
			}
			
			$credentials=array('username'=>$user->username,'password'=> 'BLANK', 'password_clear'=>$user->password);
			$options=array('user_id'=>$user->id,'type'=>'jsnconnect', 'autoregister'=>false);
			$app=JFactory::getApplication();
			// Redirect After Login
			$config = JComponentHelper::getParams('com_jsn');
			if(!$config->get('loginUrl')) JFactory::getApplication()->setUserState('users.login.form.return', base64_decode(JFactory::getApplication()->input->get('return','','raw')));
			$app->login($credentials, $options);
			$app->redirect(JRoute::_(JFactory::getApplication()->getUserState('users.login.form.return')));
			return true;
		}
		return false;
	}
	
	public function showRegistration() {
		$valid=false;
		if ( ! JFactory::getUser()->id ) {
			$user							=	$this->getUser();
			$connectUser					=	$this->getConnectUser( null, $user );

			if ( ( ! $user->id ) && $connectUser ) {
				$valid=true;
				
				//global $_JSNPLUGINS;
				$config = JComponentHelper::getParams('com_jsn');
				switch($config->get('logintype','USERNAME'))
				{
					case 'USERNAME': $stringUsername=JText::_('COM_JSN_USERNAME'); break;
					case 'MAIL': $stringUsername=JText::_('COM_JSN_EMAIL'); break;
					case 'USERNAMEMAIL': $stringUsername=JText::_('COM_JSN_USERNAME').' / '.JText::_('COM_JSN_EMAIL'); break;
				}
				$html=array();
				$html[]='<div id="socialconnect" class="row-fluid socialconnect_'.$this->name.'"><div class="span12 col-lg-12">';
				//$html[]='<div class="page-header"><h2>'.JText::_('COM_JSN_COMPLETEREGISTRATIONEMAIL').'</h2></div>';
				$html[]='<form id="member-socialregistration" action="'.SocialConnect::getUrl($this->name, 'storeregistration').'" method="post" class="form-validate form-horizontal well" enctype="multipart/form-data">
								<fieldset>
									<legend>'.JText::_('COM_JSN_COMPLETEREGISTRATIONEMAIL').'</legend>
																<div class="control-group">
									<div class="control-label">
									<label id="jform_email1-lbl" for="jform_email1" class="hasTip required" title="">'.JText::_('COM_JSN_EMAIL').'<span class="star">&nbsp;*</span></label>										</div>
									<div class="controls">
										<input type="email" name="email1" class="validate-email required" id="jform_email1" value="" size="30" required="required" aria-required="true">					</div>
								</div>';
		
				if($config->get('confirmusermail',0)) $html[]='										<div class="control-group">
									<div class="control-label">
									<label id="jform_email2-lbl" for="jform_email2" class="hasTip required" title="">'.JText::_('COM_JSN_EMAILCONFIRM').'<span class="star">&nbsp;*</span></label>										</div>
									<div class="controls">
										<input type="email" name="email2" class="validate-email required" id="jform_email2" value="" size="30" required="required" aria-required="true">					</div>
								</div>';
				$html[]='			<div class="form-actions">
							<button type="submit" class="btn btn-primary validate">'.JText::_('COM_JSN_CONNECTEMAIL').'</button>
					
							<input type="hidden" name="return" value="'.JFactory::getApplication()->input->get('return','','raw').'">		</div>
							</fieldset>
					</form>';
				
	
				//$html[]='<div class="page-header"><h2>'.JText::_('COM_JSN_COMPLETEREGISTRATIONLINK').'</h2></div>';
				$html[]='<form action="'.SocialConnect::getUrl($this->name, 'storeregistration').'" method="post" class="form-horizontal well">
								<fieldset>
									<legend>'.JText::_('COM_JSN_COMPLETEREGISTRATIONLINK').'</legend>
				
																<div class="control-group">
										<div class="control-label">
											<label id="username-lbl" for="username" class=" required">'.$stringUsername.'<span class="star">&nbsp;*</span></label>						</div>
										<div class="controls">
											<input type="text" name="username" id="username" value="" class="validate-username required" size="25" required="required" aria-required="true">						</div>
									</div>
																				<div class="control-group">
										<div class="control-label">
											<label id="password-lbl" for="password" class=" required">'.JText::_('COM_JSN_PASSWORD').'<span class="star">&nbsp;*</span></label>						</div>
										<div class="controls">
											<input type="password" name="password" id="password" value="" class="validate-password required" size="25" required="required" aria-required="true">						</div>
									</div>
														<div class="control-group">
							</div>
							<div class="form-actions">
							<button type="submit" class="btn btn-primary validate">'.JText::_('COM_JSN_CONNECTLINK').'</button>
							<input type="hidden" name="return" value="'.JFactory::getApplication()->input->get('return','','raw').'">		</div>
							</fieldset>
					</form>';
				$html[]='</div></div>';
				echo implode('', $html);
			}
		}
		if($valid){
			return true;
		}
		else
		{
			$app=JFactory::getApplication();
			$app->enqueueMessage('Not authorized','error');
			$app->redirect(JRoute::_(base64_decode(JFactory::getApplication()->input->get('return','','raw'))));
			return false;
		}
	}
	
	
	public function storeRegistration() {
		$post										=	array();
		$userId										=	null;
		$error										=	null;
		
		
		if ( ! JFactory::getUser()->id ) {
			$user									=	$this->getUser();
			$connectUser							=	$this->getConnectUser( null, $user );

			if ( ( ! $user->id ) && $connectUser ) {
				$username							=	stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->post->getArray(), 'username', null ) );
				$password							=	stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->post->getArray(), 'password', null ) );

				if ( $username || $password ) {
					$valid							=	false;
					
					$db = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query->select('a.*')->from('#__users AS a')->where('(a.username = '.$db->quote($username).' OR a.email = '.$db->quote($username).')');
					$db->setQuery( $query );
					if($foundUser=$db->loadObject()){
						//print_r($foundUser);
						$salt=substr($foundUser->password,strpos($foundUser->password,':')+1);
						$password_cripted=JUserHelper::getCryptedPassword($password,$salt).':'.$salt;
						if($foundUser->password==$password_cripted || $foundUser->password=='$'.$salt) $valid=true;
					}

					if ( ! $valid ) {
						$error						=	JText::_( 'Invalid login credentials! Please login to link an existing account or supply a valid email address to complete registration.' );
					} else {
						$userId						=	$foundUser->id;
					}
				} else {
					$config = JComponentHelper::getParams('com_jsn');
					
					$email						=	stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->post->getArray(), 'email1', null ) );
					if($config->get('confirmusermail',0)) $confirmEmail				=	stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->post->getArray(), 'email2', null ) );
					else $confirmEmail=$email;
					
					if(filter_var($email, FILTER_VALIDATE_EMAIL) && $email==$confirmEmail) $emailInvalid=false;
					else $emailInvalid=true;
					
					$db=JFactory::getDbo();
					$query = $db->getQuery(true);
					$query->select('a.id')->from('#__users AS a')->where('a.email = '.$db->quote($email));
					$db->setQuery( $query );
					$emailExists=$db->loadResult();
					
					$post['email']		=	$email;
					if($config->get('socialconnect_confirmemail',0)==2) $post['_EmailNotConfirmed'] = true; 
					
					if ( $emailExists ) {
						$error						.=	JText::_( 'COM_JSN_EMAILALREADYUSED' );
						$error					.=	' ' . JText::_( 'COM_JSN_EMAILALREADYUSED_DESC' );
						
					} elseif ( $emailInvalid ) {
						$error						.=	JText::_( 'COM_JSN_REGISTRATIONNOTCOMPLETE' );
						$error				.=	' ' . JText::_( 'COM_JSN_REGISTRATIONNOTCOMPLETE_DESC' );
					}
				}
			}
		}

		if ( $error ) {
			$app=JFactory::getApplication();
			$app->enqueueMessage($error,'error');
			$app->redirect(SocialConnect::getUrl( $this->name, 'registration', true ).'&return='.JFactory::getApplication()->input->get('return','','raw'));
			return false;
		} else {
			$this->syncUser( $userId, $post );
		}
	}
	
	public function setAvatar( $connectUser, $userId ) {

		$avatarImg									=	$connectUser->avatar;

		if ( $avatarImg ) {
			$avatarName								=	$connectUser->id;

			$request								=	SocialConnect::httpRequest( $avatarImg );

			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
				$ext								=	pathinfo( $avatarImg, PATHINFO_EXTENSION );
				
				if ( ! $ext ) {
					if ( isset( $request['headers']['Content-Type'] ) ) {
						if ( preg_match( '/image\/(\w+)/', $request['headers']['Content-Type'], $matches ) ) {
							if ( isset( $matches[1] ) ) {
								$ext				=	$matches[1];
							}
						}
					}
					if ( isset( $request['headers']['content-type'] ) ) {
						if ( preg_match( '/image\/(\w+)/', $request['headers']['content-type'], $matches ) ) {
							if ( isset( $matches[1] ) ) {
								$ext				=	$matches[1];
							}
						}
					}
				}
				
				if ( $ext ) {
					$ext=substr($ext,0,3);
					
					$ext							=	strtolower( $ext );

					if ( ! in_array( $ext, array( 'jpe', 'jpg', 'png', 'gif' ) ) ) {
						return null;
					}

					$alias='avatar';
					$db=JFactory::getDbo();
					$query = $db->getQuery(true);
					$query->select('a.*')->from('#__jsn_fields AS a')->where('a.alias = '.$db->quote($alias));
					$db->setQuery( $query );
					if($avatarField = $db->loadObject())
					{
						$registry = new JRegistry;
						$registry->loadString($avatarField->params);
						$avatarField->params = $registry->toArray();

						if(isset($avatarField->params['image_path']) && !empty($avatarField->params['image_path']))
							$upload_path=$avatarField->params['image_path']; 
						else 
							$upload_path='images/profiler/';

						$upload_dir=JPATH_SITE.'/'.$upload_path;
	
						$md5=md5(time().rand());

						$tmpName=$upload_dir.'tmp_'.$alias.$userId.'_'.$md5.'.'.$ext;

						file_put_contents($tmpName, $request['results']);

						if(file_exists(JPATH_ADMINISTRATOR.'/components/com_k2/lib/class.upload.php')) require_once(JPATH_ADMINISTRATOR.'/components/com_k2/lib/class.upload.php');
						else require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/assets/class.upload.php');
						$foo = new Upload($tmpName);
						if ($foo->uploaded)
						{
							// Delete old file
							foreach (glob($upload_dir.$alias.$userId.'*') as $deletefile)
							{
								unlink($deletefile);
							}

							// Store & Resize Image Thumbs
							$filename=$alias.$userId.'mini_'.$md5;
							$foo->file_new_name_body = $filename;
							$foo->image_resize = true;
							$foo->image_ratio_crop = true;
							$foo->image_convert = 'jpg';
							if($avatarField->params['image_thumbwidth']!=0) $foo->image_x = $avatarField->params['image_thumbwidth'];
							if($avatarField->params['image_thumbheight']!=0) $foo->image_y = $avatarField->params['image_thumbheight'];
							//die($foo->image_x);
							$foo->Process($upload_dir);
							// Store & Resize Image
							$filename=$alias.$userId.'_'.$md5;
							$foo->file_new_name_body = $filename;
							$foo->image_resize = true;
							$foo->image_ratio_crop = true;
							$foo->image_convert = 'jpg';
							if($avatarField->params['image_width']!=0) $foo->image_x = $avatarField->params['image_width'];
							if($avatarField->params['image_height']!=0) $foo->image_y = $avatarField->params['image_height'];
							$foo->Process($upload_dir);
							if ($foo->processed)
							{
								$foo->Clean();
								return $upload_path.$foo->file_dst_name;
							}
						}
					}
					return '';
				}
			}
		}

		return null;
	}

	public function getReturnUrl() {
		$app  = JFactory::getApplication();

		$input = $app->input;

		if($input->get('view','')=='login') {

			// Get Return URL from UserState
			$url=JFactory::getApplication()->getUserState('users.login.form.data.return');

			// Override if is set return parameter in request
			$method = $input->getMethod();
			if ($return = $input->get('return', '', 'BASE64')) //if ($return = $input->$method->get('return', '', 'BASE64'))
			{
				$url = base64_decode($return);

				if (!JUri::isInternal($url))
				{
					$url = '';
				}
			}

			// If empty set default URL for login page
			if (!isset($url) || empty($url))
			{
				$url = 'index.php?option=com_users&view=profile';
			}

		}
		
		// If empty set current URL like modules
		if (!isset($url) || empty($url))
		{
			$url = JURI::current();//'index.php?option=com_users&view=profile';
			$urlGet=JFactory::getApplication()->input->get->getArray();
			if(count($urlGet))
			{
				$url.='?';
				foreach( $urlGet as $k => $v)
				{
					$url.=$k.'='.$v.'&';
				}
				$url=substr($url, 0, -1);
			}
		}

		if($input->get('tmpl','')=='' && $input->get('format','html')=='html' && $input->get('task','')==''  && !($input->get('view','')=='social' && !$input->get('url','')!='')  ) JFactory::getApplication()->setUserState('users.socialconnect.redirect',$url);
		
		return $url;
	}
	
	
	
}

// ################################################################################################################################################################################
















class SocialConnectFacebook extends SocialConnect {
	var $name	=	null;
	var $api	=	null;
	var $gen	=	null;
	var $sync	=	null;
	var $config	=	null;

	public function __construct() {
		
		$config = JComponentHelper::getParams('com_jsn');
		
		$this->name='facebook';

		$this->api						=	new stdClass();
		$this->api->enabled				=	$config->get( 'facebook_enabled', 0 );
		$this->api->application_id		=	$config->get( 'facebook_application_id', null );
		$this->api->application_secret	=	$config->get( 'facebook_application_secret', null );

		$this->gen						=	new stdClass();
		$this->gen->fieldname			=	'facebook_id';
		$this->gen->session_id			=	'socialconnectfacebook';
		$this->gen->secret				=	md5( (string) $this->api->application_secret );
		
		$this->sync						=	new stdClass();
		$this->sync->fromfields			=	'first_name|*|last_name|*|middle_name';
		$this->sync->tofields			=	'firstname|*|lastname|*|secondname';

		$this->config					=	new stdClass();
		$this->config->type				=	$config->get( 'socialconnect_type', 'popup' );

	}

	static public function getInstance() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$cache		=	new SocialConnectFacebook();
		}

		return $cache;
	}

	public function getJS() {
		static $cache							=	null;

		if ( ! isset( $cache ) ) {

			if ( $this->api->enabled && $this->api->application_id && $this->api->application_secret ) {
				static $JS_loaded				=	0;

				if ( ! $JS_loaded++ ) {
					$perms						=	array( 'email' );

					$urlParams					=	array(	'response_type=code',
															'client_id=' . urlencode( $this->api->application_id ),
															'redirect_uri=' . urlencode( SocialConnect::getUrl( 'facebook', 'accesstoken' ) ),
															'scope=' . urlencode( implode( ',', $perms ) ),
															'state=' . urlencode( md5( uniqid( $this->api->application_secret ) ) )
														);

					if( $this->config->type == 'popup' ) $urlParams[] = 'display=popup';

					require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
					$user=JsnHelper::getUser();
					if($user->getValue($this->gen->fieldname)) return false;
					
					$lang = JFactory::getLanguage();
					$lang->load('com_jsn');
					if($user->id) $button_text=JText::sprintf('COM_JSN_LINKTO','Facebook');
					else $button_text=JText::sprintf('COM_JSN_LOGINWITH','Facebook');

					$return=$this->getReturnUrl();
					/*$urlGet=JFactory::getApplication()->input->get->getArray();
					if(count($urlGet))
					{
						$return.='?';
						foreach( $urlGet as $k => $v)
						{
							$return.=$k.'='.$v.'&';
						}
						$return=substr($return, 0, -1);
					}*/
					
					$script= "jQuery(document).bind('ready ajaxStop',function(){jQuery('.socialconnect:not(.facebook_done)').addClass('facebook_done').append('<div><button class=\"facebook_button facebook zocial\">".$button_text."</button></div>');";
					
					$script						.=	"jQuery( '.facebook_button' ).click(function(e){e.preventDefault();}).oauth".$this->config->type."({"
												.		"url: 'https://www.facebook.com/dialog/oauth?" . addslashes( implode( '&', $urlParams ) ) . "',"
												.		"name: 'oAuthFacebook',"
												.		"callback: function( success, error, oAuthWindow ) {"
												.			"if ( success == true ) {"
												.				"window.location = '" . addslashes( SocialConnect::getUrl('facebook').'&return='.base64_encode($return) ) . "';"
												.			"} else {"
												.				"/*window.location.reload();*/"
												.			"}"
												.		"}"
												.	"});";
					$script		.="});";

					$doc = JFactory::getDocument();
					$doc->addScriptDeclaration( $script );
					
				}

				$cache							=	true;
			} else {
				$cache							=	false;
			}
		}

		return $cache;
	}

	public function getConnectUser( $id = null, $user = null ) {
		static $cache							=	array();

		if ( $id === null ) {
			$id									=	'me';
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]							=	false;

			if ( $this->getConnectID() == $id ) {
				$id								=	'me';
			} else {
				if ( $id != 'me' ) {
					$id							=	urlencode( $id );
				}
			}

			$token								=	$this->getToken();

			if ( $token ) {
				$token							=	'&access_token=' . urlencode( $token );
			}

			$fields								=	array( 'id', 'name', 'email', 'picture.type(large)' );
			$mapping							=	explode( '|*|', $this->sync->fromfields	 );

			if ( $mapping ) foreach( $mapping as $field ) {
				if ( $field && ( ! in_array( $field, $fields ) ) ) {
					$fields[]					=	urlencode( $field );
				}
			}

			$request							=	SocialConnect::httpRequest( 'https://graph.facebook.com/' . $id . '?fields=' . implode( ',', $fields ) . $token );
			$resultsArray						=	(array) json_decode( $request['results'] );

			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
				$cache[$id]						=	new stdClass();
				$cache[$id]->id					=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );
				$cache[$id]->username			=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'username', null ) );

				if ( ! $cache[$id]->username ) {
					$cache[$id]->username		=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'name', null ) );
				}

				$cache[$id]->name				=	stripslashes( SocialConnect::getParam( $resultsArray, 'name', null ) );

				$picture						=	SocialConnect::getParam( $resultsArray, 'picture', null );

				if ( isset( $picture->data ) ) {
					$cache[$id]->avatar			=	stripslashes( SocialConnect::getParam( get_object_vars( $picture->data ), 'url', null ) );
				} elseif ( is_string( $picture ) ) {
					$cache[$id]->avatar			=	stripslashes( $picture );
				} else {
					$cache[$id]->avatar			=	null;
				}

				$cache[$id]->email				=	stripslashes( SocialConnect::getParam( $resultsArray, 'email', null ) );
				$cache[$id]->user				=	$resultsArray;

				$sessionArray					=	$this->getRawSession();

				if ( $id == 'me' ) {
					if ( ! $cache[$id]->id ) {
						$cache[$id]->id			=	stripslashes( SocialConnect::getParam( $sessionArray, 'id', null ) );
					}

					if ( ! $cache[$id]->username ) {
						$cache[$id]->username	=	JApplication::stringURLSafe( SocialConnect::getParam( $sessionArray, 'username', null ) );
					}

					if ( ! $cache[$id]->username ) {
						$cache[$id]->username	=	JApplication::stringURLSafe( SocialConnect::getParam( $sessionArray, 'name', null ) );
					}

					if ( ! $cache[$id]->name ) {
						$cache[$id]->name		=	stripslashes( SocialConnect::getParam( $sessionArray, 'name', null ) );
					}

					if ( ! $cache[$id]->email ) {
						$cache[$id]->email		=	stripslashes( SocialConnect::getParam( $sessionArray, 'email', null ) );
					}

					if ( ! $cache[$id]->user ) {
						$cache[$id]->user		=	$sessionArray;
					}
				}

				if ( ! $cache[$id]->email ) {
					$cache[$id]->email			=	$cache[$id]->id . '@mail.invalid';
				}
			} elseif ( $user ) {
				if ( $request['results'] ) {
					$resultError				=	SocialConnect::getParam( $resultsArray, 'error', null );

					if ( $resultError ) {
						if ( isset( $resultError->message ) ) {
							$user->_error		=	stripslashes( $resultError->message );
						}
					}
				} elseif ( $request['error'] ) {
					$user->_error				=	$request['error'];
				}
			}
		}

		return $cache[$id];
	}

	public function mapFields( &$user, $fbUser ) {
		if ( $this->sync->fromfields && $this->sync->tofields ) {
			$fromFields								=	explode( '|*|', $this->sync->fromfields );
			$toFields								=	explode( '|*|', $this->sync->tofields );

			for ( $i = 0, $n = count( $fromFields ); $i < $n; $i++ ) {
				$fromField							=	SocialConnect::getParam( $fromFields, $i, null );
				$toField							=	SocialConnect::getParam( $toFields, $i, null );

				if ( $toField && ( $fromField && array_key_exists( $fromField, $fbUser ) ) ) {
					$value							=	$fbUser[$fromField];

					if ( ( ! is_object( $value ) ) && ( ! is_array( $value ) ) ) {
						$value						=	stripslashes( SocialConnect::getParam( $fbUser, $fromField, null ) );
					} else {
						if ( is_object( $value ) && isset( $value->data ) ) {
							$value					=	$value->data;
						}

						if ( is_array( $value ) ) {
							$values					=	array();

							if ( $value ) foreach ( $value as $v ) {
								if ( isset( $v->name ) ) {
									$values[]		=	stripslashes( SocialConnect::getParam( get_object_vars( $v ), 'name', null ) );
								}
							}

							if ( $values ) {
								$value				=	$values;
							} else {
								$value				=	null;
							}
						} else {
							if ( isset( $value->name ) ) {
								$value				=	stripslashes( SocialConnect::getParam( get_object_vars( $value ), 'name', null ) );
							} else {
								$value				=	null;
							}
						}
					}

					switch ( $fromField ) {
						case 'birthday':
							$value					=	date( 'Y-m-d', strtotime( $value ) );
							break;
						case 'updated_time':
							$value					=	date( 'Y-m-d H:i:s', strtotime( $value ) );
							break;
					}

					if ( is_array( $value ) ) {
						$value						=	implode( ', ', $value );
					}

					$user->$toField					=	$value;
				}
			}
		}
	}

	public function accessToken() {
		$success							=	'false';
		$error								=	null;

		if ( $this->api->enabled && $this->api->application_id && $this->api->application_secret ) {
			$errorResponse					=	stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'error', null ) );

			if ( ! $errorResponse ) {
				$request					=	array(	'code=' . urlencode( stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'code', null ) ) ),
														'client_id=' . urlencode( $this->api->application_id ),
														'client_secret=' . urlencode( $this->api->application_secret ),
														'redirect_uri=' . urlencode( SocialConnect::getUrl( 'facebook', 'accesstoken' ) ),
														'grant_type=authorization_code'
													);

				$request					=	SocialConnect::httpRequest( 'https://graph.facebook.com/oauth/access_token', 'POST', implode( '&', $request ) );

				if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
					$resultsArray			=	array();

					//parse_str( $request['results'], $resultsArray );
					$resultsArray = (array) json_decode( $request['results'] );

					$accessToken			=	stripslashes( SocialConnect::getParam( $resultsArray, 'access_token', null ) );
					$request				=	SocialConnect::httpRequest( 'https://graph.facebook.com/me?access_token=' . $accessToken );
					$resultsArray			=	(array) json_decode( $request['results'] );

					if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
						$userId				=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );

						$this->setSession( $userId, $accessToken, $resultsArray );

						$success			=	'true';
					} else {
						if ( $request['results'] ) {
							$resultError	=	SocialConnect::getParam( $resultsArray, 'error', null );

							if ( $resultError ) {
								if ( isset( $resultError->message ) ) {
									$error	=	stripslashes( $resultError->message );
								}
							}
						}

						if ( ! $error ) {
							$error			=	$request['error'];
						}
					}
				} else {
					if ( $request['results'] ) {
						$resultsArray		=	(array) json_decode( $request['results'] );
						$resultError		=	SocialConnect::getParam( $resultsArray, 'error', null );

						if ( $resultError ) {
							if ( isset( $resultError->message ) ) {
								$error		=	stripslashes( $resultError->message );
							}
						}
					}

					if ( ! $error ) {
						$error				=	$request['error'];
					}
				}
			} else {
				$error						=	$errorResponse . ': ' . stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'error_description', null ) );
			}
		}

		if ($this->config->type == 'redirect' ) {
			$app=JFactory::getApplication();
			$app->redirect(SocialConnect::getUrl($this->name).'&return='.base64_encode(JFactory::getApplication()->getUserState('users.socialconnect.redirect')));
		}

		$js									=	"if(window.opener) {window.opener.oAuthSuccess = $success;"
											.	( $error ? "window.opener.oAuthError = '" . addslashes( $error ). "';" : null )
											.	"window.opener.oAuthClosed= true; window.close();} else window.location = '" . addslashes( SocialConnect::getUrl('facebook') ) . "'";

		echo '<script type="text/javascript">' . $js . '</script>';
	}
}



class SocialConnectTwitter extends SocialConnect {
	var $name	=	null;
	var $api	=	null;
	var $gen	=	null;
	var $sync	=	null;
	var $config	=	null;

	public function __construct() {
		$config = JComponentHelper::getParams('com_jsn');
		
		$this->name='twitter';

		$this->api					=	new stdClass();
		$this->api->enabled			=	$config->get( 'twitter_enabled', 0 );
		$this->api->consumer_key	=	$config->get( 'twitter_consumer_key', null );
		$this->api->consumer_secret	=	$config->get( 'twitter_consumer_secret', null );

		$this->gen					=	new stdClass();
		$this->gen->fieldname		=	'twitter_id';
		$this->gen->session_id		=	'socialconnecttwitter';
		$this->gen->secret			=	md5( (string) $this->api->consumer_secret );

		$this->sync					=	new stdClass();
		$this->sync->fromfields			=	'';
		$this->sync->tofields			=	'';

		$this->config					=	new stdClass();
		$this->config->type				=	$config->get( 'socialconnect_type', 'popup' );
	}

	static public function getInstance() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$cache		=	new SocialConnectTwitter();
		}

		return $cache;
	}

	public function getJS() {
		static $cache					=	null;

		if ( ! isset( $cache ) ) {
			if ( $this->api->enabled && $this->api->consumer_key && $this->api->consumer_secret ) {
				static $JS_loaded		=	0;

				if ( ! $JS_loaded++ ) {

					require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
					$user=JsnHelper::getUser();
					if($user->getValue($this->gen->fieldname)) return false;
					
					$lang = JFactory::getLanguage();
					$lang->load('com_jsn');
					if($user->id) $button_text=JText::sprintf('COM_JSN_LINKTO','Twitter');
					else $button_text=JText::sprintf('COM_JSN_LOGINWITH','Twitter');

					$return=$this->getReturnUrl();
					/*$urlGet=JFactory::getApplication()->input->get->getArray();
					if(count($urlGet))
					{
						$return.='?';
						foreach( $urlGet as $k => $v)
						{
							$return.=$k.'='.$v.'&';
						}
						$return=substr($return, 0, -1);
					}*/
					
					$script= "jQuery(document).bind('ready ajaxStop',function(){jQuery('.socialconnect:not(.twitter_done)').addClass('twitter_done').append('<div><button class=\"twitter_button twitter zocial\">".$button_text."</button></div>');";

					$script				.=	"jQuery( '.twitter_button' ).click(function(e){e.preventDefault();}).oauth".$this->config->type."({"
										.		"url: 'https://api.twitter.com/oauth/authenticate',"
										.		"name: 'oAuthTwitter',"
										.		"init: function( settings ) {"
										.			"var response = false;"
										.			"jQuery.ajax({"
										.				"url: '" . addslashes( SocialConnect::getUrl( 'twitter', 'requesttoken' ) ) . "',"
										.				"async: false"
										.			"}).done( function( data, textStatus, jqXHR ) {"
										.				"if ( data ) {"
										.					"var regex = /<tokenresponse>(.+)<\/tokenresponse>/;"
										.					"var match = regex.exec( data );"
										.					"if ( match != null ) {"
										.						"var json = jQuery.parseJSON( match[1] );"
										.						"if ( json.oauth_token ) {"
										.							"settings.url = 'https://api.twitter.com/oauth/authenticate' + '?oauth_token=' + json.oauth_token;"
										.							"response = true;"
										.						"} else {"
										.						"}"
										.					"} else {"
										.					"}"
										.				"} else {"
										.				"}"
										.			"}).fail( function( jqXHR, textStatus, errorThrown ) {"
										.			"});"
										.			"return response;"
										.		"},"
										.		"callback: function( success, error, oAuthWindow ) {"
										.			"if ( success == true ) {"
										.				"window.location = '" . addslashes( SocialConnect::getUrl('twitter').'&return='.base64_encode($return) ) . "';"
										.			"} else {"
										.				"window.location.reload();"
										.			"}"
										.		"}"
										.	"});";

					$script		.="});";

					$doc = JFactory::getDocument();
					$doc->addScriptDeclaration( $script );
				}

				$cache					=	true;
			} else {
				$cache					=	false;
			}
		}

		return $cache;
	}

	public function getConnectUser( $id = null, $user = null ) {
		static $cache							=	array();

		if ( $id === null ) {
			$id									=	$this->getConnectID();
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]							=	false;

			if ( $this->getConnectID() == $id ) {
				$isMe							=	true;
			} else{
				$isMe							=	false;
			}

			$sessionArray						=	$this->getRawSession();
			$method								=	'GET';
			$url								=	'https://api.twitter.com/1.1/users/show.json';
			$request							=	array(	'oauth_nonce' => md5( uniqid() ),
															'oauth_signature_method' => 'HMAC-SHA1',
															'oauth_timestamp' => time(),
															'oauth_consumer_key' => $this->api->consumer_key,
															'oauth_version' => '1.0',
															'oauth_token' => $this->getToken(),
															'user_id' => $id
														);
			ksort( $request );

			$baseRequest						=	array();

			foreach ( $request as $k => $v ) {
				$baseRequest[]					=	rawurlencode( $k ) . '=' . rawurlencode( $v );
			}

			$base								=	$method . '&' . rawurlencode( $url ) . '&' . rawurlencode( implode( '&', $baseRequest ) );
			$secret								=	rawurlencode( stripslashes( SocialConnect::getParam( $sessionArray, 'oauth_token_secret', null ) ) );

			$request['oauth_signature']			=	base64_encode( hash_hmac( 'SHA1', $base, rawurlencode( $this->api->consumer_secret ) . '&' . $secret, true ) );

			unset( $request['user_id'] );

			ksort( $request );

			$requestAuthorized					=	array();

			foreach ( $request as $k => $v ) {
				$requestAuthorized[]			=	rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
			}

			$request							=	SocialConnect::httpRequest( $url . '?user_id=' . urlencode( $id ), $method, null, array( 'Authorization: OAuth ' . implode( ', ', $requestAuthorized ), 'Expect:' ) );

			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
				$resultsArray					=	(array) json_decode( $request['results'] );

				$cache[$id]						=	new stdClass();
				$cache[$id]->id					=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );
				$cache[$id]->username			=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'screen_name', null ) );

				if ( ! $cache[$id]->username ) {
					$cache[$id]->username		=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'name', null ) );
				}

				$cache[$id]->name				=	stripslashes( SocialConnect::getParam( $resultsArray, 'name', null ) );

				if ( ! $cache[$id]->name ) {
					$cache[$id]->name			=	stripslashes( SocialConnect::getParam( $resultsArray, 'screen_name', null ) );
				}

				$cache[$id]->avatar				=	stripslashes( SocialConnect::getParam( $resultsArray, 'profile_image_url', null ) );
				$cache[$id]->email				=	$cache[$id]->id . '@mail.invalid';
				$cache[$id]->user				=	$resultsArray;

				if ( $isMe ) {
					if ( ! $cache[$id]->id ) {
						$cache[$id]->id			=	stripslashes( SocialConnect::getParam( $sessionArray, 'user_id', null ) );
					}

					if ( ! $cache[$id]->username ) {
						$cache[$id]->username	=	JApplication::stringURLSafe( SocialConnect::getParam( $sessionArray, 'screen_name', null ) );
					}

					if ( ! $cache[$id]->name ) {
						$cache[$id]->name		=	stripslashes( SocialConnect::getParam( $sessionArray, 'screen_name', null ) );
					}

					if ( ! $cache[$id]->user ) {
						$cache[$id]->user		=	$sessionArray;
					}
				}
			} elseif ( $user ) {
				if ( $request['error'] ) {
					$user->_error				=	$request['error'];
				} else {
					$resultsArray				=	(array) json_decode( $request['results'] );

					$error						=	stripslashes( SocialConnect::getParam( $resultsArray, 'error', null ) );

					if ( $error ) {
						$user->_error			=	$error;
					}
				}
			}
		}

		return $cache[$id];
	}

	public function mapFields( &$user, $twitterUser ) {
		if ( $this->sync->fromfields && $this->sync->tofields ) {
			$fromFields					=	explode( '|*|', $this->sync->fromfields );
			$toFields					=	explode( '|*|', $this->sync->tofields );

			for ( $i = 0, $n = count( $fromFields ); $i < $n; $i++ ) {
				$fromField				=	SocialConnect::getParam( $fromFields, $i, null );
				$toField				=	SocialConnect::getParam( $toFields, $i, null );

				if ( $toField && ( $fromField && array_key_exists( $fromField, $twitterUser ) ) ) {
					$user->$toField		=	stripslashes( SocialConnect::getParam( $twitterUser, $fromField, null ) );
				}
			}
		}
	}

	public function requestToken() {
		if ( $this->api->enabled && $this->api->consumer_key && $this->api->consumer_secret ) {
			$method								=	'POST';
			$url								=	'https://api.twitter.com/oauth/request_token';
			$request							=	array(	'oauth_nonce' => md5( uniqid() ),
															'oauth_callback' => SocialConnect::getUrl( 'twitter', 'accesstoken' ),
															'oauth_signature_method' => 'HMAC-SHA1',
															'oauth_timestamp' => time(),
															'oauth_consumer_key' => $this->api->consumer_key,
															'oauth_version' => '1.0'
														);

			ksort( $request );

			$baseRequest						=	array();

			foreach ( $request as $k => $v ) {
				$baseRequest[]					=	rawurlencode( $k ) . '=' . rawurlencode( $v );
			}

			$base								=	$method . '&' . rawurlencode( $url ) . '&' . rawurlencode( implode( '&', $baseRequest ) );

			$request['oauth_signature']			=	base64_encode( hash_hmac( 'SHA1', $base, rawurlencode( $this->api->consumer_secret ) . '&', true ) );

			ksort( $request );

			$requestAuthorized					=	array();

			foreach ( $request as $k => $v ) {
				$requestAuthorized[]			=	rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
			}

			$request							=	SocialConnect::httpRequest( $url, $method, null, array( 'Authorization: OAuth ' . implode( ', ', $requestAuthorized ), 'Expect:', 'Content-length: 0' ) );

			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
				$results						=	explode( '&', $request['results'] );
				$response						=	array();

				if ( $results ) foreach ( $results as $r ) {
					$resultsArray				=	explode( '=', $r );

					if ( $resultsArray ) {
						$key					=	( isset( $resultsArray[0] ) ? $resultsArray[0] : null );

						if ( $key ) {
							$value				=	( isset( $resultsArray[1] ) ? $resultsArray[1] : null );

							$response[$key]		=	$value;
						}
					}
				}

				header( 'HTTP/1.0 200' );

				echo '<tokenresponse>' . json_encode( $response ) . '</tokenresponse>';
			} else {
				header( 'HTTP/1.0 ' . $request['http_code'] );

				echo $request['results'];
			}
		} else {
			header( 'HTTP/1.0 403' );
		}
	}

	public function accessToken() {
		$success								=	'false';
		$error									=	null;

		if ( $this->api->enabled && $this->api->consumer_key && $this->api->consumer_secret ) {
			$method								=	'POST';
			$url								=	'https://api.twitter.com/oauth/access_token';
			$post								=	array( 'oauth_verifier' => stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'oauth_verifier', null ) ) );
			$request							=	array(	'oauth_nonce' => md5( uniqid() ),
															'oauth_signature_method' => 'HMAC-SHA1',
															'oauth_timestamp' => time(),
															'oauth_consumer_key' => $this->api->consumer_key,
															'oauth_version' => '1.0',
															'oauth_token' => stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'oauth_token', null ) )
														);

			ksort( $request );

			$baseRequest						=	array();

			foreach ( $request as $k => $v ) {
				$baseRequest[]					=	rawurlencode( $k ) . '=' . rawurlencode( $v );
			}

			$base								=	$method . '&' . rawurlencode( $url ) . '&' . rawurlencode( implode( '&', $baseRequest ) );

			$request['oauth_signature']			=	base64_encode( hash_hmac( 'SHA1', $base, rawurlencode( $this->api->consumer_secret ) . '&', true ) );

			ksort( $request );

			$requestAuthorized					=	array();

			foreach ( $request as $k => $v ) {
				$requestAuthorized[]			=	rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
			}

			$request							=	SocialConnect::httpRequest( $url, $method, $post, array( 'Authorization: OAuth ' . implode( ', ', $requestAuthorized ), 'Expect:' ) );

			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
				$results						=	explode( '&', $request['results'] );
				$response						=	array();

				if ( $results ) foreach ( $results as $r ) {
					$resultsArray				=	explode( '=', $r );

					if ( $resultsArray ) {
						$key					=	( isset( $resultsArray[0] ) ? $resultsArray[0] : null );

						if ( $key ) {
							$value				=	( isset( $resultsArray[1] ) ? $resultsArray[1] : null );

							$response[$key]		=	$value;
						}
					}
				}

				$userId							=	stripslashes( SocialConnect::getParam( $response, 'user_id', null ) );
				$accessToken					=	stripslashes( SocialConnect::getParam( $response, 'oauth_token', null ) );

				$this->setSession( $userId, $accessToken, $response );

				$success						=	'true';
			} else {
				$error							=	$request['error'];
			}
		}

		if ($this->config->type == 'redirect' ) {
			$app=JFactory::getApplication();
			$app->redirect(SocialConnect::getUrl($this->name).'&return='.base64_encode(JFactory::getApplication()->getUserState('users.socialconnect.redirect')));
		}

		$js										=	"window.opener.oAuthSuccess = $success;"
												.	( $error ? "window.opener.oAuthError = '" . addslashes( $error ). "';" : null )
												.	"window.opener.oAuthClosed= true; window.close();";

		echo '<script type="text/javascript">' . $js . '</script>';
	}
}



class SocialConnectGoogle extends SocialConnect {
	var $name	=	null;
	var $api	=	null;
	var $gen	=	null;
	var $sync	=	null;
	var $config	=	null;

	public function __construct() {
		$config = JComponentHelper::getParams('com_jsn');

		$this->name					=	'google';

		$this->api					=	new stdClass();
		$this->api->enabled			=	$config->get( 'google_enabled', 0 );
		$this->api->client_id		=	$config->get( 'google_client_id', null );
		$this->api->client_secret	=	$config->get( 'google_client_secret', null );
		$this->api->api_key			=	$config->get( 'google_api_key', null );

		$this->gen					=	new stdClass();
		$this->gen->fieldname		=	'google_id';
		$this->gen->session_id		=	'socialconnectgoogle';
		$this->gen->secret			=	md5( (string) $this->api->client_secret );

		$this->sync						=	new stdClass();
		$this->sync->fromfields			=	'';//'name/givenName|*|name/middleName|*|name/familyName';
		$this->sync->tofields			=	'';//'firstname|*|secondname|*|lastname';

		$this->config					=	new stdClass();
		$this->config->type				=	$config->get( 'socialconnect_type', 'popup' );
	}

	static public function getInstance() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$cache		=	new SocialConnectGoogle();
		}

		return $cache;
	}

	public function getJS() {
		if(!$this->api->enabled) return false;
		static $cache				=	null;

		if ( ! isset( $cache ) ) {

			if ( $this->api->enabled && $this->api->client_id && $this->api->client_secret && $this->api->api_key ) {
				static $JS_loaded	=	0;

				if ( ! $JS_loaded++ ) {

					$urlParams		=	array(	'response_type=code',
												'client_id=' . urlencode( $this->api->client_id ),
												'redirect_uri=' . urlencode( SocialConnect::getUrl( 'google', 'accesstoken' ) ),
												'scope=' . urlencode( 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email' )
											);

					require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
					$user=JsnHelper::getUser();
					if($user->getValue($this->gen->fieldname)) return false;

					$lang = JFactory::getLanguage();
					$lang->load('com_jsn');
					if($user->id) $button_text=JText::sprintf('COM_JSN_LINKTO','Google');
					else $button_text=JText::sprintf('COM_JSN_LOGINWITH','Google');

					$return=$this->getReturnUrl();
					/*$urlGet=JFactory::getApplication()->input->get->getArray();
					if(count($urlGet))
					{
						$return.='?';
						foreach( $urlGet as $k => $v)
						{
							$return.=$k.'='.$v.'&';
						}
						$return=substr($return, 0, -1);
					}*/

					$script= "jQuery(document).bind('ready ajaxStop',function(){jQuery('.socialconnect:not(.google_done)').addClass('google_done').append('<div><button class=\"google_button googleplus zocial\">".$button_text."</button></div>');";


					$script			.=	"jQuery( '.google_button' ).click(function(e){e.preventDefault();}).oauth".$this->config->type."({"
									.		"url: 'https://accounts.google.com/o/oauth2/auth?" . addslashes( implode( '&', $urlParams ) ) . "',"
									.		"name: 'oAuthGoogle',"
									.		"callback: function( success, error, oAuthWindow ) {"
									.			"if ( success == true ) {"
									.				"window.location = '" . addslashes( SocialConnect::getUrl('google').'&return='.base64_encode($return) ) . "';"
									.			"} else {"
									.				"window.location.reload();"
									.			"}"
									.		"}"
									.	"});";
					$script		.="});";

					$doc = JFactory::getDocument();
					$doc->addScriptDeclaration( $script );
				}

				$cache				=	true;
			} else {
				$cache				=	false;
			}
		}

		return $cache;
	}

	public function getConnectUser( $id = null, $user = null ) {
		static $cache									=	array();

		if ( $id === null ) {
			$id											=	'me';
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]									=	false;

			if ( $this->getConnectID() == $id ) {
				$id										=	'me';
			} else {
				if ( $id != 'me' ) {
					$id									=	urlencode( $id );
				}
			}

			$token										=	$this->getToken();

			if ( $token ) {
				$token									=	'&access_token=' . urlencode( $token );
			} else {
				$token									=	'&key=' . urlencode( $this->api->api_key );
			}

			$fields										=	array( 'id', 'email','picture' );
			$mapping									=	explode( '|*|', $this->sync->fromfields );
			foreach($mapping as &$v)
			{
				if(strpos($v,'/') > 0) $v=substr($v,0,strpos($v,'/'));
			}

			if ( $mapping ) foreach( $mapping as $field ) {
				if ( $field && ( ! in_array( $field, $fields ) ) ) {
					$fields[]							=	urlencode( $field );
				}
			}

			$ip_array=SocialConnect::getIParray();
			$request									=	SocialConnect::httpRequest( 'https://www.googleapis.com/userinfo/v2/' . $id . '?fields=' . urlencode( implode( ',', $fields ) ) . $token . '&userIp=' . urlencode( array_pop( $ip_array ) ) );
			//print_r($request);die();
			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
				$resultsArray							=	(array) json_decode( $request['results'] );
				$error									=	SocialConnect::getParam( $resultsArray, 'error', null );

				if ( $error ) {
					if ( $user && isset( $error->message ) ) {
						$user->_error					=	stripslashes( $error->message );
					}
				} else {
					$cache[$id]							=	new stdClass();
					$cache[$id]->id						=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );
					$cache[$id]->username				=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'name', null ) );

					if ( ! $cache[$id]->username ) {
						$cache[$id]->username			=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'name', null ) );
					}

					$cache[$id]->name					=	stripslashes( SocialConnect::getParam( $resultsArray, 'name', null ) );

					$image								=	SocialConnect::getParam( $resultsArray, 'picture', null );

					$cache[$id]->avatar					=	$image;//( isset( $image->url ) ? preg_replace( '/\?sz=\d+/', '', stripslashes( SocialConnect::getParam( get_object_vars( $image ), 'url', null ) ) ) : null );

					$emails								=	SocialConnect::getParam( $resultsArray, 'emails', null );

					$cache[$id]->email					=	SocialConnect::getParam( $resultsArray, 'email', null );;

					if ( $emails ) foreach ( $emails as $email ) {
						if ( isset( $email->value ) && isset( $email->primary ) && $email->primary ) {
							$cache[$id]->email			=	stripslashes( SocialConnect::getParam( get_object_vars( $email ), 'value', null ) );
						}
					}

					$cache[$id]->user					=	$resultsArray;

					$sessionArray						=	$this->getRawSession();

					if ( $id == 'me' ) {
						if ( ! $cache[$id]->id ) {
							$cache[$id]->id				=	stripslashes( SocialConnect::getParam( $sessionArray, 'id', null ) );
						}

						if ( ! $cache[$id]->username ) {
							$cache[$id]->username		=	JApplication::stringURLSafe( SocialConnect::getParam( $sessionArray, 'name', null ) );
						}

						if ( ! $cache[$id]->name ) {
							$cache[$id]->name			=	stripslashes( SocialConnect::getParam( $sessionArray, 'name', null ) );
						}

						if ( ! $cache[$id]->avatar ) {
							$cache[$id]->avatar			=	stripslashes( SocialConnect::getParam( $sessionArray, 'picture', null ) );
						}

						if ( ! $cache[$id]->email ) {
							$cache[$id]->email			=	stripslashes( SocialConnect::getParam( $sessionArray, 'email', null ) );
						}

						if ( ! $cache[$id]->user ) {
							$cache[$id]->user			=	$sessionArray;
						}
					}

					if ( ! $cache[$id]->email ) {
						$cache[$id]->email				=	$cache[$id]->id . '@mail.invalid';
					}
				}
			} elseif ( $user ) {
				$resultsArray							=	(array) json_decode( $request['results'] );
				$error									=	SocialConnect::getParam( $resultsArray, 'error', null );

				if ( $error ) {
					if ( $user && isset( $error->message ) ) {
						$user->_error					=	stripslashes( $error->message );
					}
				}
			}
		}

		return $cache[$id];
	}

	public function mapFields( &$user, $googleUser ) {
		if ( $this->sync->fromfields && $this->sync->tofields ) {
			$fromFields							=	explode( '|*|', $this->sync->fromfields );
			$toFields							=	explode( '|*|', $this->sync->tofields );

			for ( $i = 0, $n = count( $fromFields ); $i < $n; $i++ ) {
				$fromField						=	SocialConnect::getParam( $fromFields, $i, null );
				$toField						=	SocialConnect::getParam( $toFields, $i, null );
				$customFields					=	array(	'name/formatted', 'name/familyName', 'name/givenName', 'name/middleName',
															'name/honorificPrefix', 'name/honorificSuffix', 'organizations/primary', 'placesLived/primary'
														);

				if ( $toField && ( $fromField && ( array_key_exists( $fromField, $googleUser ) || in_array( $fromField, $customFields ) ) ) ) {
					$field						=	SocialConnect::getParam( $googleUser, $fromField, null );
					$value						=	null;

					switch ( $fromField ) {
						case 'name/formatted':
						case 'name/familyName':
						case 'name/givenName':
						case 'name/middleName':
						case 'name/honorificPrefix':
						case 'name/honorificSuffix':
							$field				=	SocialConnect::getParam( $googleUser, 'name', null );
							$name				=	str_replace( 'name/', '', $fromField );

							if ( isset( $field->$name ) ) {
								$value			=	stripslashes( SocialConnect::getParam( get_object_vars( $field ), $name, null ) );
							}
							break;
						case 'birthday':
							$value				=	date( 'Y-m-d', strtotime( $value ) );
							break;
						case 'image':
							if ( isset( $field->url ) ) {
								$value			=	stripslashes( SocialConnect::getParam( get_object_vars( $field ), 'url', null ) );
							}
							break;
						case 'urls':
							$value				=	array();

							if ( $field ) foreach ( $field as $url ) {
								if ( isset( $url->value ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $url ), 'value', null ) );
								}
							}
							break;
						case 'organizations/primary':
							$field				=	SocialConnect::getParam( $googleUser, 'organizations', null );

							if ( $field ) foreach ( $field as $organization ) {
								if ( isset( $organization->primary ) && $organization->primary ) {
									$job		=	array();

									if ( isset( $organization->name ) ) {
										$job[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $organization ), 'name', null ) );
									}

									if ( isset( $organization->title ) ) {
										$job[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $organization ), 'title', null ) );
									}

									if ( $job ) {
										$value	=	implode( ' - ', $job );
									}
								}
							}
							break;
						case 'organizations':
							$value				=	array();

							if ( $field ) foreach ( $field as $organization ) {
								$job			=	array();

								if ( isset( $organization->name ) ) {
									$job[]		=	stripslashes( SocialConnect::getParam( get_object_vars( $organization ), 'name', null ) );
								}

								if ( isset( $organization->title ) ) {
									$job[]		=	stripslashes( SocialConnect::getParam( get_object_vars( $organization ), 'title', null ) );
								}

								if ( $job ) {
									$value[]	=	implode( ' - ', $job );
								}
							}
							break;
						case 'placesLived/primary':
							$field				=	SocialConnect::getParam( $googleUser, 'placesLived', null );

							if ( $field ) foreach ( $field as $location ) {
								if ( isset( $location->value ) && isset( $location->primary ) && $location->primary ) {
									$value		=	stripslashes( SocialConnect::getParam( get_object_vars( $location ), 'value', null ) );
								}
							}
							break;
						case 'placeslived':
							$value				=	array();

							if ( $field ) foreach ( $field as $location ) {
								if ( isset( $location->value ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $location ), 'value', null ) );
								}
							}
							break;
						default:
							if ( is_string( $field ) ) {
								$value			=	stripslashes( $field );
							}
							break;
					}

					if ( is_array( $value ) ) {
						$value					=	implode( ', ', $value );
					}

					$user->$toField				=	$value;
				}
			}
		}
	}

	public function accessToken() {
		$success							=	'false';
		$error								=	null;

		if ( $this->api->enabled && $this->api->client_id && $this->api->client_secret && $this->api->api_key ) {
			$errorResponse					=	stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'error', null ) );

			if ( ! $errorResponse ) {
				$request					=	array(	'code=' . urlencode( stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'code', null ) ) ),
														'client_id=' . urlencode( $this->api->client_id ),
														'client_secret=' . urlencode( $this->api->client_secret ),
														'redirect_uri=' . urlencode( SocialConnect::getUrl( 'google', 'accesstoken' ) ),
														'grant_type=authorization_code'
													);

				$request					=	SocialConnect::httpRequest( 'https://accounts.google.com/o/oauth2/token', 'POST', implode( '&', $request ) );

				if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
					$resultsArray			=	(array) json_decode( $request['results'] );
					$resultError			=	stripslashes( SocialConnect::getParam( $resultsArray, 'error', null ) );

					if ( $resultError ) {
						$error				=	$resultError;
					} else {
						$accessToken		=	stripslashes( SocialConnect::getParam( $resultsArray, 'access_token', null ) );
						$request			=	SocialConnect::httpRequest( 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $accessToken );

						if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
							$resultsArray	=	(array) json_decode( $request['results'] );
							$resultError	=	stripslashes( SocialConnect::getParam( $resultsArray, 'error', null ) );

							if ( $resultError ) {
								$error		=	$resultError;
							} else {
								$userId		=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );

								$this->setSession( $userId, $accessToken, $resultsArray );

								$success	=	'true';
							}
						} else {
							$error			=	$request['error'];
						}
					}
				} else {
					$error					=	$request['error'];
				}
			} else {
				$error						=	$errorResponse;
			}
		}

		if ($this->config->type == 'redirect' ) {
			$app=JFactory::getApplication();
			$app->redirect(SocialConnect::getUrl($this->name).'&return='.base64_encode(JFactory::getApplication()->getUserState('users.socialconnect.redirect')));
		}

		$js									=	"window.opener.oAuthSuccess = $success;"
											.	( $error ? "window.opener.oAuthError = '" . addslashes( $error ). "';" : null )
											.	"window.opener.oAuthClosed= true; window.close();";

		echo '<script type="text/javascript">' . $js . '</script>';
	}
}



class SocialConnectLinkedIn extends SocialConnect {
	var $name	=	null;
	var $api	=	null;
	var $gen	=	null;
	var $sync	=	null;
	var $config	=	null;

	public function __construct() {
		$config = JComponentHelper::getParams('com_jsn');

		$this->name					=	'linkedin';

		$this->api					=	new stdClass();
		$this->api->enabled			=	$config->get( 'linkedin_enabled', 0 );
		$this->api->api_key		=	$config->get( 'linkedin_api_key', null );
		$this->api->secret_key	=	$config->get( 'linkedin_secret_key', null );

		$this->gen					=	new stdClass();
		$this->gen->fieldname		=	'linkedin_id';
		$this->gen->session_id		=	'socialconnectlinkedin';
		$this->gen->secret			=	md5( (string) $this->api->secret_key );

		$this->sync						=	new stdClass();
		$this->sync->fromfields			=	'first-name|*|maiden-name|*|last-name';
		$this->sync->tofields			=	'firstname|*|secondname|*|lastname';

		$this->config					=	new stdClass();
		$this->config->type				=	$config->get( 'socialconnect_type', 'popup' );
	}

	static public function getInstance() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$cache		=	new SocialConnectLinkedIn();
		}

		return $cache;
	}

	public function getJS() {
		if(!$this->api->enabled) return false;
		static $cache					=	null;

		if ( ! isset( $cache ) ) {

			if ( $this->api->enabled && $this->api->api_key  && $this->api->secret_key ) {
				static $JS_loaded		=	0;

				if ( ! $JS_loaded++ ) {
					$perms			=	array( 'r_liteprofile', 'r_emailaddress' );

					$urlParams		=	array(	'response_type=code',
												'client_id=' . urlencode( $this->api->api_key ),
												'redirect_uri=' . urlencode( SocialConnect::getUrl( 'linkedin', 'accesstoken' ) ),
												'scope=' . urlencode( implode( ' ', $perms ) ),
												'state=' . urlencode( md5( uniqid( $this->api->secret_key ) ) )
											);

					require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
					$user=JsnHelper::getUser();
					if($user->getValue($this->gen->fieldname)) return false;

					$lang = JFactory::getLanguage();
					$lang->load('com_jsn');
					if($user->id) $button_text=JText::sprintf('COM_JSN_LINKTO','LinkedIn');
					else $button_text=JText::sprintf('COM_JSN_LOGINWITH','LinkedIn');

					$return=$this->getReturnUrl();
					/*$urlGet=JFactory::getApplication()->input->get->getArray();
					if(count($urlGet))
					{
						$return.='?';
						foreach( $urlGet as $k => $v)
						{
							$return.=$k.'='.$v.'&';
						}
						$return=substr($return, 0, -1);
					}*/

					$script= "jQuery(document).bind('ready ajaxStop',function(){jQuery('.socialconnect:not(.linkedin_done)').addClass('linkedin_done').append('<div><button class=\"linkedin_button linkedin zocial\">".$button_text."</button></div>');";
					

					$script			.=	"jQuery( '.linkedin_button' ).click(function(e){e.preventDefault();}).oauth".$this->config->type."({"
									.		"url: 'https://www.linkedin.com/uas/oauth2/authorization?" . addslashes( implode( '&', $urlParams ) ) . "',"
									.		"name: 'oAuthLinkedin',"
									.		"callback: function( success, error, oAuthWindow ) {"
									.			"if ( success == true ) {"
									.				"window.location = '" . addslashes( SocialConnect::getUrl('linkedin').'&return='.base64_encode($return) ) . "';"
									.			"} else {"
									.				"window.location.reload();"
									.			"}"
									.		"}"
									.	"});";
					$script		.="});";
					
					$doc = JFactory::getDocument();
					$doc->addScriptDeclaration( $script );
				}

				$cache					=	true;
			} else {
				$cache					=	false;
			}
		}

		return $cache;
	}

	public function getConnectUser( $id = null, $user = null ) {
		static $cache							=	array();

		if ( $id === null ) {
			$id									=	'me';
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]							=	false;

			if ( $this->getConnectID() == $id ) {
				$id								=	'me';
			} else {
				if ( $id != 'me' ) {
					$id							=	urlencode( $id );
				}
			}

			$token								=	$this->getToken();

			/*if ( $token ) {
				$token							=	'?oauth2_access_token=' . urlencode( $token );
			}*/

			$fields								=	array( 'id', 'first-name', 'last-name', 'picture-url', 'email-address' );
			$mapping							=	explode( '|*|', $this->sync->fromfields );

			if ( $mapping ) foreach( $mapping as $field ) {
				if ( $field && ( ! in_array( $field, $fields ) ) ) {
					$fields[]					=	htmlspecialchars( urlencode( $field ) );
				}
			}

			$request				=	SocialConnect::httpRequest( 'https://api.linkedin.com/v2/me?projection=(id,localizedFirstName,localizedLastName,profilePicture(displayImage~:playableStreams))','GET',array(),array('Authorization: Bearer '.$token) );
			$request2				=	SocialConnect::httpRequest( 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))','GET',array(),array('Authorization: Bearer '.$token) );

			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {

				$resultsArray		=	(array) json_decode( $request['results'] );
				$resultsArray2		=	(array) json_decode( $request2['results'] );
				/* Name */
				$resultsArray['first-name'] = stripslashes( SocialConnect::getParam( $resultsArray, 'localizedFirstName', null ) );
				$resultsArray['last-name'] = stripslashes( SocialConnect::getParam( $resultsArray, 'localizedLastName', null ) );
				$resultsArray['formatted-name'] = $resultsArray['first-name']. ' ' . $resultsArray['last-name'];

				/* Image */
				$resultsArray['profilePicture'] = SocialConnect::getParam($resultsArray['profilePicture'],'displayImage~'); 
				$resultsArray['picture-url'] = stripslashes( SocialConnect::getParam( $resultsArray['profilePicture']->elements[0]->identifiers[0], 'identifier', null ));

				/* Email */
				$resultsArray2 = SocialConnect::getParam($resultsArray2['elements'][0],'handle~');
				$resultsArray['email-address'] = stripslashes( SocialConnect::getParam( $resultsArray2, 'emailAddress', null ));

				$cache[$id]						=	new stdClass();
				$cache[$id]->id					=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );
				$cache[$id]->name				=	stripslashes( SocialConnect::getParam( $resultsArray, 'formatted-name', null ) );
				$cache[$id]->username			=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'formatted-name', null ) );
				$cache[$id]->avatar				=	stripslashes( SocialConnect::getParam( $resultsArray, 'picture-url', null ) );
				$cache[$id]->email				=	stripslashes( SocialConnect::getParam( $resultsArray, 'email-address', null ) );
				$cache[$id]->user				=	$resultsArray;

				$sessionArray					=	$this->getRawSession();

				if ( $id == 'me' ) {
					if ( ! $cache[$id]->id ) {
						$cache[$id]->id			=	stripslashes( SocialConnect::getParam( $sessionArray, 'id', null ) );
					}

					if ( ! $cache[$id]->name ) {
						$cache[$id]->name		=	stripslashes( SocialConnect::getParam( $sessionArray, 'formatted-name', null ) );
					}

					if ( ! $cache[$id]->username ) {
						$cache[$id]->username	=	JApplication::stringURLSafe( SocialConnect::getParam( $sessionArray, 'formatted-name', null ) );
					}

					if ( ! $cache[$id]->avatar ) {
						$cache[$id]->avatar		=	stripslashes( SocialConnect::getParam( $sessionArray, 'picture-url', null ) );
					}

					if ( ! $cache[$id]->email ) {
						$cache[$id]->email		=	stripslashes( SocialConnect::getParam( $sessionArray, 'email-address', null ) );
					}

					if ( ! $cache[$id]->user ) {
						$cache[$id]->user		=	$sessionArray;
					}
				}

				if ( ! $cache[$id]->email ) {
					$cache[$id]->email			=	$cache[$id]->id . '@mail.invalid';
				}
			} elseif ( $user ) {
				if ( $request['error'] ) {
					$user->_error				=	$request['error'];
				} else {
					preg_match( '%<message>(.+)</message>%i', $request['results'], $error );

					if ( isset( $error[1] ) ) {
						$user->_error			=	trim( strip_tags( $error[1] ) );
					}
				}
			}
		}

		return $cache[$id];
	}

	public function mapFields( &$user, $linkedinUser ) {
		if ( $this->sync->fromfields && $this->sync->tofields ) {
			$fromFields							=	explode( '|*|', $this->sync->fromfields );
			$toFields							=	explode( '|*|', $this->sync->tofields );

			for ( $i = 0, $n = count( $fromFields ); $i < $n; $i++ ) {
				$fromField						=	SocialConnect::getParam( $fromFields, $i, null );
				$toField						=	SocialConnect::getParam( $toFields, $i, null );

				if ( $toField && ( $fromField && array_key_exists( $fromField, $linkedinUser ) ) ) {
					$field						=	SocialConnect::getParam( $linkedinUser, $fromField, null );
					$value						=	null;

					switch ( $fromField ) {
						case 'location':
							if ( isset( $field->name ) ) {
								$value			=	stripslashes( SocialConnect::getParam( get_object_vars( $field ), 'name', null ) );
							}
							break;
						case 'positions':
							$value				=	array();

							if ( isset( $field->position ) ) foreach ( $field->position as $position ) {
								$job			=	array();

								if ( isset( $position->company->name ) ) {
									$job[]		=	stripslashes( SocialConnect::getParam( get_object_vars( $position->company ), 'name', null ) );
								}

								if ( isset( $position->title ) ) {
									$job[]		=	stripslashes( SocialConnect::getParam( get_object_vars( $position ), 'title', null ) );
								}

								if ( $job ) {
									$value[]	=	implode( ' - ', $job );
								}
							}
							break;
						case 'publications':
							$value				=	array();

							if ( $field ) foreach ( $field as $publication ) {
								if ( isset( $publication->title ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $publication ), 'title', null ) );
								}
							}
							break;
						case 'patents':
							$value				=	array();

							if ( isset( $field->patent ) ) foreach ( $field->patent as $patent ) {
								if ( isset( $patent->title ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $patent ), 'title', null ) );
								}
							}
							break;
						case 'languages':
							$value				=	array();

							if ( isset( $field->language ) ) foreach ( $field->language as $language ) {
								if ( isset( $language->language->name ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $language->language ), 'name', null ) );
								}
							}
							break;
						case 'skills':
							$value				=	array();

							if ( isset( $field->skill ) ) foreach ( $field->skill as $skill ) {
								if ( isset( $skill->skill->name ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $skill->skill ), 'name', null ) );
								}
							}
							break;
						case 'certifications':
							$value				=	array();

							if ( isset( $field->certification ) ) foreach ( $field->certification as $certification ) {
								if ( isset( $certification->name ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $certification ), 'name', null ) );
								}
							}
							break;
						case 'educations':
							$value				=	array();

							if ( isset( $field->education ) ) foreach ( $field->education as $education ) {
								if ( isset( $education->{'school-name'} ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $education ), 'school-name', null ) );
								}
							}
							break;
						case 'courses':
							$value				=	array();

							if ( isset( $field->course ) ) foreach ( $field->course as $course ) {
								if ( isset( $course->name ) ) {
									$value[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $course ), 'name', null ) );
								}
							}
							break;
						case 'date-of-birth':
							$year				=	stripslashes( SocialConnect::getParam( get_object_vars( $field ), 'year', null ) );
							$month				=	stripslashes( SocialConnect::getParam( get_object_vars( $field ), 'month', null ) );
							$day				=	stripslashes( SocialConnect::getParam( get_object_vars( $field ), 'day', null ) );

							if ( $year && $month && $day ) {
								$value			=	date( 'Y-m-d', strtotime( $year . '-' . $month . '-' . $day ) );
							}
							break;
						case 'member-url-resources':
							$value				=	array();

							if ( isset( $field->{'member-url'} ) ) foreach ( $field->{'member-url'} as $url ) {
								$website		=	array();

								if ( isset( $url->name ) ) {
									$website[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $url ), 'name', null ) );
								}

								if ( isset( $url->url ) ) {
									$website[]	=	stripslashes( SocialConnect::getParam( get_object_vars( $url ), 'url', null ) );
								}

								if ( $website ) {
									$value[]	=	implode( ' - ', $website );
								}
							}
							break;
						case 'phone-numbers':
							$value				=	array();

							if ( isset( $field->{'phone-number'} ) )  {
								$value[]		=	stripslashes( SocialConnect::getParam( get_object_vars( $field->{'phone-number'} ), 'phone-number', null ) );
							}
							break;
						default:
							if ( is_string( $field ) ) {
								$value			=	stripslashes( $field );
							}
							break;
					}

					if ( is_array( $value ) ) {
						$value					=	implode( ', ', $value );
					}

					$user->$toField				=	$value;
				}
			}
		}
	}

	public function accessToken() {
		$success							=	'false';
		$error								=	null;

		if ( $this->api->enabled && $this->api->api_key  && $this->api->secret_key ) {
			$errorResponse					=	stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'error', null ) );

			if ( ! $errorResponse ) {
				$request					=	array(	'code=' . urlencode( stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'code', null) ) ),
														'client_id=' . urlencode( $this->api->api_key ),
														'client_secret=' . urlencode( $this->api->secret_key ),
														'redirect_uri=' . urlencode( SocialConnect::getUrl( 'linkedin', 'accesstoken' ) ),
														'grant_type=authorization_code'
													);

				$request					=	SocialConnect::httpRequest( 'https://www.linkedin.com/uas/oauth2/accessToken', 'POST', implode( '&', $request ) );

				if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
					$resultsArray			=	(array) json_decode( $request['results'] );
					$accessToken			=	stripslashes( SocialConnect::getParam( $resultsArray, 'access_token', null ) );
					
					$request				=	SocialConnect::httpRequest( 'https://api.linkedin.com/v2/me?projection=(id,localizedFirstName,localizedLastName,profilePicture(displayImage~:playableStreams))','GET',array(),array('Authorization: Bearer '.$accessToken) );
					$request2				=	SocialConnect::httpRequest( 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))','GET',array(),array('Authorization: Bearer '.$accessToken) );
					
					if ( ( $request['http_code'] == 200 && $request2['http_code'] == 200 ) && ( ! $request['error'] ) ) {

						$resultsArray		=	(array) json_decode( $request['results'] );
						$resultsArray2		=	(array) json_decode( $request2['results'] );

						$userId				=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );
						
						/* Name */
						$resultsArray['first-name'] = stripslashes( SocialConnect::getParam( $resultsArray, 'localizedFirstName', null ) );
						$resultsArray['last-name'] = stripslashes( SocialConnect::getParam( $resultsArray, 'localizedLastName', null ) );
						$resultsArray['formatted-name'] = $resultsArray['first-name']. ' ' . $resultsArray['last-name'];

						/* Image */
						$resultsArray['profilePicture'] = SocialConnect::getParam($resultsArray['profilePicture'],'displayImage~'); 
						$resultsArray['picture-url'] = stripslashes( SocialConnect::getParam( $resultsArray['profilePicture']->elements[0]->identifiers[0], 'identifier', null ));

						/* Email */
						$resultsArray2 = SocialConnect::getParam($resultsArray2['elements'][0],'handle~');
						$resultsArray['email-address'] = stripslashes( SocialConnect::getParam( $resultsArray2, 'emailAddress', null ));
						
						$this->setSession( $userId, $accessToken, $resultsArray );

						$success			=	'true';
					} else {
						if ( $request['results'] ) {
							preg_match( '%<message>(.+)</message>%i', $request['results'], $errors );

							if ( isset( $errors[1] ) ) {
								$error		=	trim( strip_tags( $errors[1] ) );
							}
						} elseif ( $request['error'] ) {
							$error			=	$request['error'];
						}
					}
				} else {
					$error					=	$request['error'];
				}
			} else {
				$error						=	$errorResponse . ': ' . stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'error_description', null ) );
			}
		}

		if ($this->config->type == 'redirect' ) {
			$app=JFactory::getApplication();
			$app->redirect(SocialConnect::getUrl($this->name).'&return='.base64_encode(JFactory::getApplication()->getUserState('users.socialconnect.redirect')));
		}

		$js									=	"window.opener.oAuthSuccess = $success;"
											.	( $error ? "window.opener.oAuthError = '" . addslashes( $error ). "';" : null )
											.	"window.opener.oAuthClosed= true; window.close();";

		echo '<script type="text/javascript">' . $js . '</script>';
	}
	
	public function profileUrlFix($id) {
		$sessionArray=$this->getRawSession();
		$publicURL = SocialConnect::getParam( $sessionArray, 'public-profile-url', null );
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.params')->from('#__jsn_users AS a')->where('a.id = '. (int) $id);
		$db->setQuery( $query );
		$params=$db->loadResult();
		$params=json_decode($params);
		$params->linkedin_url=$publicURL;
		$params=json_encode($params);
		
		$query = $db->getQuery(true);
		$query->update("#__jsn_users");
		$query->set($db->quoteName('params').' = '.$db->quote($params));
		$query->where('id = '. $id);
		$db->setQuery($query);
		$db->execute();
	}
}




class SocialConnectInstagram extends SocialConnect {
	var $name	=	null;
	var $api	=	null;
	var $gen	=	null;
	var $sync	=	null;
	var $config	=	null;

	public function __construct() {
		
		$config = JComponentHelper::getParams('com_jsn');
		
		$this->name='instagram';

		$this->api						=	new stdClass();
		$this->api->enabled				=	$config->get( 'instagram_enabled', 0 );
		$this->api->application_id		=	$config->get( 'instagram_application_id', null );
		$this->api->application_secret	=	$config->get( 'instagram_application_secret', null );

		$this->gen						=	new stdClass();
		$this->gen->fieldname			=	'instagram_id';
		$this->gen->session_id			=	'socialconnectinstagram';
		$this->gen->secret				=	md5( (string) $this->api->application_secret );
		
		$this->sync						=	new stdClass();
		$this->sync->fromfields			=	'first_name|*|last_name|*|middle_name';
		$this->sync->tofields			=	'firstname|*|lastname|*|secondname';

		$this->config					=	new stdClass();
		$this->config->type				=	'redirect'; //$config->get( 'socialconnect_type', 'popup' );

	}

	static public function getInstance() {
		static $cache	=	null;

		if ( ! isset( $cache ) ) {
			$cache		=	new SocialConnectInstagram();
		}

		return $cache;
	}

	public function getJS() {
		static $cache							=	null;

		if ( ! isset( $cache ) ) {

			if ( $this->api->enabled && $this->api->application_id && $this->api->application_secret ) {
				static $JS_loaded				=	0;

				if ( ! $JS_loaded++ ) {
					$perms						=	array( 'email' );

					$urlParams					=	array(	'response_type=code',
															'client_id=' . urlencode( $this->api->application_id ),
															'redirect_uri=' . urlencode( SocialConnect::getUrl( 'instagram', 'accesstoken' ) )//,
															//'scope=' . urlencode( implode( ',', $perms ) ),
															//'state=' . urlencode( md5( uniqid( $this->api->application_secret ) ) )
														);

					if( $this->config->type == 'popup' ) $urlParams[] = 'display=popup';

					require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
					$user=JsnHelper::getUser();
					if($user->getValue($this->gen->fieldname)) return false;
					
					$lang = JFactory::getLanguage();
					$lang->load('com_jsn');
					if($user->id) $button_text=JText::sprintf('COM_JSN_LINKTO','Instagram');
					else $button_text=JText::sprintf('COM_JSN_LOGINWITH','Instagram');

					$return=$this->getReturnUrl();
					/*$urlGet=JFactory::getApplication()->input->get->getArray();
					if(count($urlGet))
					{
						$return.='?';
						foreach( $urlGet as $k => $v)
						{
							$return.=$k.'='.$v.'&';
						}
						$return=substr($return, 0, -1);
					}*/
					
					$script= "jQuery(document).bind('ready ajaxStop',function(){jQuery('.socialconnect:not(.instagram_done)').addClass('instagram_done').append('<div><button class=\"instagram_button instagram zocial\">".$button_text."</button></div>');";
					
					$script						.=	"jQuery( '.instagram_button' ).click(function(e){e.preventDefault();}).oauth".$this->config->type."({"
												.		"url: 'https://api.instagram.com/oauth/authorize/?" . addslashes( implode( '&', $urlParams ) ) . "',"
												.		"name: 'oAuthInstagram',"
												.		"callback: function( success, error, oAuthWindow ) {"
												.			"if ( success == true ) {"
												.				"window.location = '" . addslashes( SocialConnect::getUrl('instagram').'&return='.base64_encode($return) ) . "';"
												.			"} else {"
												.				"/*window.location.reload();*/"
												.			"}"
												.		"}"
												.	"});";
					$script		.="});";

					$doc = JFactory::getDocument();
					$doc->addScriptDeclaration( $script );
					
				}

				$cache							=	true;
			} else {
				$cache							=	false;
			}
		}

		return $cache;
	}

	public function getConnectUser( $id = null, $user = null ) {
		static $cache							=	array();

		if ( $id === null ) {
			$id									=	'self';
		}

		if ( ! isset( $cache[$id] ) ) {
			$cache[$id]							=	false;

			if ( $this->getConnectID() == $id ) {
				$id								=	'self';
			} else {
				if ( $id != 'self' ) {
					$id							=	urlencode( $id );
				}
			}

			$token								=	$this->getToken();

			if ( $token ) {
				$token							=	'?access_token=' . urlencode( $token );
			}

			$fields								=	array( 'id', 'full_name', 'email', 'profile_picture' );
			$mapping							=	explode( '|*|', $this->sync->fromfields	 );

			if ( $mapping ) foreach( $mapping as $field ) {
				if ( $field && ( ! in_array( $field, $fields ) ) ) {
					$fields[]					=	urlencode( $field );
				}
			}

			$request							=	SocialConnect::httpRequest( 'https://api.instagram.com/v1/users/' . $id . '/' . $token );
			$resultsArray						=	(array) json_decode( $request['results'] );
			$resultsArray			=	$resultsArray['data'];

			if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
				$cache[$id]						=	new stdClass();
				$cache[$id]->id					=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );
				$cache[$id]->username			=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'username', null ) );

				if ( ! $cache[$id]->username ) {
					$cache[$id]->username		=	JApplication::stringURLSafe( SocialConnect::getParam( $resultsArray, 'full_name', null ) );
				}

				$cache[$id]->name				=	stripslashes( SocialConnect::getParam( $resultsArray, 'full_name', null ) );
				if ( ! $cache[$id]->name ) {
					$cache[$id]->name		=	SocialConnect::getParam( $resultsArray, 'username', null );
				}

				$picture						=	SocialConnect::getParam( $resultsArray, 'profile_picture', null );

				if ( isset( $picture->data ) ) {
					$cache[$id]->avatar			=	stripslashes( SocialConnect::getParam( get_object_vars( $picture->data ), 'url', null ) );
				} elseif ( is_string( $picture ) ) {
					$cache[$id]->avatar			=	stripslashes( $picture );
				} else {
					$cache[$id]->avatar			=	null;
				}

				$cache[$id]->email				=	stripslashes( SocialConnect::getParam( $resultsArray, 'email', null ) );
				$cache[$id]->user				=	$resultsArray;

				$sessionArray					=	$this->getRawSession();

				if ( $id == 'self' ) {
					if ( ! $cache[$id]->id ) {
						$cache[$id]->id			=	stripslashes( SocialConnect::getParam( $sessionArray, 'id', null ) );
					}

					if ( ! $cache[$id]->username ) {
						$cache[$id]->username	=	JApplication::stringURLSafe( SocialConnect::getParam( $sessionArray, 'username', null ) );
					}

					if ( ! $cache[$id]->username ) {
						$cache[$id]->username	=	JApplication::stringURLSafe( SocialConnect::getParam( $sessionArray, 'full_name', null ) );
					}

					if ( ! $cache[$id]->name ) {
						$cache[$id]->name		=	stripslashes( SocialConnect::getParam( $sessionArray, 'full_name', null ) );
					}

					if ( ! $cache[$id]->email ) {
						$cache[$id]->email		=	stripslashes( SocialConnect::getParam( $sessionArray, 'email', null ) );
					}

					if ( ! $cache[$id]->user ) {
						$cache[$id]->user		=	$sessionArray;
					}
				}

				if ( ! $cache[$id]->email ) {
					$cache[$id]->email			=	$cache[$id]->id . '@mail.invalid';
				}
			} elseif ( $user ) {
				if ( $request['results'] ) {
					$resultError				=	SocialConnect::getParam( $resultsArray, 'error', null );

					if ( $resultError ) {
						if ( isset( $resultError->message ) ) {
							$user->_error		=	stripslashes( $resultError->message );
						}
					}
				} elseif ( $request['error'] ) {
					$user->_error				=	$request['error'];
				}
			}
		}

		return $cache[$id];
	}

	public function mapFields( &$user, $fbUser ) {
		if ( $this->sync->fromfields && $this->sync->tofields ) {
			$fromFields								=	explode( '|*|', $this->sync->fromfields );
			$toFields								=	explode( '|*|', $this->sync->tofields );

			for ( $i = 0, $n = count( $fromFields ); $i < $n; $i++ ) {
				$fromField							=	SocialConnect::getParam( $fromFields, $i, null );
				$toField							=	SocialConnect::getParam( $toFields, $i, null );

				if ( $toField && ( $fromField && array_key_exists( $fromField, $fbUser ) ) ) {
					$value							=	$fbUser[$fromField];

					if ( ( ! is_object( $value ) ) && ( ! is_array( $value ) ) ) {
						$value						=	stripslashes( SocialConnect::getParam( $fbUser, $fromField, null ) );
					} else {
						if ( is_object( $value ) && isset( $value->data ) ) {
							$value					=	$value->data;
						}

						if ( is_array( $value ) ) {
							$values					=	array();

							if ( $value ) foreach ( $value as $v ) {
								if ( isset( $v->name ) ) {
									$values[]		=	stripslashes( SocialConnect::getParam( get_object_vars( $v ), 'name', null ) );
								}
							}

							if ( $values ) {
								$value				=	$values;
							} else {
								$value				=	null;
							}
						} else {
							if ( isset( $value->name ) ) {
								$value				=	stripslashes( SocialConnect::getParam( get_object_vars( $value ), 'name', null ) );
							} else {
								$value				=	null;
							}
						}
					}

					switch ( $fromField ) {
						case 'birthday':
							$value					=	date( 'Y-m-d', strtotime( $value ) );
							break;
						case 'updated_time':
							$value					=	date( 'Y-m-d H:i:s', strtotime( $value ) );
							break;
					}

					if ( is_array( $value ) ) {
						$value						=	implode( ', ', $value );
					}

					$user->$toField					=	$value;
				}
			}
		}
	}

	public function accessToken() {
		$success							=	'false';
		$error								=	null;

		if ( $this->api->enabled && $this->api->application_id && $this->api->application_secret ) {
			$errorResponse					=	stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'error', null ) );

			if ( ! $errorResponse ) {
				$request					=	array(	'code=' . urlencode( stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'code', null ) ) ),
														'client_id=' . urlencode( $this->api->application_id ),
														'client_secret=' . urlencode( $this->api->application_secret ),
														'redirect_uri=' . urlencode( SocialConnect::getUrl( 'instagram', 'accesstoken' ) ),
														'grant_type=authorization_code'
													);

				$request					=	SocialConnect::httpRequest( 'https://api.instagram.com/oauth/access_token', 'POST', implode( '&', $request ) );

				if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
					//$resultsArray			=	array();

					$resultsArray = (array) json_decode( $request['results']);

					$accessToken			=	stripslashes( SocialConnect::getParam( $resultsArray, 'access_token', null ) );
					
					$request				=	SocialConnect::httpRequest( 'https://api.instagram.com/v1/users/self/?access_token=' . $accessToken );
					$resultsArray			=	(array) json_decode( $request['results'] );
					$resultsArray			=	$resultsArray['data'];
					
					if ( ( $request['http_code'] == 200 ) && ( ! $request['error'] ) ) {
						$userId				=	stripslashes( SocialConnect::getParam( $resultsArray, 'id', null ) );

						$this->setSession( $userId, $accessToken, $resultsArray );

						$success			=	'true';
					} else {
						if ( $request['results'] ) {
							$resultError	=	SocialConnect::getParam( $resultsArray, 'error', null );

							if ( $resultError ) {
								if ( isset( $resultError->message ) ) {
									$error	=	stripslashes( $resultError->message );
								}
							}
						}

						if ( ! $error ) {
							$error			=	$request['error'];
						}
					}
				} else {
					if ( $request['results'] ) {
						$resultsArray		=	(array) json_decode( $request['results'] );
						$resultError		=	SocialConnect::getParam( $resultsArray, 'error', null );

						if ( $resultError ) {
							if ( isset( $resultError->message ) ) {
								$error		=	stripslashes( $resultError->message );
							}
						}
					}

					if ( ! $error ) {
						$error				=	$request['error'];
					}
				}
			} else {
				$error						=	$errorResponse . ': ' . stripslashes( SocialConnect::getParam( JFactory::getApplication()->input->get->getArray(), 'error_description', null ) );
			}
		}

		if ($this->config->type == 'redirect' ) {
			$app=JFactory::getApplication();
			$app->redirect(SocialConnect::getUrl($this->name).'&return='.base64_encode(JFactory::getApplication()->getUserState('users.socialconnect.redirect')));
		}

		$js									=	"if(window.opener) {window.opener.oAuthSuccess = $success;"
											.	( $error ? "window.opener.oAuthError = '" . addslashes( $error ). "';" : null )
											.	"window.opener.oAuthClosed= true; window.close();} else window.location = '" . addslashes( SocialConnect::getUrl('instagram') ) . "'";

		echo '<script type="text/javascript">' . $js . '</script>';
	}

	public function profileUrlFix($id) {
		$sessionArray=$this->getRawSession();
		$publicURL = SocialConnect::getParam( $sessionArray, 'username', null );
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.params')->from('#__jsn_users AS a')->where('a.id = '. (int) $id);
		$db->setQuery( $query );
		$params=$db->loadResult();
		$params=json_decode($params);
		$params->instagram_url=$publicURL;
		$params=json_encode($params);
		
		$query = $db->getQuery(true);
		$query->update("#__jsn_users");
		$query->set($db->quoteName('params').' = '.$db->quote($params));
		$query->where('id = '. $id);
		$db->setQuery($query);
		$db->execute();
	}
}

?>