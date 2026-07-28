<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

require_once(JPATH_COMPONENT . '/../com_users/controllers/users.php');

/**
 * View class for a list of users.
 *
 * @since  1.6
 */
class JsnControllerUsers extends UsersControllerUsers
{
	public function getModel($name = 'User', $prefix = 'JsnModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}
}
