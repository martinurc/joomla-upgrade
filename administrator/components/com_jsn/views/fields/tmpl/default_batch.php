<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


JHtml::_('formbehavior.chosen', 'select');

$published	= $this->state->get('filter.published');
?>
<div class="modal hide fade" id="collapseModal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">x</button>
		<h3><?php echo JText::_('COM_JSN_BATCH_OPTIONS');?></h3>
	</div>
	<div class="modal-body" style="padding:30px;">
		<p><?php echo JText::_('COM_JSN_BATCH_TIP'); ?></p>
		<div class="control-group">
			<div class="controls">
				<?php echo JHtml::_('jsnbatch.access');?>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<?php echo JHtml::_('jsnbatch.accessview');?>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<?php echo JHtml::_('jsnbatch.fieldgroup');?>
			</div>
		</div>
		<?php /*<div class="control-group">
			<div class="controls">
				<?php echo JHtml::_('batch.language'); ?>
			</div> 
		*/	?>	
		</div>
	<div class="modal-footer">
		<button class="btn" type="button" onclick="document.id('batch-field-id').value='';document.id('batch-access').value='';document.id('batch-language-id').value=''" data-dismiss="modal">
			<?php echo JText::_('JCANCEL'); ?>
		</button>
		<button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton('field.batch');">
			<?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
		</button>
	</div>
</div>
