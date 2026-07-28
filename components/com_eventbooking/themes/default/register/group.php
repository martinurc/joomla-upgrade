<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('calendar', '', 'id', 'name');
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['html' => true, 'sanitize' => false]);
Factory::getApplication()->getDocument()->addStyleDeclaration('.hasTip{display:block !important}');

EventbookingHelperJquery::validateForm();

if (EventbookingHelper::isJoomla4())
{
	$containerClass = ' eb-container-j4';
}
else
{
	$containerClass = '';
}
?>
<div id="eb-group-registration-form" class="eb-container<?php echo $containerClass; ?><?php echo $this->waitingList ? ' eb-waitinglist-group-registration-form' : '';?> eb-group-registration-form-<?php echo $this->event->id; ?>">
	<?php
	if ($this->params->get('show_page_heading', 1))
	{
		if ($this->input->getInt('hmvc_call'))
		{
			$hTag = 'h2';
		}
		else
		{
			$hTag = 'h1';
		}
	?>
		<<?php echo $hTag; ?> class="eb-page-title"><?php echo $this->pageHeading; ?></<?php echo $hTag; ?>>
	<?php
	}

	if (strlen($this->formMessage))
	{
	?>
		<div class="eb-message"><?php echo HTMLHelper::_('content.prepare', $this->formMessage);  ?></div>
	<?php
	}

	if (!$this->bypassNumberMembersStep)
	{
	?>
		<div id="eb-number-group-members">
			<div class="eb-form-heading">
				<?php echo Text::_('EB_NUMBER_MEMBERS'); ?>
			</div>
			<div class="eb-form-content">

			</div>
		</div>
	<?php
	}

	if ($this->collectMemberInformation)
	{
	?>
		<div id="eb-group-members-information">
			<div class="eb-form-heading">
				<?php echo Text::_('EB_MEMBERS_INFORMATION'); ?>
			</div>
			<div class="eb-form-content"></div>
		</div>
	<?php
	}

	if($this->showBillingStep)
	{
	?>
		<div id="eb-group-billing">
			<div class="eb-form-heading">
				<?php echo Text::_('EB_BILLING_INFORMATION'); ?>
			</div>
			<div class="eb-form-content">

			</div>
		</div>
	<?php
	}

	EventbookingHelperHtml::addOverridableScript('media/com_eventbooking/js/site-register-group.min.js', ['version' => EventbookingHelper::getInstalledVersion()]);

	// Prevent session lost
	HTMLHelper::_('behavior.keepalive');
	?>
</div>