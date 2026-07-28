<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper   = $this->bootstrapHelper;
$controlGroupClass = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass = $bootstrapHelper->getClassMapping('control-label');
$controlsClass     = $bootstrapHelper->getClassMapping('controls');
$inputPrependClass = $bootstrapHelper->getClassMapping('input-prepend');
$inputAppendClass  = $bootstrapHelper->getClassMapping('input-append');
$addOnClass        = $bootstrapHelper->getClassMapping('add-on');
$inputSmallClass   = $bootstrapHelper->getClassMapping('input-small');
?>
<h3 class="eb-heading"><?php echo $this->escape(Text::_('EB_PAYMENT_INFORMATION')); ?></h3>
<div class="<?php echo $controlGroupClass;  ?>">
	<label class="<?php echo $controlLabelClass; ?>">
		<?php echo Text::_('EB_AMOUNT'); ?>
	</label>
	<div class="<?php echo $controlsClass; ?>">
		<?php
		$input = '<input id="amount" type="text" readonly="readonly" class="' . $inputSmallClass . ' form-control" value="' . EventbookingHelper::formatAmount($this->fees['amount'], $this->config) . '" />';

		echo $bootstrapHelper->getAddon($input, $this->currencySymol, $this->config->currency_position);
		?>
	</div>
</div>
<?php
	if ($this->showPaymentFee)
	{
	?>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass; ?>">
			<?php echo Text::_('EB_PAYMENT_FEE'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php
			$input = '<input id="payment_processing_fee" type="text" readonly="readonly" class="' . $inputSmallClass . ' form-control" value="' . EventbookingHelper::formatAmount($this->fees['payment_processing_fee'], $this->config) . '" />';

			echo $bootstrapHelper->getAddon($input, $this->currencySymol, $this->config->currency_position);
			?>
		</div>
	</div>
	<div class="<?php echo $controlGroupClass;  ?>">
		<label class="<?php echo $controlLabelClass; ?>">
			<?php echo Text::_('EB_GROSS_AMOUNT'); ?>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php
			$input = '<input id="gross_amount" type="text" readonly="readonly" class="' . $inputSmallClass . ' form-control" value="' . EventbookingHelper::formatAmount($this->fees['gross_amount'], $this->config) . '" />';

			echo $bootstrapHelper->getAddon($input, $this->currencySymol, $this->config->currency_position);
			?>
		</div>
	</div>
<?php
}
