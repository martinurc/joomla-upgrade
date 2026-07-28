<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\FormField', 'JFormField');
}

class JFormFieldSubmissionvalues extends FormField
{
	public function getInput()
	{
		Text::script('COM_RSFORM_OPERATOR_IS');
		Text::script('COM_RSFORM_OPERATOR_IS_NOT');
		Text::script('COM_RSFORM_OPERATOR_CONTAINS');
		Text::script('COM_RSFORM_OPERATOR_CONTAINS_NOT');
		Text::script('COM_RSFORM_OPERATOR_STARTS');
		Text::script('COM_RSFORM_OPERATOR_STARTS_NOT');
		Text::script('COM_RSFORM_OPERATOR_ENDS');
		Text::script('COM_RSFORM_OPERATOR_ENDS_NOT');
		Text::script('COM_RSFORM_OPERATOR_GREATER_THAN');
		Text::script('COM_RSFORM_OPERATOR_GREATER_OR_EQUAL');
		Text::script('COM_RSFORM_OPERATOR_LESS_THAN');
		Text::script('COM_RSFORM_OPERATOR_LESS_OR_EQUAL');
		Text::script('COM_RSFORM_REMOVE_VALUE');
		Text::script('COM_RSFORM_REMOVE_VALUE_SURE');
		Text::script('COM_RSFORM_FIELD_NAME');
		Text::script('COM_RSFORM_FIELD_VALUE');

		Factory::getDocument()->addScriptDeclaration(
			"var addField = function (storedName, storedOperator, storedValue) {
	var option;

	// Grab container
	var container = document.getElementById('{$this->id}_container');
	var spacer_one = document.createTextNode(' ');
	var spacer_two = document.createTextNode(' ');
	var spacer_three = document.createTextNode(' ');
	
	// Create name input 
	var name = document.createElement('input');
	name.setAttribute('name', '{$this->name}[name][]');
	name.setAttribute('type', 'text');
	name.setAttribute('placeholder', Joomla.JText._('COM_RSFORM_FIELD_NAME'));
	if (storedName)
	{
		name.setAttribute('value', storedName);
	}
	
	// Create operator select
	var operator = document.createElement('select');
	operator.setAttribute('name', '{$this->name}[operator][]');
	operator.setAttribute('style', 'width: auto;');
	
	var options = ['is', 'is_not', 'contains', 'contains_not', 'starts', 'starts_not', 'ends', 'ends_not', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal'];
	
	for (var i = 0; i < options.length; i++)
	{
		option = document.createElement('option');
		option.value = options[i];
		option.text = Joomla.JText._('COM_RSFORM_OPERATOR_' + options[i].toUpperCase());
		
		if (storedOperator && option.value === storedOperator)
		{
			option.selected = true;
		}
		
		operator.options.add(option);		
	}
	
	// Create value input
	var value = document.createElement('input');
	value.setAttribute('name', '{$this->name}[value][]');
	value.setAttribute('type', 'text');
	value.setAttribute('placeholder', Joomla.JText._('COM_RSFORM_FIELD_VALUE'));
	if (storedValue)
	{
		value.setAttribute('value', storedValue);
	}
	
	// Create remove button
	var button = document.createElement('button');
	button.setAttribute('type', 'button');
	button.setAttribute('class', 'btn btn-secondary btn-small btn-sm');
	button.setAttribute('onclick', 'deleteField(this);');
	button.innerText = Joomla.JText._('COM_RSFORM_REMOVE_VALUE');
	
	// Create row containing these
	var row = document.createElement('p');
	
	// Append elements to DOM
	row.appendChild(name);
	row.appendChild(spacer_one);
	row.appendChild(operator);
	row.appendChild(spacer_two);
	row.appendChild(value);
	row.appendChild(spacer_three);
	row.appendChild(button);
	container.appendChild(row);
};

var deleteField = function (element) {
	if (confirm(Joomla.JText._('COM_RSFORM_REMOVE_VALUE_SURE')))
	{
		var container = document.getElementById('{$this->id}_container');
		container.removeChild(element.parentNode);
	}
};
");

		if ($this->value && is_array($this->value))
		{
			Factory::getDocument()->addScriptDeclaration("document.addEventListener('DOMContentLoaded', function() {
	var storedValues = " . json_encode($this->value) . ";
	
	var names 		= storedValues.hasOwnProperty('name') ? storedValues.name : []; 
	var operators 	= storedValues.hasOwnProperty('operator') ? storedValues.operator : []; 
	var values 		= storedValues.hasOwnProperty('value') ? storedValues.value : []; 
	
	var name, operator, value;
	for (var i = 0; i < names.length; i++)
	{
		name = names[i];
		operator = typeof operators[i] != 'undefined' ? operators[i] : null;
		value = typeof values[i] != 'undefined' ? values[i] : null;
		
		if (name !== null && operator !== null && value !== null)
		{
			addField(name, operator, value);
		}
	}
});");
		}

		$html = '<p><button type="button" onclick="addField();" class="btn btn-primary">' . Text::_('COM_RSFORM_ADD_VALUE') . '</button></p>';
		$html .= '<div id="' . $this->id . '_container">';
		$html .= '</div>';

		return $html;
	}
}
