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

// Create shortcut to parameters.
$params = $this->state->get('params');
$params = $params->toArray();

?>

<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'field.cancel' || document.formvalidator.isValid(document.id('item-form'))) {
			<?php //echo $this->form->getField('description')->save(); ?>
			Joomla.submitform(task, document.getElementById('item-form'));
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_jsn&layout=editgroup&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate form-horizontal">
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
						<?php foreach ($this->form->getFieldset('groups') as $field) : ?>
							<div class="control-group">
								<div class="control-label">
									<?php echo $field->label; ?>
								</div>
								<div class="controls">
									<?php echo $field->input; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</fieldset>
						<!-- <div class="row-fluid">
							<div class="span6">
								<h4><?php echo JText::_('COM_JSN_FIELDSET_URLS_AND_IMAGES');?></h4>
								<div class="control-group">
									<?php echo $this->form->getLabel('images'); ?>
									<div class="controls">
										<?php echo $this->form->getInput('images'); ?>
									</div>
								</div>
								<?php foreach ($this->form->getGroup('images') as $field) : ?>
									<div class="control-group">
										<?php if (!$field->hidden) : ?>
											<?php echo $field->label; ?>
										<?php endif; ?>
										<div class="controls">
											<?php echo $field->input; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div> -->
				<?php echo JHtml::_('bootstrap'.JSNPREFIX.'.endTab'); ?>

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
				<input type="hidden" name="jform[parent_id]" value="1" />
				<!-- <div class="control-group">
					<?php echo $this->form->getLabel('parent_id'); ?>
					<div class="controls">
						<?php echo $this->form->getInput('parent_id'); ?>
					</div>
				</div> -->
				<div class="control-group">
					<?php echo $this->form->getLabel('published'); ?>
					<div class="controls">
						<?php if($this->item->core) $this->form->setFieldAttribute('published','disabled','true'); echo $this->form->getInput('published'); ?>
					</div>
				</div>
				<!-- <div class="control-group">
					<?php echo $this->form->getLabel('required'); ?>
					<div class="controls">
						<?php echo $this->form->getInput('required'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo $this->form->getLabel('profile'); ?>
					<div class="controls">
						<?php echo $this->form->getInput('profile'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo $this->form->getLabel('register'); ?>
					<div class="controls">
						<?php echo $this->form->getInput('register'); ?>
					</div>
				</div> -->
				<div class="control-group">
					<?php echo $this->form->getLabel('access'); ?>
					<div class="controls">
						<?php if($this->item->core) $this->form->setFieldAttribute('access','disabled','true'); echo $this->form->getInput('access'); ?>
					</div>
				</div>
				<div class="control-group">
					<?php echo $this->form->getLabel('accessview'); ?>
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
