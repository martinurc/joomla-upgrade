<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

if(JFactory::getApplication()->input->get('export','false') == 'true') {echo $this->loadTemplate('export');}

$doc = JFactory::getDocument();
$doc->addStylesheet(JURI::root().'components/com_jsn/assets/css/style.min.css');
$module = new stdClass();
$module->id='_admin';

$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select('id')->from('#__jsn_fields')->where('search = 1');
$column = $db->setQuery($query)->loadColumn();

if(file_exists(JPATH_ADMINISTRATOR .'/templates/isis/html/com_users/users/default.php' ) ) include(JPATH_ADMINISTRATOR .'/templates/isis/html/com_users/users/default.php' );
else include(JPATH_COMPONENT . '/../com_users/views/users/tmpl/default.php');

if(count($column)) {
	$params = new JRegistry();
	$params->set('search_fields',$column);

	include(JPATH_SITE . '/modules/mod_jsnsearch/helper.php');

	ModJsnSearch::getJavascript($module,$params);

	include(JPATH_SITE . '/modules/mod_jsnsearch/tmpl/default.php');
?>
	<script>
	jQuery(document).ready(function($){
		$('#adminForm').attr('action',$('#adminForm').attr('action').replace('option=com_users','option=com_jsn'));
		$('#sidebar').append('<hr /><h2 style="padding:0 10px;"><?php echo JText::_('COM_USERS_SEARCH_USERS'); ?></h2>');
		$('.jsn_search_module').appendTo('#sidebar');
		$('#adminForm').searchtools().data().plugin_searchtools.filterContainer = $($('#adminForm').searchtools().data().plugin_searchtools.filterContainer.selector+",#sidebar");
	});
	</script>
	<style>
	#sidebar .jsn_search_module{padding:10px 10px 10px 10px;}
	</style>
<?php 
} 
else { 
?>
	<script>
	jQuery(document).ready(function($){
		$('#adminForm').attr('action',$('#adminForm').attr('action').replace('option=com_users','option=com_jsn'));
	});
	</script>	
<?php 
}

require_once(JPATH_ADMINISTRATOR.'/components/com_jsn/defines.php');
$config = JComponentHelper::getParams('com_jsn');
?>
<script>
	jQuery(document).ready(function($){
<?php
if(JSN_TYPE == 'pro'){
?>
	
		$('#toolbar').append('<div class="btn-wrapper dropdown"><button class="btn btn-small btn-info" type="button" data-toggle="dropdown"><i class="icon-share"></i> <?php echo JText::_('COM_JSN_EXPORT'); ?></button><ul style="font-size:12px;text-shadow:none;" class="dropdown-menu"><li><a href="index.php?option=com_jsn&view=users&export=true&limit=0"><?php echo JText::_('COM_JSN_EXPORT_TYPE_IMPORTABLE'); ?></a></li><li><a href="index.php?option=com_jsn&view=users&export=true&limit=0&readable=1"><?php echo JText::_('COM_JSN_EXPORT_TYPE_READABLE'); ?></a></li></ul></div>');
<?php
}
if(JSN_TYPE != 'free' && $config->get('admin_loginas',1)){
?>
		$('#userList tbody tr').each(function(){
			var id = $(this).find('td:last-child').text();
			$(this).find('.btn-group').append('<a target="_blank" href="<?php echo JURI::root(); ?>index.php?option=com_users&amp;switchuser=1&amp;uid='+id+'" class="hasTooltip btn btn-mini" title="" data-original-title="<?php echo JText::_('COM_JSN_LOGINASUSER'); ?>"><span class="icon-switch" aria-hidden="true"></span><span class="hidden-phone"><?php echo JText::_('COM_JSN_LOGINASUSER'); ?></span></a>');
		});
	
<?php } ?>
	});
</script>