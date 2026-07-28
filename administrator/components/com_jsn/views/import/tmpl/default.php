<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


if(!JFactory::getUser()->authorise('core.admin.import', 'com_jsn')) {
	die('No access');
}

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

$app		= JFactory::getApplication();
$user		= JFactory::getUser();
$userId		= $user->get('id');

if (!empty( $this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;

UserImportAdmin::getInstance()->init();

class UserImportAdmin
{	
	//private $filecontent;
	private $csv;
	private $j_fields = array('id','name','username','email','password','password_clear','block','sendEmail','registerDate','lastvisitDate','activation','params','lastResetTime','resetCount','otpKey','otep','requireReset','groups','facebook_id','twitter_id','google_id','linkedin_id','instagram_id');
	private $jsn_fields;
	private $jsn_fields_multiple;
	//private $fields;
	public function __construct()
	{
			$this->filecontent='';
	}
	
	public static function getInstance()
	{
		static $cache=null;
		if (!isset($cache))
		{
			$cache=new UserImportAdmin();
		}
		return $cache;
	}
	
	public function init()
	{
		if(JFactory::getApplication()->input->getVar('goimport',false))
		{
			if(isset($_FILES['filecsv']) && !empty($_FILES['filecsv']['name']))
			{
				//require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');
				require_once(JPATH_SITE.'/components/com_jsn/helpers/parsecsv.lib.php');
				$this->csv=new parseCSV();
				/* TODO ENCODING */
				$content = file_get_contents($_FILES['filecsv']['tmp_name']);
				$firstline = strtok($content, "\n\r");
				$firstline = preg_replace('/[^A-Za-z0-9\-,;_\r\n"]/', '', $firstline);
				//$content=$firstline."\n".preg_replace('/^.+[\n\r]/', '', $content);
				$this->csv->auto($content);
				$this->check();
			}
			else
			{
				$app=JFactory::getApplication();
				$app->enqueueMessage('No file selected','error');
				$this->getForm();
			}
		}
		else
		{
			$this->getForm();
		}
	}
	
	private function getForm()
	{
		$plugin = JPluginHelper::getPlugin('user','joomla');
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->select('extension_id')->from('#__extensions')->where('name = '.$db->quote('plg_user_joomla'));
		$db->setQuery($query);
		$pluginId = $db->loadResult();
		$params = new JRegistry($plugin->params);
		echo('
		<div class="page-header"><h2>Import User from CSV</h2></div>
		<div class="alert alert-warning">
			<b>RULES AND NOTE</b>
			<ul>
				<li>Fields that should never miss: <b>email</b>, and </b>name</b> or <b>firstname</b></li>
				<li>Import only from <b>CSV files</b> (Comma Separated Values)</li>
				<li>The first row of CSV file determines the columns (<b>alias of field</b>)</li>
				<li>Existing user are identified by <b>email</b></li>
				<li>If missing password or password_clear column, new password will be generated for new users</li>
				<li>If missing username column, it will be generated for new users</li>
				<li>"<b>password</b>" column set the hash of password (useful when import from a old Joomla installation)</li>
				<li>"<b>password_clear</b>" column set the password</li>
				<li>"<b>groups</b>" column set Joomla usergroups, default registration usergroup (Registered) will be always assigned
			</ul>
			<b>Some Examples:</b>
			<pre>username,email,password_clear,firstname,lastname,interests
john,john@test.it,passwordofjohn,John,Doe,"music,art"
mark,mark@test.com,passwordofmark,Mark,White,art</pre>
			<pre>email;password_clear;firstname;lastname;gender
john@test.it;passwordofjohn;John;Doe;male
mark@test.com;passwordofmark;Mark;White;male</pre>
			<pre>email,name,gender,groups
"john@test.it","John Doe","male","Author,Manager"
"mark@test.com","Mark White","male","Publisher"</pre>
		</div>
		<div>
		<form method="post" action="'.JRoute::_('index.php?option=com_jsn&view=import').'" class="form-horizontal" name="importcsv" enctype="multipart/form-data">
			<div class="control-group">
				<div class="control-label"><label>File CSV</label></div>
				<div class="controls">
					<input type="file" name="filecsv"/>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label"><label>Overwrite existing user</label></div>
				<div class="controls">
					<label class="checkbox"><input type="checkbox" name="overwrite" value="1" />Yes</label>
				</div>
			</div>
			<div class="control-group">
				<div class="control-label"><label>Send email with access details to New User</label></div>
				<div class="controls">
					<label class="checkbox"><input type="checkbox" disabled="disabled" '.($params->get('mail_to_user', 1) ? 'checked="checked" ' : '').'name="sendmail" value="1" />Yes (you can set this from plugin "<a href="index.php?option=com_plugins&task=plugin.edit&extension_id='.$pluginId.'" >User - Joomla!</a>")</label>
				</div>
			</div>
			<div class="form-actions">
				<input name="goimport" type="submit" class="btn btn-primary" value="Import" />
			</div>
		</form>
		</div>
		');
	}
	
	private function check()
	{
		$error=false;
		$errorField=array();

		// Check required fields
		if(!in_array('email',$this->csv->titles))
		{
			$error=true;
			$errorField[]='Email';
		}
		/*if(!in_array('name',$this->csv->titles) && !in_array('firstname',$this->csv->titles))
		{
			$error=true;
			$errorField[]='Name or Firstname';
		}*/
		if($error)
		{
			$app=JFactory::getApplication();
			$app->enqueueMessage('Missing some required fields: '.implode(', ',$errorField),'error');
			$this->getForm();
			return;
		}

		// Check known fields
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->select('alias')->from('#__jsn_fields')->where($db->quoteName('level').' = 2')->where($db->quoteName('type').' != '.$db->quote('delimeter'));
		$db->setQuery($query);
		$this->jsn_fields=$db->loadColumn();
		foreach($this->csv->titles as $field)
		{
			$from=array('_lat','_lng');
			$to=array('','');
			$field=str_replace($from,$to,$field);
			if(!in_array($field,$this->j_fields) && !in_array($field,$this->jsn_fields))
			{
				$error=true;
				$errorField[]=$field;
			}
		}
		if($error)
		{
			$app=JFactory::getApplication();
			if(count($errorField)==1) $app->enqueueMessage(implode(', ',$errorField).' column is unknown and will be skipped','warning');
			else $app->enqueueMessage(implode(', ',$errorField).' columns are unknown and will be skipped','warning');
			
		}

		$this->import();
		
		
	}
	
	private function import()
	{
		$app=JFactory::getApplication();
		$db=JFactory::getDbo();
		$log_error=array();
		$log_new=0;
		$log_updated=0;
		$counter=0;

		$query=$db->getQuery(true);
		$query->select('alias')->from('#__jsn_fields')->where($db->quoteName('level').' = 2')
			->where('('.$db->quoteName('type').' = '.$db->quote('checkboxlist').' OR ('. $db->quoteName('type').' = '.$db->quote('selectlist').' AND '.$db->quoteName('params').' LIKE '.$db->quote('%"select_multiple":"1"%').'))');
		$db->setQuery($query);
		$this->jsn_fields_multiple=$db->loadColumn();

		$query=$db->getQuery(true);
		$query->select('id,title')->from('#__usergroups');
		$db->setQuery($query);
		$usergroups=$db->loadAssocList('title');

		foreach($this->csv->data as $userRow)
		{
			$counter+=1;

			// Check Correct Email Address
			if (!filter_var($userRow['email'], FILTER_VALIDATE_EMAIL)) {
				$log_error[]='- Email not valid in row '.($counter+1);
    			continue;
			}

			// Compose Name
			if(!empty($userRow['name']) && empty($userRow['firstname']))
			{
				$userRow['firstname']=$userRow['name']; // TODO split name based on JSN config
			}
			elseif(empty($userRow['name']) && !empty($userRow['firstname']))
			{
				$userRow['name']=$userRow['firstname'];
				if(!empty($userRow['secondname'])) $userRow['name'].=' '.$userRow['secondname'];
				if(!empty($userRow['lastname'])) $userRow['name'].=' '.$userRow['lastname'];
			}
			
			// Check if user already exists
			$query=$db->getQuery(true);
			$query->select('id')->from('#__users')->where($db->quoteName('email').' = '.$db->quote($userRow['email']));
			$db->setQuery($query);
			$exist=$db->loadResult();

			// Check if name is available and not empty (only for new users)
			if(empty($userRow['name']) && !$exist)
			{
				$log_error[]='- Missing name or firstname in row '.($counter+1);
    			continue;
			}

			// UserGroup to Array
			$jUserConfig = JComponentHelper::getParams('com_users');
			$defaultGroup=$jUserConfig->get('new_usertype',2);
			if(!empty($userRow['groups']))
			{
				foreach ($usergroups as $k => $v)
				{
					$userRow['groups'] = str_ireplace($k,$v['id'],$userRow['groups']);
				}
				$userRow['groups']=explode(',',$userRow['groups']);
				if(!in_array($defaultGroup,$userRow['groups'])) $userRow['groups'][]=$defaultGroup;
			}
			else{
				if(!$exist) $userRow['groups']=array($defaultGroup);
				else unset($userRow['groups']);
			}

			if(!$exist)
			{
				// Add User
				$user=new JUser();

				// If missing password_clear and password assign Random Password
				if(empty($userRow['password']) && empty($userRow['password_clear']))
				{
					$userRow['password_clear']=JUserHelper::genRandomPassword();
				}
				if(empty($userRow['password']))
				{
					$salt=JUserHelper::getSalt();
					$userRow['password']=JUserHelper::getCryptedPassword($userRow['password_clear'],$salt);
				}
				// If missing username assign Username based on Name
				$jsnConfig = JComponentHelper::getParams('com_jsn');
				if(empty($userRow['username']) && $jsnConfig->get('logintype', 'USERNAME')!='MAIL')
				{
					$username=JApplication::stringURLSafe($userRow['name']);
					if( ! trim($username,'-_ ') ){
						$username='user_'.date('YmdHis').rand(0,2000);
					}
					else $username .= '_'.rand(0,2000);
					$userRow['username']=$username;
				}
				elseif(empty($userRow['username'])){
					$userRow['username']=$userRow['email'];
				}

				$log_new+=1;
			}
			else
			{
				// Update User if requested
				if(!JFactory::getApplication()->input->getVar('overwrite',false)) continue;
				
				// Load User
				$user=JFactory::getUser($exist);

				// Remove Name if empty
				if(empty($userRow['name'])) {
					unset($userRow['name']);
					unset($userRow['firstname']);
					unset($userRow['secondname']);
					unset($userRow['lastname']);
				}

				// Remove Username if empty
				if(empty($userRow['username'])) unset($userRow['username']);

				// Update password if available password_clear
				if(!empty($userRow['password_clear']))
				{
					$salt=JUserHelper::getSalt();
					$userRow['password']=JUserHelper::getCryptedPassword($userRow['password_clear'],$salt);
				}

				$log_updated+=1;
			}

			foreach($userRow as $k => $v)
			{
				$from=array('_lat','_lng');
				$to=array('','');
				$field=str_replace($from,$to,$k);
				if($field!='id' && (in_array($field,$this->j_fields) || in_array($field,$this->jsn_fields)))
				{
					if(in_array($k, $this->jsn_fields_multiple))
					{
						$v=explode(',',$v);
					}
					if($k=='params') {
						$j_params=(object)json_decode($v);
						foreach($j_params as $pk => $pv){
							$user->setParam($pk,$pv);
						}
					}
					else $user->set($k,$v);
				}
			}
			
			$user->save((boolean)$exist);

		}
		$app->enqueueMessage( 'New imported users: <b>'.$log_new.'</b>','notice');
		$app->enqueueMessage('Updated users: <b>'.$log_updated.'</b>','notice');
		if(count($log_error))
		{
			$app->enqueueMessage( 'Errors: <b>'.count($log_error).'</b><br />'.implode('<br />', $log_error ),'error');
		} 

	}
}
?></div>
<style>
.form-horizontal .control-label{width:160px;}
</style>