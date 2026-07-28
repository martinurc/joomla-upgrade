<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


?>
<?php
	//echo JHtml::_('bootstrap.startAccordion', 'categoryOptions', array('active' => 'collapse0'));
	$fieldSets = $this->form->getFieldsets('params');
	$i = 0;

	foreach ($fieldSets as $name => $fieldSet) :
		if($name=='groups') continue;
		$label = !empty($fieldSet->label) ? JText::_($fieldSet->label) : JText::_('COM_JSN_'.$name.'_FIELDSET_LABEL');
		echo JHtml::_('bootstrap'.JSNPREFIX.'.addTab', 'myTab', $name, $label);
			if (isset($fieldSet->description) && trim($fieldSet->description)) :
				echo '<p class="tip">'.$this->escape(JText::_($fieldSet->description)).'</p>';
			endif;
			$field_class='Jsn'.ucfirst($name).'FieldHelper';
			if(class_exists($field_class) && method_exists($field_class, 'editScript')) echo $field_class::editScript();
			?>
				<?php foreach ($this->form->getFieldset($name) as $field) : ?>
					<div class="control-group">
						<div class="control-label">
							<?php echo $field->label; ?>
						</div>
						<div class="controls">
							<?php echo $field->input; ?>
						</div>
					</div>
				<?php endforeach;
		echo JHtml::_('bootstrap'.JSNPREFIX.'.endTab');
	endforeach;
//echo JHtml::_('bootstrap.endAccordion');

?>
<script>
jQuery(document).ready(function($){
	$('#myTabTabs li').hide();
	$('#myTabTabs li:first').show().next().show().next().show();
	$('#myTabTabs li a[href="#'+$('#jform_type').val()+'"]').parent().show();
	
	$('#jform_type').change(function(){
		$('#myTabTabs li').hide();
		$('#myTabTabs li:first').show().next().show().next().show();
		$('#myTabTabs li a[href="#'+$(this).val()+'"]').parent().show();
	});
});
</script>
