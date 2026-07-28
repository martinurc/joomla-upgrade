<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/* @var  $this EventbookingViewRegisterRaw */

$bootstrapHelper     = $this->bootstrapHelper;
$controlGroupClass   = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass   = $bootstrapHelper->getClassMapping('control-label');
$controlsClass       = $bootstrapHelper->getClassMapping('controls');
$btnPrimaryClass     = $bootstrapHelper->getClassMapping('btn btn-primary');

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

$memberFields = [];

foreach ($this->rowFields as $rowField)
{
	$memberFields[] = $rowField->name;
}

$memberFields = json_encode($memberFields);

if ($this->fieldSuffix && EventbookingHelper::isValidMessage($this->message->{'member_information_form_message' . $this->fieldSuffix}))
{
	$msg = $this->message->{'member_information_form_message' . $this->fieldSuffix};
}
else
{
	$msg = $this->message->member_information_form_message;
}

if (strlen($msg))
{
	$replaces = EventbookingHelperRegistration::buildEventTags($this->event, $this->config);

	$msg = EventbookingHelper::replaceUpperCaseTags($msg, $replaces);
?>
    <div class="eb-message"><?php echo HTMLHelper::_('content.prepare', $msg); ?></div>
<?php
}
?>
<form name="eb-form-group-members" id="eb-form-group-members" action="<?php echo Route::_('index.php?option=com_eventbooking&Itemid=' . $this->Itemid); ?>" autocomplete="off" class="<?php echo $formClass; ?>" method="post">
<?php
$dateFields = [];

for ($i = 1 ; $i <= $this->numberRegistrants; $i++)
{
	$headerText = Text::_('EB_MEMBER_REGISTRATION') ;
	$headerText = str_replace('[ATTENDER_NUMBER]', $i, $headerText);

	if ($this->config->allow_populate_group_member_data)
	{
	?>
		<div class="<?php echo $controlGroupClass; ?> clearfix">
			<h3 class="eb-heading">
				<?php echo $headerText ?>
			</h3>
			<?php
			if ($i > 1)
			{
				$options = [];
				$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_POPULATE_DATA_FROM'));

				for ($j = 1 ; $j < $i ; $j++)
				{
					$options[] = HTMLHelper::_('select.option', $j, Text::sprintf('EB_MEMBER_NUMBER', $j));
				}

				echo HTMLHelper::_('select.genericlist', $options, 'member_number_' . $i, 'id="member_number_' . $i . '" class="input-large eb-member-number-select" onchange="populateMemberFormData(' . $i . ', this.value)"', 'value', 'text', 0);
			}
			?>
		</div>
	<?php
	}
	else
	{
	?>
		<h3 class="eb-heading">
			<?php echo $headerText ?>
		</h3>
	<?php
	}

	$currentMemberFields = EventbookingHelperRegistration::getGroupMemberFields($this->rowFields, $i);

	$form = new RADForm($currentMemberFields);
	$form->setFieldSuffix($i);

	if (!isset($this->membersData['country_' . $i]))
	{
		$this->membersData['country_' . $i] = $this->defaultCountry;
	}

	$form->bind($this->membersData, $this->useDefaultValueForFields);

	$form->buildFieldsDependency();

	if (!$this->waitingList)
	{
		$form->setEventId($this->event->id);
	}

	$fields = $form->getFields();

	//We don't need to use ajax validation for email field for group members
	if (isset($fields['email']))
	{
		/* @var RADFormField $emailField */
		$emailField = $fields['email'];
		$cssClass = $emailField->getAttribute('class');
		$cssClass = str_replace(',ajax[ajaxEmailCall]', '', $cssClass);
		$emailField->setAttribute('class', $cssClass);
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

	foreach ($fields as $field)
	{
		$cssClass = $field->getAttribute('class');
		$cssClass = str_replace('equals[email]', 'equals[email_' . $i . ']', $cssClass);
		$field->setAttribute('class', $cssClass);

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
}

$layoutData = [
	'controlGroupClass' => $controlGroupClass,
	'controlLabelClass' => $controlLabelClass,
	'controlsClass'     => $controlsClass,
];

if (!$this->showBillingStep
	&& ($this->config->show_privacy_policy_checkbox || $this->config->show_subscribe_newsletter_checkbox))
{
	echo $this->loadCommonLayout('register/register_gdpr.php', $layoutData);
}

if (!$this->showBillingStep && $articleId = $this->getTermsAndConditionsArticleId($this->event, $this->config))
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
		<label class="<?php echo $controlLabelClass; ?>"<?php echo $style; ?>>
			<?php echo Text::_('EB_CAPTCHA'); ?><span class="required">*</span>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<?php echo $this->captcha; ?>
		</div>
	</div>
<?php
}
?>
	<div class="form-actions">
		<?php
			if (!$this->bypassNumberMembersStep)
			{
			?>
				<input type="button" id="btn-group-members-back" name="btn-group-members-back" class="<?php echo $btnPrimaryClass; ?>" value="<?php echo Text::_('EB_BACK'); ?>"/>
			<?php
			}
		?>
		<input type="<?php echo $this->showBillingStep ? 'button' : 'submit';?>" id="btn-process-group-members" name="btn-process-group-members" class="<?php echo $btnPrimaryClass; ?>" value="<?php echo $this->showBillingStep ? Text::_('EB_NEXT'): Text::_('EB_PROCESS_REGISTRATION'); ?>" />
	</div>
	<input type="hidden" name="task" value="<?php echo $this->showBillingStep ? 'register.validate_and_store_group_members_data' : 'register.store_group_members_data'; ?>" />
	<input type="hidden" name="event_id" value="<?php echo $this->eventId; ?>" />
	<input type="hidden" name="number_registrants" value="<?php echo $this->numberRegistrants; ?>" />
</form>
