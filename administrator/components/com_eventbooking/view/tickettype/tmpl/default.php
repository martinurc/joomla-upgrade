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
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal<?php if (!EventbookingHelper::isJoomla4()) echo ' joomla3'; ?>">
	<?php
	if ($translatable)
	{
		echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'tickettype', ['active' => 'general-page', 'recall' => true]);
		echo HTMLHelper::_($tabApiPrefix . 'addTab', 'tickettype', 'general-page', Text::_('EB_GENERAL'));
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
	            <?php echo  Text::_('EB_PRICE'); ?>
			</div>
			<div class="controls">
				<input class="form-control" type="text" name="price" id="price" size="50" maxlength="250" value="<?php echo $this->item->price;?>" />
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
	            <?php echo  Text::_('EB_CAPACITY'); ?>
			</div>
			<div class="controls">
				<input class="form-control" type="number" step="1" name="capacity" id="capacity" size="50" maxlength="250" value="<?php echo $this->item->capacity;?>" />
			</div>
		</div>
		<?php
			if ($this->pluginParams->get('enable_weight')) {
			?>
				<div class="control-group">
					<div class="control-label">
                        <?php echo  Text::_('EB_WEIGHT'); ?>
					</div>
					<div class="controls">
						<input class="form-control" type="number" step="1" name="weight" id="weight" size="50" maxlength="250" value="<?php echo $this->item->weight;?>" />
					</div>
				</div>
			<?php
			}
		?>
		<div class="control-group">
			<div class="control-label">
				<?php echo  Text::_('EB_MIN_TICKETS_PER_BOOKING'); ?>
			</div>
			<div class="controls">
				<input class="form-control" type="number" step="1" name="min_tickets_per_booking" id="min_tickets_per_booking" size="50" maxlength="250" value="<?php echo $this->item->min_tickets_per_booking;?>" />
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
	            <?php echo  Text::_('EB_MAX_TICKETS_PER_BOOKING'); ?>
			</div>
			<div class="controls">
				<input class="form-control" type="number" step="1" name="max_tickets_per_booking" id="max_tickets_per_booking" size="50" maxlength="250" value="<?php echo $this->item->max_tickets_per_booking;?>" />
			</div>
		</div>
	    <?php
		if ($this->pluginParams->get('enable_discount_rules')) {
		?>
			<div class="control-group">
				<div class="control-label">
	                <?php echo  Text::_('EB_DISCOUNT_RULES'); ?>
				</div>
				<div class="controls">
					<input class="form-control" type="text" name="discount_rules" id="discount_rules" size="50" maxlength="250" value="<?php echo $this->item->discount_rules;?>" />
				</div>
			</div>
	    <?php
		}
		?>
		<div class="control-group">
			<div class="control-label">
	            <?php echo Text::_('EB_PUBLISH_UP'); ?>
			</div>
			<div class="controls">
	            <?php echo HTMLHelper::_('calendar', $this->item->publish_up, 'publish_up', 'publish_up', $this->datePickerFormat . ' %H:%M', ['showTime' => true]); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
	            <?php echo Text::_('EB_PUBLISH_DOWN'); ?>
			</div>
			<div class="controls">
	            <?php echo HTMLHelper::_('calendar', $this->item->publish_down, 'publish_down', 'publish_down', $this->datePickerFormat . ' %H:%M', ['showTime' => true]); ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
	            <?php echo  Text::_('EB_ACCESS'); ?>
			</div>
			<div class="controls">
	            <?php echo $this->item->access; ?>
			</div>
		</div>
		<div class="control-group">
			<div class="control-label">
	            <?php echo  Text::_('EB_DESCRIPTION'); ?>
			</div>
			<div class="controls">
				<input class="form-control" type="text" name="description" id="description" maxlength="250" value="<?php echo $this->item->description;?>" />
			</div>
		</div>
    <?php
		if ($translatable)
		{
			echo HTMLHelper::_($tabApiPrefix . 'endTab');
			echo HTMLHelper::_($tabApiPrefix . 'addTab', 'tickettype', 'translation-page', Text::_('EB_TRANSLATION'));
			echo HTMLHelper::_($tabApiPrefix . 'startTabSet', 'tickettype-translation',
				['active' => 'translation-page-' . $this->languages[0]->sef, 'recall' => true]);

			$rootUri = Uri::root(true);

			foreach ($this->languages as $language)
			{
				$sef = $language->sef;
				echo HTMLHelper::_($tabApiPrefix . 'addTab', 'tickettype-translation', 'translation-page-' . $sef, $language->title . ' <img src="' . $rootUri . '/media/mod_languages/images/' . $language->image . '.gif" />');
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
						<input class="input-xxlarge form-control" type="text" name="description_<?php echo $sef; ?>" id="description_<?php echo $sef; ?>" maxlength="250" value="<?php echo $this->item->{'description_' . $sef}; ?>" />
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