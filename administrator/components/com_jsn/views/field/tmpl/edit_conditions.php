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

				<?php echo JHtml::_('bootstrap'.JSNPREFIX.'.addTab', 'myTab', 'conditions', JText::_('COM_JSN_FIELDSET_CONDITIONS', true)); ?>
					<fieldset class="adminform">
						<div class="control-group form-inline">
							<div class="control-label"><?php echo $this->form->getLabel('conditions'); ?></div>
							<div class="controls"><?php echo $this->form->getInput('conditions'); ?></div>
						</div>
					</fieldset>
						
				<?php echo JHtml::_('bootstrap'.JSNPREFIX.'.endTab'); ?>
				