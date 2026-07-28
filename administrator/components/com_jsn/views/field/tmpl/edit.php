<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

$app = JFactory::getApplication();
$input = $app->input;

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

$doc = JFactory::getDocument();
$doc->addStylesheet(JURI::root().'components/com_jsn/assets/css/jquery.sortable.css');
$doc->addScript(JURI::root().'components/com_jsn/assets/js/jquery.sortable.min.js');

$user=JFactory::getUser();

// Create shortcut to parameters.
$params = $this->state->get('params');
$params = $params->toArray();

if($input->get('id')>0) {
	$this->form->setFieldAttribute('alias','readonly','true');
	$this->form->setFieldAttribute('type','readonly','true');
}

// Populate Fields Params
$this->form->setValue('params',null,(object)$this->item->params);

?>


<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'field.cancel' || document.formvalidator.isValid(document.id('item-form'))) {
			<?php echo $this->form->getField('description')->save(); ?>
			Joomla.submitform(task, document.getElementById('item-form'));
		}
	}
</script>
<script type="text/javascript">
	jQuery(document).ready(function($){
		<?php
		$condition_suffix=array('','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19');
		foreach($condition_suffix as $suffix) :
		?>
		$('#jform_params_condition_operator<?php echo $suffix; ?>-lbl').parent().parent().addClass('conditionGroup conditionGroupFirst');
		$('#jform_params_condition_field<?php echo $suffix; ?>-lbl').parent().parent().addClass('conditionGroup');
		$('#jform_params_condition_custom<?php echo $suffix; ?>-lbl').parent().parent().addClass('conditionGroup');
		$('#jform_params_condition_twoways<?php echo $suffix; ?>-lbl').parent().parent().addClass('conditionGroup conditionGroupLast');
		$('#jform_params_condition_hide<?php echo $suffix; ?>-lbl').parent().parent().addClass('conditionGroup conditionGroupFirst');
		$('#jform_params_condition_action<?php echo $suffix; ?>-lbl').parent().parent().addClass('conditionGroup conditionGroupLast');
		$('#jform_params_condition_usergroups<?php echo $suffix; ?>-lbl').parent().parent().addClass('conditionGroup conditionGroupFirst');
		$('#jform_params_condition_ugaction<?php echo $suffix; ?>-lbl').parent().parent().addClass('conditionGroup conditionGroupLast');
		$('#jform_params_spacer_conditions<?php echo $suffix; ?>-lbl').closest('.control-group').addClass('conditionGroupTitle');
		
		$('#jform_params_condition_operator<?php echo $suffix; ?>').change(function(){
			if($(this).val()==0){
				$('#jform_params_condition_field<?php echo $suffix; ?>').parent().parent().hide();
				$('#jform_params_condition_custom<?php echo $suffix; ?>').parent().parent().hide();
				$('#jform_params_condition_hide<?php echo $suffix; ?>').parent().parent().hide();
				$('fieldset#jform_params_condition_action<?php echo $suffix; ?>').parent().parent().hide();
				$('#jform_params_condition_usergroups<?php echo $suffix; ?>').parent().parent().hide();
				$('fieldset#jform_params_condition_twoways<?php echo $suffix; ?>').parent().parent().hide();
				$('fieldset#jform_params_condition_ugaction<?php echo $suffix; ?>').parent().parent().hide();
			}
			else{
				$('#jform_params_condition_field<?php echo $suffix; ?>').parent().parent().show();
				$('#jform_params_condition_custom<?php echo $suffix; ?>').parent().parent().show();
				$('#jform_params_condition_hide<?php echo $suffix; ?>').parent().parent().show();
				$('fieldset#jform_params_condition_action<?php echo $suffix; ?>').parent().parent().show();
				$('#jform_params_condition_usergroups<?php echo $suffix; ?>').parent().parent().show();
				$('fieldset#jform_params_condition_twoways<?php echo $suffix; ?>').parent().parent().show();
				$('fieldset#jform_params_condition_ugaction<?php echo $suffix; ?>').parent().parent().show();
			}
			$('#jform_params_condition_field<?php echo $suffix; ?>').change();
		});
		$('#jform_params_condition_field<?php echo $suffix; ?>').change(function(){
			if($(this).val()=='_custom' && $('#jform_params_condition_operator<?php echo $suffix; ?>').val()!='0'){
				$('#jform_params_condition_custom<?php echo $suffix; ?>').parent().parent().show();
			}
			else{
				$('#jform_params_condition_custom<?php echo $suffix; ?>').parent().parent().hide();
			}
		});
		$('#jform_params_condition_operator<?php echo $suffix; ?>').change();
		<?php
		endforeach;
		?>
		$('#jform_params_condition_action1-lbl').removeClass('btn-success');
		$('#jform_params_condition_twoways1-lbl').removeClass('btn-success');

		/* layout JS */
		
	});
