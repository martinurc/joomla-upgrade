<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


require_once(JPATH_SITE.'/components/com_jsn/helpers/helper.php');

$controller = JControllerLegacy::getInstance('Jsn');
$controller->execute(JFactory::getApplication()->input->get('task', 'display'));
$controller->redirect();

?>