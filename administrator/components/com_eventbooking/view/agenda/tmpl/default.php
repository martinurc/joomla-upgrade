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

if (!EventbookingHelper::isJoomla4())
{
	HTMLHelper::_('formbehavior.chosen', 'select');
}

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

$editor = Editor::getInstance(Factory::getApplication()->get('editor'));
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal<?php if (!EventbookingHelper::isJoomla4()) echo ' joomla3'; ?>">
    <?php
	if ($translatable)
	{
		echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'agenda', ['active' => 'general-page', 'recall' => true]);
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'agenda', 'general-page', Text::_('EB_GENERAL'));
	}
	?>
	<div class="control-group">
		<div class="control-label">
            <?php echo  Text::_('EB_EVENT'); ?>
		</div>
		<div class="controls">
            <?php echo $this->lists['event_id']; ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
            <?php echo  Text::_('EB_TITLE'); ?>
		</div>
		<div class="controls">
			<input class="form-control" type="text" name="title" id="title" size="50" maxlength="250" value="<?php echo $this->item->title;?>" />
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
            <?php echo  Text::_('EB_TIME'); ?>
		</div>
		<div class="controls">
			<input class="form-control" type="text" name="time" id="time" size="50" maxlength="250" value="<?php echo $this->item->time;?>" />
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
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'agenda', 'translation-page', Text::_('EB_TRANSLATION'));
		echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'agenda-translation',
			['active' => 'translation-page-' . $this->languages[0]->sef, 'recall' => true]);

		$rootUri = Uri::root(true);

		foreach ($this->languages as $language)
		{
			$sef = $language->sef;
			echo HTMLHelper::_($tabApiPrefix . 'addTab', 'agenda-translation', 'translation-page-' . $sef, $language->title . ' <img src="' . $rootUri . '/media/mod_languages/images/' . $language->image . '.gif" />');
			?>
			<div class="control-group">
				<div class="control-label">
                    <?php echo  Text::_('EB_TITLE'); ?>
				</div>
				<div class="controls">
					<input class="input-xxlarge form-control" type="text" name="title_<?php echo $sef; ?>" id="title_<?php echo $sef; ?>" maxlength="250" value="<?php echo $this->item->{'title_' . $sef}; ?>" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
                    <?php echo Text::_('EB_DESCRIPTION'); ?>
				</div>
				<div class="controls">
                    <?php echo $editor->display('description_' . $sef, $this->item->{'description_' . $sef}, '100%', '250', '75', '10'); ?>
				</div>
			</div>
            <?php
			echo HTMLHelper::_($tabApiPrefix . 'endTab');
		}

		echo HTMLHelper::_($tabApiPrefix . 'endTabSet');
	}
	?>

	<div class="clearfix"></div>
    <?php echo HTMLHelper::_('form.token'); ?>
	<input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>"/>
	<input type="hidden" name="task" value=""/>
</form>
