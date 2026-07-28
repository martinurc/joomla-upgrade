<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

Text::script('ERROR');
Text::script('RSFP_CONDITION_PLEASE_ADD_OPTIONS');
Text::script('RSFP_CONDITION_IS');
Text::script('RSFP_CONDITION_IS_NOT');
Text::script('COM_RSFORM_CONDITION_PLEASE_SELECT_AT_LEAST_ONE_FIELD');
Text::script('COM_RSFORM_CONDITION_PLEASE_ADD_AT_LEAST_ONE_CONDITION');

if (version_compare(JVERSION, '4.0', '<'))
{
    HTMLHelper::_('formbehavior.chosen', '.advancedSelect');
}
HTMLHelper::_('script', 'com_rsform/admin/conditions.js', array('relative' => true, 'version' => 'auto'));

$this->document->addScriptDeclaration('if (window.opener && window.opener.showConditions) { window.opener.showConditions(); }');
if ($this->close)
{
	$this->document->addScriptDeclaration('window.close();');
}
$this->document->addScriptDeclaration('function getConditionOptionFields() { return ' . json_encode($this->optionFields) . '; }');
?>
<?php if (!RSFormProHelper::getConfig('global.disable_multilanguage')) { ?>
    <p><?php echo Text::sprintf('RSFP_YOU_ARE_EDITING_CONDITIONS_IN', $this->escape($this->lang)); ?></p>
<?php } ?>
<form name="adminForm" id="adminForm" method="post" action="index.php">
	<div id="conditionsContainer">
	<p>
		<button class="btn btn-success" onclick="Joomla.submitbutton('apply');" type="button"><?php echo Text::_('JAPPLY'); ?></button>
		<button class="btn btn-success" onclick="Joomla.submitbutton('save');" type="button"><?php echo Text::_('JSAVE'); ?></button>
		<button class="btn btn-secondary" onclick="window.close();" type="button"><?php echo Text::_('JCANCEL'); ?></button>
	</p>

	<div class="d-flex flex-row justify-content-between">
		<?php echo Text::sprintf('RSFP_SHOW_FIELD_IF_THE_FOLLOWING_MATCH', $this->lists['action'], $this->lists['block'], $this->lists['allfields'], $this->lists['condition']); ?> <a class="btn btn-primary" href="javascript: void(0);" onclick="addCondition();"><i class="rsficon rsficon-plus"></i></a>
	</div>
	<?php if ($this->condition->details) { ?>
		<?php foreach ($this->condition->details as $detail) { ?>
		<p>
			<?php echo HTMLHelper::_('select.genericlist', $this->optionFields, 'detail_component_id[]', array('class' => 'form-select d-inline-block w-auto'), 'id', 'name', $detail->component_id); ?>
			<span class="rsform_spacer">&nbsp;</span>
			<?php echo HTMLHelper::_('select.genericlist', $this->operators, 'operator[]', array('class' => 'form-select d-inline-block w-auto'), 'value', 'text', $detail->operator); ?>
			<span class="rsform_spacer">&nbsp;</span>
			<select name="value[]" class="form-select d-inline-block w-auto">
			<?php foreach ($this->optionFields as $field) { ?>
                <?php if ($field->id != $detail->component_id) continue; ?>
                <?php foreach ($field->items as $item) { ?>
                    <option <?php if ($item->value == $detail->value) { ?>selected="selected"<?php } ?> value="<?php echo $this->escape($item->value); ?>"><?php echo $this->escape($item->label); ?></option>
                <?php } ?>
			<?php } ?>
			</select>
			<span class="rsform_spacer">&nbsp;</span>
			<button type="button" class="btn btn-danger btn-mini" onclick="this.parentNode.parentNode.removeChild(this.parentNode);"><i class="rsficon rsficon-remove"></i></button>
		</p>
		<?php } ?>
	<?php } ?>
	</div>
	
	<?php echo HTMLHelper::_('form.token'); ?>
	<input type="hidden" name="option" value="com_rsform" />
	<input type="hidden" name="controller" value="conditions" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="formId" value="<?php echo $this->formId; ?>" />
	<input type="hidden" name="form_id" value="<?php echo $this->formId; ?>" />
	<input type="hidden" name="cid" value="<?php echo (int) $this->condition->id; ?>" />
	<input type="hidden" name="id" value="<?php echo (int) $this->condition->id; ?>" />
	<input type="hidden" name="lang_code" value="<?php echo $this->escape($this->lang); ?>" />
</form>