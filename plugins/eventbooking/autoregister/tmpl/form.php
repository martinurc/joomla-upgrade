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

/**
 * Layout variables
 *
 * @var int    $autoRegisterAllChildrenEvents
 * @var string $eventIds
 */
?>
<div class="control-group">
	<label class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel(
			'auto_register_event_ids',
			Text::_('EB_AUTO_REGISTER_EVENT_IDS'),
			Text::_('EB_AUTO_REGISTER_EVENT_IDS_EXPLAIN')
		);
		?>
	</label>
	<div class="controls">
		<input class="form-control input-large" type="text" name="auto_register_event_ids" id="auto_register_event_ids"
		       size="" maxlength="250" value="<?php
		echo $eventIds; ?>"/>
	</div>
</div>
<div class="control-group">
	<label class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel(
			'auto_register_all_children_events',
			Text::_('EB_AUTO_REGISTER_ALL_CHILDREN_EVENTS'),
			Text::_('EB_AUTO_REGISTER_ALL_CHILDREN_EVENTS_EXPLAIN')
		);
		?>
	</label>
	<div class="controls">
		<?php echo EventbookingHelperHtml::getBooleanInput('auto_register_all_children_events', $autoRegisterAllChildrenEvents); ?>
	</div>
</div>
