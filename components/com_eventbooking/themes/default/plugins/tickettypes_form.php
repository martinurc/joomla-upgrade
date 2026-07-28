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
 * @var stdClass $row
 * @var \Joomla\Registry\Registry
 */

if ($row->event_type == 1)
{
?>
    <div class="control-group">
        <div class="control-label">
			<?php echo EventbookingHelperHtml::getFieldLabel('update_ticket_types_to_children_events', Text::_('EB_UPDATE_CHANGE_TO_CHILDREN_EVENTS'), Text::_('EB_UPDATE_CHANGE_TO_CHILDREN_EVENTS_EXPLAIN')); ?>
        </div>
        <div class="controls">
			<?php echo EventbookingHelperHtml::getBooleanInput('update_ticket_types_to_children_events', $params->get('update_ticket_types_to_children_events', 1)); ?>
        </div>
    </div>
<?php
}
?>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('ticket_types_collect_members_information', Text::_('EB_COLLECT_MEMBERS_INFORMATION'), Text::_('EB_COLLECT_MEMBERS_INFORMATION_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<?php echo EventbookingHelperHtml::getBooleanInput('ticket_types_collect_members_information', $collectMembersInformation); ?>
	</div>
</div>
<div class="control-group">
    <div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('only_allow_register_one_ticket_type', Text::_('EB_ONLY_ALLOW_REGISTERING_ONE_TICKET_TYPE'), Text::_('EB_ONLY_ALLOW_REGISTERING_ONE_TICKET_TYPE_EXPLAIN')); ?>
    </div>
    <div class="controls">
		<?php echo EventbookingHelperHtml::getBooleanInput('only_allow_register_one_ticket_type', $params->get('only_allow_register_one_ticket_type', 0)); ?>
    </div>
</div>
<div class="row-fluid eb-ticket-types-container">
	<?php
	foreach ($form->getFieldset() as $field)
	{
		echo $field->input;
	}
	?>
</div>
