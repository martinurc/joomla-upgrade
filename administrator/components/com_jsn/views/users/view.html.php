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

if (file_exists(JPATH_COMPONENT . '/../com_users/views/users/view.html.php')) {
	include(JPATH_COMPONENT . '/../com_users/views/users/view.html.php');
}

/**
 * View class for a list of users.
 *
 * @since  1.6
 */
class JsnViewUsers extends UsersViewUsers
{
	
}
