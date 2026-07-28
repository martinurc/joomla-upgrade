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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/* @var EventbookingViewRegisterHtml $this */
$url = Route::_('index.php?option=com_eventbooking&task=register.process_group_registration&Itemid=' . $this->Itemid, false);

$selectedState = '';

$bootstrapHelper     = $this->bootstrapHelper;
$controlGroupClass   = $bootstrapHelper->getClassMapping('control-group');
$inputPrependClass   = $bootstrapHelper->getClassMapping('input-prepend');
$inputAppendClass    = $bootstrapHelper->getClassMapping('input-append');
$addOnClass          = $bootstrapHelper->getClassMapping('add-on');
$controlLabelClass   = $bootstrapHelper->getClassMapping('control-label');
$controlsClass       = $bootstrapHelper->getClassMapping('controls');
$btnPrimary          = $bootstrapHelper->getClassMapping('btn btn-primary');

if ($this->config->get('form_layout') == 'stacked')
{
	$formClass  = $bootstrapHelper->getClassMapping('form');
	$formFormat = 'stacked';
}
else
{
	$formClass  = $bootstrapHelper->getClassMapping('form form-horizontal');
	$formFormat = 'horizontal';
}

$useUkGrid           = false;

if ($this->enableFormGrid)
{
	if (in_array($this->config->twitter_bootstrap_version, [4, 5]))
	{
		$formClass .= ' ' . $bootstrapHelper->getClassMapping('row-fluid');
	}
	elseif ($this->config->twitter_bootstrap_version == 'uikit3')
	{
		$useUkGrid = true;
	}
}

$layoutData = [
	'controlGroupClass' => $controlGroupClass,
	'controlLabelClass' => $controlLabelClass,
	'controlsClass'     => $controlsClass,
];