</script>
<style>
.conditionGroup{background:#eee;margin-bottom:0px !important;padding:0px 10px 10px 10px;margin-left:50px;}
.conditionGroupFirst{padding-top:10px;border-top-left-radius: 4px;border-top-right-radius: 4px;}
.conditionGroupLast{margin-bottom:10px !important;border-bottom-left-radius: 4px;border-bottom-right-radius: 4px;}
.conditionGroupTitle{margin-top:30px;}
</style>

<form action="<?php echo JRoute::_('index.php?option=com_jsn&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate form-horizontal">
	<div class="row-fluid">
	<!-- Begin Content -->
		<div class="span10 form-horizontal">
			<?php echo JHtml::_('bootstrap'.JSNPREFIX.'.startTabSet', 'myTab', array('active' => 'general')); ?>

				<?php echo JHtml::_('bootstrap'.JSNPREFIX.'.addTab', 'myTab', 'general', JText::_('COM_JSN_FIELDSET_DETAILS', true)); ?>
					<fieldset class="adminform">
						<div class="control-group form-inline">
							<?php echo $this->form->getLabel('title'); ?> <?php echo $this->form->getInput('title'); ?> <?php echo $this->form->getLabel('catid'); ?> <?php echo $this->form->getInput('catid'); ?>
						</div>
						<div class="control-group form-inline">
							<?php echo $this->form->getLabel('alias'); ?> <?php echo $this->form->getInput('alias'); ?>
						</div>
						<div class="control-group form-inline">
							<?php echo $this->form->getLabel('type'); ?> <?php if(in_array($this->item->type,array('username','usermail','password','registerdate','lastvisitdate'))) echo ('<b>'.$this->item->type.'</b><input type="hidden" id="jform_type" value="'.$this->item->type.'" />'); else echo $this->form->getInput('type'); ?>
						</div>
						<?php echo $this->form->getInput('description'); ?>
					</fieldset>
				<?php echo JHtml::_('bootstrap'.JSNPREFIX.'.endTab'); ?>

						
							<?php echo $this->loadTemplate('conditions'); ?>
							<?php echo $this->loadTemplate('fieldoptions'); ?>
					
									</div>
				<input type="hidden" name="task" value="" />
				<?php echo JHtml::_('form.token'); ?>
		</div>
		<!-- End Content -->
		<!-- Begin Sidebar -->
		<div class="span2">
			<h4><?php echo JText::_('JDETAILS');?></h4>
			<hr />
			<fieldset class="form-vertical">
				<div class="control-group">
					<div class="controls">
						<?php echo jText::_($this->form->getValue('title')); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('parent_id')); ?>
					<div class="controls">
						<?php echo $this->form->getInput('parent_id'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('published')); ?>
					<div class="controls">
						<?php if($this->item->core || !$user->authorise('core.edit.state', 'com_jsn')) $this->form->setFieldAttribute('published','disabled','true'); echo $this->form->getInput('published'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('required')); ?>
					<div class="controls">
						<?php if($this->item->core && $this->item->alias!='avatar' && $this->item->alias!='secondname') $this->form->setFieldAttribute('required','disabled','true'); echo $this->form->getInput('required'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('profile')); ?>
					<div class="controls">
						<?php /*if($this->item->core) $this->form->setFieldAttribute('profile','disabled','true');*/ echo $this->form->getInput('profile'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('edit')); ?>
					<div class="controls">
						<?php if($this->item->core && $this->item->alias!='avatar') $this->form->setFieldAttribute('edit','disabled','true'); echo $this->form->getInput('edit'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('register')); ?>
					<div class="controls">
						<?php if($this->item->core && $this->item->alias!='avatar') $this->form->setFieldAttribute('register','disabled','true'); echo $this->form->getInput('register'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('editbackend')); ?>
					<div class="controls">
						<?php if($this->item->core && $this->item->alias!='avatar') $this->form->setFieldAttribute('editbackend','disabled','true'); echo $this->form->getInput('editbackend'); ?>
					</div>
				</div>
				<?php if($this->item->type!='delimeter' && $this->item->type!='password') : ?>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('search')); ?>
					<div class="controls">
						<?php /*if($this->item->core) $this->form->setFieldAttribute('profile','disabled','true');*/ echo $this->form->getInput('search'); ?>
					</div>
				</div>
				<?php endif; ?>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('access')); ?>
					<div class="controls">
						<?php if($this->item->core) $this->form->setFieldAttribute('access','disabled','true');echo $this->form->getInput('access'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo str_replace('<label ','<label data-placement="left" ',$this->form->getLabel('accessview')); ?>
					<div class="controls">
						<?php echo $this->form->getInput('accessview'); ?>
					</div>
				</div>
				<!-- <div class="control-group">
					<?php echo $this->form->getLabel('language'); ?>
					<div class="controls">
						<?php echo $this->form->getInput('language'); ?>
					</div>
				</div> -->
			</fieldset>
		</div>
		<!-- End Sidebar -->
	</div>
</form>
