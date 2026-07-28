<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.core');
HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['html' => true, 'sanitize' => false]);

if (!EventbookingHelper::isJoomla4())
{
	HTMLHelper::_('formbehavior.chosen', 'select');
}

$document = Factory::getApplication()->getDocument();
$document->addStyleDeclaration('.hasTip{display:block !important}')
	->addScript(Uri::root(true) . '/media/com_eventbooking/js/admin-speaker-default.min.js');

$translatable = Multilanguage::isEnabled() && count($this->languages);

if (EventbookingHelper::isJoomla4())
{
	$tabApiPrefix = 'uitab.';
}
else
{
	HTMLHelper::_('behavior.tabstate');

	$tabApiPrefix = 'bootstrap.';
}

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$span6           = $bootstrapHelper->getClassMapping('span6');

$languageKeys = [
	'EB_ENTER_SPEAKER_NAME',
];

EventbookingHelperHtml::addJSStrings($languageKeys);

$editor = Editor::getInstance(Factory::getApplication()->get('editor'));
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<?php
	if ($translatable)
	{
		echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'speaker', ['active' => 'general-page', 'recall' => true]);
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'speaker', 'general-page', Text::_('EB_GENERAL'));
	}
	?>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_EVENTS'); ?>
		</div>
		<div class="controls">
			<?php echo EventbookingHelperHtml::getChoicesJsSelect($this->lists['event_id'], Text::_('EB_TYPE_OR_SELECT_SOME_EVENTS')) ; ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_NAME'); ?>
		</div>
		<div class="controls">
			<input type="text" name="name" id="name" class="form-control" size="50" maxlength="250" value="<?php echo $this->item->name;?>" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_TITLE'); ?>
		</div>
		<div class="controls">
			<input type="text" name="title" id="title" class="form-control" size="50" maxlength="250" value="<?php echo $this->item->title;?>" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_AVATAR'); ?>
		</div>
		<div class="controls">
			<?php echo EventbookingHelperHtml::getMediaInput($this->item->avatar, 'avatar'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_URL'); ?>
		</div>
		<div class="controls">
			<input class="form-control" type="url" name="url" id="url" size="50" maxlength="250" value="<?php echo $this->item->url;?>" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_DESCRIPTION'); ?>
		</div>
		<div class="controls">
			<?php echo $editor->display('description', $this->item->description, '100%', '250', '75', '10') ; ?>
		</div>
	</div>
	<?php
	if ($translatable)
	{
		echo HTMLHelper::_($tabApiPrefix . 'endTab');
		echo $this->loadTemplate('translation', ['editor' => $editor]);
		echo HTMLHelper::_($tabApiPrefix . 'endTabSet');
	}
	?>
	<input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>"/>
	<input type="hidden" name="task" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>