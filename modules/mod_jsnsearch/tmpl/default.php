<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

$jsnConfig=JComponentHelper::getParams('com_jsn');

$doc = JFactory::getDocument();
if(JFactory::getApplication()->isSite() && $jsnConfig->get('bootstrap',0)) {
	$doc->addStylesheet(JURI::root().'media/jui/css/bootstrap.min.css');
	$dir = $doc->direction;
	if($dir=='rtl')
	{
		$doc->addStylesheet(JURI::root().'media/jui/css/bootstrap-rtl.css');
	}
}

?>
<form action="<?php echo JRoute::_('index.php?Itemid='.$params->get('menuitem',''),false); ?>" method="get">
<div class="jsn_search_module<?php $moduleclass_sfx; ?> jsn_result_<?php echo $params->get('menuitem',''); ?>">
	<?php
	$ids=$params->get('search_fields', array());
	if(in_array('id', $ids)) :
	?>
		<div class="control-group">
			<label class="control-label">ID</label>
			<div class="controls">
				<input type="text" placeholder="<?php echo JText::_('COM_JSN_SEARCHFOR'); ?> ID..." name="searchid" value="<?php echo JFactory::getApplication()->input->get('searchid',''); ?>"/>
			</div>
		</div>
	<?php 
	endif;
	if(in_array('formatname', $ids)) :
	?>
		<div class="control-group">
			<label class="control-label"><?php echo JText::_('COM_JSN_NAME'); ?></label>
			<div class="controls">
				<input type="text" placeholder="<?php echo JText::_('COM_JSN_SEARCHFOR').' '.JText::_('COM_JSN_NAME'); ?>..." name="name" value="<?php echo JFactory::getApplication()->input->get('name','','raw'); ?>"/>
			</div>
		</div>
	<?php 
	endif;
	
	$db = JFactory::getDBO();
	$query = $db->getQuery(true);
	$query->select('a.*')->from('#__jsn_fields AS a')->where('a.level = 2')->where('a.search = 1')->where('a.published = 1')->order($db->escape('a.lft') . ' ASC');
	$db->setQuery( $query );
	$fields = $db->loadObjectList('id');
	
	foreach (glob(JPATH_ADMINISTRATOR . '/components/com_jsn/helpers/fields/*.php') as $filename) {
		require_once $filename;
	}
	
	foreach($ids as $id)
	{
		if($id!='formatname' && isset($fields[$id])){
			$registry = new JRegistry;
			$registry->loadString($fields[$id]->params);
			$fields[$id]->params = $registry;

			$class='Jsn'.ucfirst($fields[$id]->type).'FieldHelper';
			
			if($fields[$id]->params->get('titlesearch','')!='') $fields[$id]->title=$fields[$id]->params->get('titlesearch','');
			
			?>
				<div class="control-group">
					<label class="control-label"><?php echo JText::_($fields[$id]->title); ?></label>
					<div class="controls">
			<?php
			if(class_exists($class)) echo str_replace('jform_','jsearchform'.$module->id.'_',$class::getSearchInput($fields[$id]));
			?>
					</div>
				</div>
			<?php
		}
	}
	?>
	
	<?php
	if(in_array('status', $ids)) :
	?>
		<div class="control-group">
			<div class="controls">
				<fieldset class="checkboxes" id="jform_corestatus">
					<label class="checkbox inline"><input type="checkbox" name="status" value="1" <?php if(JFactory::getApplication()->input->get('status','')!='') echo "checked=checked"; ?> /><b>OnLine</b></label>
				</fieldset>
			</div>
		</div>
	<?php 
	endif;
	?>
	
	<input type="submit" class="btn btn-primary jsn_search_btn" value="<?php echo JText::_('COM_JSN_SEARCH'); ?>" />
	<input type="hidden" name="search" value="1"/>
	<?php echo JHtml::_('form.token'); ?>
	
</div>
</form>