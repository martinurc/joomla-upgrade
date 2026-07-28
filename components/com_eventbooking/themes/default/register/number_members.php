<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/* @var  $this EventbookingViewRegisterHtml */

if ($this->fieldSuffix && EventbookingHelper::isValidMessage($this->message->{'number_members_form_message' . $this->fieldSuffix}))
{
	$msg = $this->message->{'number_members_form_message' . $this->fieldSuffix};
}
else
{
	$msg = $this->message->number_members_form_message;
}

$msg        = str_replace('[MIN_NUMBER_REGISTRANTS]', $this->minNumberRegistrants, $msg);
$msg        = str_replace('[MAX_NUMBER_REGISTRANTS]', $this->maxRegistrants, $msg);

$replaces = EventbookingHelperRegistration::buildEventTags($this->event, $this->config);

$msg = EventbookingHelper::replaceUpperCaseTags($msg, $replaces);

$bootstrapHelper     = $this->bootstrapHelper;
$controlGroupClass   = $bootstrapHelper->getClassMapping('control-group');
$controlLabelClass   = $bootstrapHelper->getClassMapping('control-label');
$controlsClass       = $bootstrapHelper->getClassMapping('controls');
$btnPrimaryClass     = $bootstrapHelper->getClassMapping('btn btn-primary');

if ($this->config->get('form_layout') == 'stacked')
{
	$formClass = $bootstrapHelper->getClassMapping('form');
}
else
{
	$formClass = $bootstrapHelper->getClassMapping('form form-horizontal');
}

if (strlen($msg))
{
?>
	<div class="eb-message"><?php echo HTMLHelper::_('content.prepare', $msg); ?></div>
<?php
}
?>
<form name="eb-form-number-group-members" id="eb-form-number-group-members" autocomplete="off" class="<?php echo $formClass; ?>">
	<div class="<?php echo $controlGroupClass; ?>">
		<label class="<?php echo $controlLabelClass; ?>" for="number_registrants">
			<?php echo  Text::_('EB_NUMBER_REGISTRANTS') ?><span class="required">*</span>
		</label>
		<div class="<?php echo $controlsClass; ?>">
			<input type="number" class="form-control input-medium validate[required,custom[number],min[<?php echo $this->minNumberRegistrants; ?>],max[<?php echo $this->maxRegistrants; ?>]"
				id="number_registrants" name="number_registrants" value="<?php echo $this->numberRegistrants;?>"
				data-errormessage-range-underflow="<?php echo Text::sprintf('EB_NUMBER_REGISTRANTS_IN_VALID', $this->minNumberRegistrants); ?>"
				data-errormessage-range-overflow="<?php echo Text::sprintf('EB_MAX_REGISTRANTS_REACH', $this->maxRegistrants);?>"
				step="1" min="<?php echo $this->minNumberRegistrants ?>" max="<?php echo $this->maxRegistrants; ?>" />
		</div>
	</div>
	<div class="form-actions">
		<input type="button" name="btn-number-members-back" id="btn-number-members-back" class="<?php echo $btnPrimaryClass; ?>" value="<?php echo Text::_('EB_BACK'); ?>" onclick="window.history.go(-1) ;" />
		<input type="button" name="btn-process-number-members" id="btn-process-number-members" class="<?php echo $btnPrimaryClass; ?>" value="<?php echo Text::_('EB_NEXT'); ?>" />
	</div>
</form>
