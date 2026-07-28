<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

require_once(JPATH_ADMINISTRATOR . '/components/com_jsn/defines.php');

if (file_exists(JPATH_COMPONENT . '/../com_users/models/user.php')) {
	require_once(JPATH_COMPONENT . '/../com_users/models/user.php');
}

/**
 * Methods supporting a list of user records.
 *
 * @since  1.6
 */
class JsnModelUser extends UsersModelUser
{
	
}
