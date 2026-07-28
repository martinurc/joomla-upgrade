<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var \Joomla\CMS\Editor\Editor $editor
 */

if (EventbookingHelper::isJoomla4())
{
	$tabApiPrefix = 'uitab.';
}
else
{
	$tabApiPrefix = 'bootstrap.';
}

$rootUri = Uri::root(true);

echo HTMLHelper::_($tabApiPrefix . 'addTab', 'speaker', 'translation-page', Text::_('EB_TRANSLATION'));
echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'speaker-translation',
	['active' => 'translation-page-' . $this->languages[0]->sef, 'recall' => true]);

foreach ($this->languages as $language)
{
	$sef = $language->sef;
	echo HTMLHelper::_($tabApiPrefix . 'addTab', 'speaker-translation', 'translation-page-' . $sef, $language->title . ' <img src="' . $rootUri . '/media/mod_languages/images/' . $language->image . '.gif" />');
	?>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_NAME'); ?>
		</div>
		<div class="controls">
			<input class="input-xlarge form-control" type="text" name="name_<?php echo $sef; ?>" id="name_<?php echo $sef; ?>" size="" maxlength="250" value="<?php echo $this->item->{'name_' . $sef}; ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_TITLE'); ?>
		</div>
		<div class="controls">
			<input class="input-xlarge form-control" type="text" name="title_<?php echo $sef; ?>" id="title_<?php echo $sef; ?>" size="" maxlength="250" value="<?php echo $this->item->{'title_' . $sef}; ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_URL'); ?>
		</div>
		<div class="controls">
			<input class="input-xlarge form-control" type="url" name="url_<?php echo $sef; ?>" id="url_<?php echo $sef; ?>" size="" maxlength="250" value="<?php echo $this->item->{'url_' . $sef}; ?>"/>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_DESCRIPTION'); ?>
		</div>
		<div class="controls">
			<?php echo $editor->display('description_' . $sef, $this->item->{'description_' . $sef}, '100%', '400', '75', '10'); ?>
		</div>
	</div>

	<?php
	echo HTMLHelper::_($tabApiPrefix . 'endTab');
}
echo HTMLHelper::_($tabApiPrefix . 'endTabSet');
echo HTMLHelper::_($tabApiPrefix . 'endTab');
