<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.core');

if (!EventbookingHelper::isJoomla4())
{
	HTMLHelper::_('formbehavior.chosen', 'select');
}

Factory::getApplication()->getDocument()->addScript(Uri::root(true) . '/media/com_eventbooking/js/admin-tax-default.min.js');

$keys = ['EB_ENTER_TAX_RATE'];
EventbookingHelperHtml::addJSStrings($keys);
?>
<form action="<?php echo $this->getFormAction(); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_CATEGORY'); ?>
		</div>
		<div class="controls">
			<?php echo $this->lists['category_id']; ?>
		</div>
	</div>
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
			<?php echo  Text::_('EB_COUNTRY'); ?>
		</div>
		<div class="controls">
			<?php echo $this->lists['country']; ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo  Text::_('EB_STATE'); ?>
		</div>
		<div class="controls">
			<?php echo $this->lists['state']; ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_TAX_RATE'); ?>
		</div>
		<div class="controls">
			<input class="form-control" type="number" name="rate" id="rate" size="5" maxlength="250" value="<?php echo $this->item->rate;?>" /> %
		</div>
	</div>
	<?php
		if (isset($this->lists['vies']))
		{
		?>
		<div class="control-group">
			<div class="control-label">
				<?php echo Text::_('EB_VIES'); ?>
			</div>
			<div class="controls">
				<?php echo $this->lists['vies'];?>
			</div>
		</div>
		<?php
		}
	?>
	<div class="control-group">
		<div class="control-label">
			<?php echo Text::_('EB_PUBLISHED'); ?>
		</div>
		<div class="controls">
			<?php echo $this->lists['published']; ?>
		</div>
	</div>
	<div class="clearfix"></div>
	<?php echo HTMLHelper::_('form.token'); ?>
    <input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>"/>
	<input type="hidden" name="task" value="" />
</form>