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

$translatable = $this->item->translatable && Multilanguage::isEnabled() && count($this->languages);

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
$tags   = EventbookingHelperHtml::getSupportedTags($this->item->name);
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal<?php if (!EventbookingHelper::isJoomla4()) echo ' joomla3'; ?>">
	<?php
	if ($translatable)
	{
		echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'mitem', ['active' => 'general-page', 'recall' => true]);
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'mitem', 'general-page', Text::_('EB_GENERAL'));
	}
	?>
	<div class="control-group">
		<div class="control-label">
			<?php
				echo EventbookingHelperHtml::getFieldLabel($this->item->name, Text::_($this->item->title), $this->item->description ? Text::_($this->item->description) : '');

				if (count($tags))
				{
					$availableTags = '[' . implode(']<br /> [', $tags) . ']';
				?>
                    <p class="eb-available-tags">
		                <?php echo Text::_('EB_AVAILABLE_TAGS'); ?>: <br /><strong><?php echo $availableTags; ?></strong>
                    </p>
                <?php
				}
			?>
		</div>
		<div class="controls">
            <?php
				if ($this->item->type == 'text')
				{
				?>
                    <input type="text" name="<?php echo $this->item->name; ?>" class="input-xxlarge form-control" value="<?php echo $this->escape($this->message->get($this->item->name)); ?>" />
                <?php
				}
				elseif ($this->item->type == 'textarea')
				{
				?>
                    <textarea name="<?php echo $this->item->name; ?>" class="input-xxlarge form-control" rows="10"><?php echo $this->message->get($this->item->name); ?></textarea>
                <?php
				}
				else
				{
					echo $editor->display($this->item->name, $this->message->get($this->item->name), '100%', '550', '90', '6');
				}
			?>
		</div>
	</div>
    <?php
		if ($translatable)
		{
			echo HTMLHelper::_($tabApiPrefix . 'endTab');
			echo HTMLHelper::_($tabApiPrefix . 'addTab', 'mitem', 'translation-page', Text::_('EB_TRANSLATION'));
			echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'mitem-translation',
				['active' => 'translation-page-' . $this->languages[0]->sef, 'recall' => true]);

			$rootUri = Uri::root(true);

			foreach ($this->languages as $language)
			{
				$sef       = $language->sef;
				$inputName = $this->item->name . '_' . $sef;
				echo HTMLHelper::_($tabApiPrefix . 'addTab', 'mitem-translation', 'translation-page-' . $sef, $language->title . ' <img src="' . $rootUri . '/media/mod_languages/images/' . $language->image . '.gif" />');
			?>
                <div class="control-group">
                    <div class="control-label">
	                    <?php
							echo EventbookingHelperHtml::getFieldLabel($inputName, Text::_($this->item->title), $this->item->description ? Text::_($this->item->description) : '');

							if (count($tags))
							{
								$availableTags = '[' . implode(']<br /> [', $tags) . ']';
								?>
                                <p class="eb-available-tags">
                                    <?php echo Text::_('EB_AVAILABLE_TAGS'); ?>:<br /> <strong><?php echo $availableTags; ?></strong>
                                </p>
                                <?php
							}
						?>
                    </div>
                    <div class="controls">
				        <?php
						if ($this->item->type == 'text')
						{
						?>
                            <input type="text" name="<?php echo $inputName; ?>" class="input-xxlarge form-control" value="<?php echo $this->escape($this->message->get($inputName)); ?>" />
					    <?php
						}
						elseif ($this->item->type == 'textarea')
						{
						?>
                            <textarea name="<?php echo $inputName; ?>" class="input-xxlarge form-control" rows="10"><?php echo $this->message->get($inputName); ?></textarea>
                        <?php
						}
						else
						{
							echo $editor->display($inputName, $this->message->get($inputName), '100%', '550', '90', '6');
						}
						?>
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