<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


JLoader::register('JHtmlUsers', JPATH_COMPONENT . '/helpers/html/users.php');
JHtml::register('users.spacer', array('JHtmlUsers', 'spacer'));

$this->user=JsnHelper::getUser($this->data->id);

$fieldsets = $this->form->getFieldsets();
if (isset($fieldsets['core']))   unset($fieldsets['core']);
if (isset($fieldsets['params'])) unset($fieldsets['params']);
if (isset($fieldsets['privacyconsent'])) unset($fieldsets['privacyconsent']);
if (isset($fieldsets['actionlogs'])) unset($fieldsets['actionlogs']);
if (JFactory::getUser()->id != $this->data->id && isset($fieldsets['profile'])) unset($fieldsets['profile']);

/* Add field to exclude (these fields are in other template position) */
$this->excludeFromProfile[]='jform[avatar]';
$this->excludeFromProfile[]='jform[registerdate]';
$this->excludeFromProfile[]='jform[lastvisitdate]';
/* ---- */

/* J 3.7+ - Custom Fields */
$tmp          = isset($this->data->jcfields) ? $this->data->jcfields : array();
$customFields = array();

foreach ($tmp as $customField)
{
	$customFields[$customField->name] = $customField;
}
/* ---- */

foreach ($fieldsets as $group => $fieldset): // Iterate through the form fieldsets
	$fields = $this->form->getFieldset($group);
	$count=0;
	$empty=array();
	foreach ($fields as $field)
	{
		if(!$field->hidden && !in_array($field->name,$this->excludeFromProfile))
		{
			if($field->value=='' && $this->config->get('hideempty',0)) $empty[]=$field->name; 
			else $count+=1;
		}
	}
	if ($count) : 
?>
<fieldset id="<?php echo $group;?>">
	<?php if (isset($fieldset->label)): // If the fieldset has a label set, display it as the legend.?>
	<legend><?php echo JText::_($fieldset->label); ?></legend>
	<?php endif;?>
	<dl class="dl-horizontal">
	<?php foreach ($fields as $field):
		if (!$field->hidden && !in_array($field->name,$empty) && !in_array($field->name,$this->excludeFromProfile)) :?>
		<dt class="<?php echo $field->fieldname; ?>Label"><?php echo $field->title; ?></dt>
		<dd class="<?php echo $field->fieldname; ?>Value"><?php 
			if (key_exists($field->fieldname, $customFields))
				echo $customFields[$field->fieldname]->value ?: JText::_('COM_USERS_PROFILE_VALUE_NOT_FOUND');
			else
				echo $this->user->getField($field,false);
		?></dd>
		<?php endif;?>
	<?php endforeach;?>
	</dl>
</fieldset>
	<?php endif;?>
<?php endforeach;?>