if (!$this->userId && ($this->config->user_registration || $this->config->show_user_login_section))
{
	$validateLoginForm = true;
	echo $this->loadCommonLayout('register/register_login.php', $layoutData);
}
else
{
	$validateLoginForm = false;
}
?>
<form method="post" name="adminForm" id="adminForm" action="<?php echo $url; ?>" autocomplete="off" class="<?php echo $formClass; ?>" enctype="multipart/form-data">
<?php
	if (!$this->userId && $this->config->user_registration)
	{
		echo $this->loadCommonLayout('register/register_user_registration.php', $layoutData);
	}

	$fields = $this->form->getFields();

	if (isset($fields['state']))
	{
		$selectedState = $fields['state']->value;
	}

	// Workaround to have it work with stacked form
	if ($formFormat == 'stacked' && in_array($this->config->twitter_bootstrap_version, [4, 5]))
	{
		$bootstrapHelper->addClassMapping('control-label', 'form-control-label');
		$bootstrapHelper->addClassMapping('controls', 'controls');
	}

	if ($useUkGrid)
	{
	?>
		<div class="uk-grid eb-grid-fields-container">
	<?php
	}

	$dateFields = [];

	foreach ($fields as $field)
	{
		if ($field->position != 0)
		{
			continue;
		}

		echo $field->getControlGroup($bootstrapHelper);

		if ($field->type == 'Date')
		{
			$dateFields[] = $field->name;
		}
	}

	if ($useUkGrid)
	{
	?>
		</div>
	<?php
	}

	// Restore the workaround
	if ($formFormat == 'stacked' && in_array($this->config->twitter_bootstrap_version, [4, 5]))
	{
		$bootstrapHelper->addClassMapping('control-label', $controlLabelClass);
		$bootstrapHelper->addClassMapping('controls', $controlsClass);
	}

	if (($this->totalAmount > 0) || $this->form->containFeeFields())
	{
	?>
		<h3 class="eb-heading"><?php echo Text::_('EB_PAYMENT_INFORMATION'); ?></h3>
	<?php
		foreach ($fields as $field)
		{
			if ($field->position != 1)
			{
				continue;
			}

			echo $field->getControlGroup($bootstrapHelper);

			if ($field->type == 'Date')
			{
				$dateFields[] = $field->name;
			}
		}

		$layoutData['currencySymbol']     = $this->event->currency_symbol ?: $this->config->currency_symbol;
		$layoutData['onCouponChange']     = 'calculateGroupRegistrationFee();';
		$layoutData['addOnClass']         = $addOnClass;
		$layoutData['inputPrependClass']  = $inputPrependClass;
		$layoutData['inputAppendClass']   = $inputAppendClass;
		$layoutData['showDiscountAmount'] = ($this->enableCoupon || $this->discountAmount > 0 || $this->bundleDiscountAmount > 0);
		$layoutData['showTaxAmount']      = ($this->event->tax_rate > 0);
		$layoutData['showGrossAmount']    = ($this->enableCoupon || $this->discountAmount > 0 || $this->bundleDiscountAmount > 0 || $this->event->tax_rate > 0 || $this->showPaymentFee);

		echo $this->loadCommonLayout('register/register_payment_amount.php', $layoutData);

		if (!$this->waitingList)
		{
			$layoutData['registrationType'] = 'group';
			echo $this->loadCommonLayout('register/register_payment_methods.php', $layoutData);
		}
	}

	foreach ($fields as $field)
	{
		if ($field->position != 2)
		{
			continue;
		}

		echo $field->getControlGroup($bootstrapHelper);

		if ($field->type == 'Date')
		{
			$dateFields[] = $field->name;
		}
	}
	
	if ($this->config->show_privacy_policy_checkbox || $this->config->show_subscribe_newsletter_checkbox)
	{
		echo $this->loadCommonLayout('register/register_gdpr.php', $layoutData);
	}

	if ($articleId = $this->getTermsAndConditionsArticleId($this->event, $this->config))
	{
		$layoutData['articleId'] = $articleId;

		echo $this->loadCommonLayout('register/register_terms_and_conditions.php', $layoutData);
	}

	if ($this->showCaptcha)
	{
		if (in_array($this->captchaPlugin, ['recaptcha_invisible', 'recaptcha_v3']))
		{
			$style = ' style="display:none;"';
		}
		else
		{
			$style = '';
		}
	?>
		<div class="<?php echo $controlGroupClass; ?>">
			<div class="<?php echo $controlLabelClass; ?>"<?php echo $style; ?>>
				<?php echo Text::_('EB_CAPTCHA'); ?><span class="required">*</span>
			</div>
			<div class="<?php echo $controlsClass; ?>">
				<?php echo $this->captcha; ?>
			</div>
		</div>
	<?php
	}

	if ($this->waitingList)
	{
		$buttonText = Text::_('EB_PROCESS');
	}
	else
	{
		$buttonText = Text::_('EB_PROCESS_REGISTRATION');
	}

	if (!empty($this->fees['show_vat_number_field']))
	{
		$showVatNumberField = 1;
	}
	else
	{
		$showVatNumberField = 0;
	}
	?>
	<div class="form-actions">
		<input type="button" class="<?php echo $btnPrimary; ?>" name="btn-group-billing-back" id="btn-group-billing-back" value="<?php echo  Text::_('EB_BACK') ;?>">
		<input type="submit" class="<?php echo $btnPrimary; ?>" name="btn-process-group-billing" id="btn-process-group-billing" value="<?php echo $buttonText;?>">
		<img id="ajax-loading-animation" alt="<?php echo Text::_('EB_PROCESSING'); ?>" src="<?php echo Uri::base(true);?>/media/com_eventbooking/ajax-loadding-animation.gif" style="display: none;"/>
	</div>
	<?php
		if (count($this->methods) == 1)
		{
		?>
			<input type="hidden" name="payment_method" value="<?php echo $this->methods[0]->getName(); ?>" />
		<?php
		}
	?>
	<input type="hidden" name="event_id" value="<?php echo $this->event->id; ?>" />
	<input type="hidden" name="show_payment_fee" value="<?php echo (int) $this->showPaymentFee ; ?>" />
	<input type="hidden" name="number_registrants" value="<?php echo $this->numberRegistrants; ?>" />
    <input type="hidden" id="group_billing_payment_amount" value="<?php echo $this->amount; ?>" />
    <input type="hidden" id="group_billing_selected_state" value="<?php echo $selectedState; ?>" />
    <input type="hidden" id="group_billing_show_vat_number_field" value="<?php echo $showVatNumberField; ?>" />
	<?php echo $this->loadCommonLayout('register/register_anti_spam.php'); ?>
	<script type="text/javascript">
		var eb_current_page = 'group_billing';
	</script>
</form>