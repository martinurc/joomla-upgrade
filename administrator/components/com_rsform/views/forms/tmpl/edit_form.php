<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<div class="<?php echo RSFormProAdapterGrid::row(); ?>">
	<div class="<?php echo RSFormProAdapterGrid::column(10); ?>">
		<fieldset class="form-horizontal">
			<legend class="rsfp-legend"><?php echo Text::_('RSFP_FORM_INFO_BASIC'); ?></legend>
			<?php
				echo $this->jform->renderFieldset('form_info_basic');
			?>
			<legend class="rsfp-legend"><?php echo Text::_('RSFP_FORM_INFO_VALIDATION'); ?></legend>
			<?php
				echo $this->jform->renderFieldset('form_info_advanced');
			?>
			<legend class="rsfp-legend"><?php echo Text::_('RSFP_FORM_INFO_THANK_YOU_MESSAGE'); ?></legend>
			<?php
				echo $this->jform->renderFieldset('form_info_thankyou');
			?>
			<legend class="rsfp-legend"><?php echo Text::_('RSFP_FORM_INFO_SUBMISSION'); ?></legend>
			<?php
				echo $this->jform->renderFieldset('form_info_submission');
			?>
            <legend class="rsfp-legend"><?php echo Text::_('RSFP_FORM_INFO_CONFIRMATION'); ?></legend>
			<?php
			echo $this->jform->renderFieldset('form_info_confirmation');
			?>
			<legend class="rsfp-legend"><?php echo Text::_('RSFP_FORM_INFO_MISC'); ?></legend>
			<?php
				echo $this->jform->renderFieldset('form_info_misc');
			?>
		</fieldset>
	</div>
	<div class="<?php echo RSFormProAdapterGrid::column(2); ?>">
		<button class="btn btn-secondary" type="button" onclick="toggleQuickAdd();"><?php echo Text::_('RSFP_TOGGLE_QUICKADD'); ?></button>
		<div class="QuickAdd">
			<h3><?php echo Text::_('RSFP_QUICK_ADD');?></h3>
			<?php
			echo RSFormProHelper::generateQuickAddGlobal();

			foreach ($this->quickfields as $field)
			{
				echo RSFormProHelper::generateQuickAdd($field, 'display');
			}
			?>
		</div>
	</div>
</div>