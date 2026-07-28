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

class JFormFieldFiltervalues extends FormField
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
		Text::script('COM_RSFORM_FIELD_VALUES');

		Factory::getDocument()->addScriptDeclaration(
			"var addDynamicField = function (storedName, storedOperator, storedValue) {
	var option;

	// Grab container
	var container = document.getElementById('{$this->id}_container');
	var column_one = document.createElement('td');
	var column_two = document.createElement('td');
	var column_three = document.createElement('td');
	var column_four = document.createElement('td');
	
	// Create name input 
	var name = document.createElement('input');
	name.classList.add('form-control');
	name.setAttribute('name', '{$this->name}[name][]');
	name.setAttribute('type', 'text');
	name.setAttribute('placeholder', Joomla.JText._('COM_RSFORM_FIELD_NAME'));
	if (storedName)
	{
		name.setAttribute('value', storedName);
	}
	column_one.appendChild(name);
	
	// Create operator select
	var operator = document.createElement('select');
	operator.classList.add('form-select');
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
	column_two.appendChild(operator);
	
	// Create value input
	var value = document.createElement('textarea');
	value.classList.add('form-control');
	value.setAttribute('name', '{$this->name}[value][]');
	value.setAttribute('placeholder', Joomla.JText._('COM_RSFORM_FIELD_VALUES'));
	if (storedValue)
	{
		value.value = storedValue;
	}
	column_three.appendChild(value);
	
	// Create remove button
	var button = document.createElement('button');
	button.setAttribute('type', 'button');
	button.setAttribute('class', 'btn btn-secondary btn-small btn-sm');
	button.setAttribute('onclick', 'deleteDynamicField(this);');
	button.innerText = Joomla.JText._('COM_RSFORM_REMOVE_VALUE');
	column_four.appendChild(button);
	
	// Create row containing these
	var row = document.createElement('tr');
	
	// Append elements to DOM
	row.appendChild(column_one);
	row.appendChild(column_two);
	row.appendChild(column_three);
	row.appendChild(column_four);
	container.appendChild(row);
};

var deleteDynamicField = function (element) {
	if (confirm(Joomla.JText._('COM_RSFORM_REMOVE_VALUE_SURE')))
	{
		var container = document.getElementById('{$this->id}_container');
		container.removeChild(element.parentNode.parentNode);
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
			addDynamicField(name, operator, value);
		}
	}
});");
		}

		$html = '<p><button type="button" onclick="addDynamicField();" class="btn btn-primary">' . Text::_('COM_RSFORM_ADD_VALUE') . '</button></p>' .
		'<table class="table table-striped table-align-middle">' .
			'<thead>' .
				'<tr>' .
					'<th>' . Text::_('COM_RSFORM_FIELD_NAME') . ' </th>' .
					'<th>' . Text::_('COM_RSFORM_OPERATOR') . ' </th>' .
					'<th>' . Text::_('COM_RSFORM_LIST_OF_OPTIONS') . ' </th>' .
					'<th></th>' .
				'</tr>' .
			'</thead>' .
			'<tbody id="' . $this->id . '_container"></tbody>' .
		'</table>';

		return $html;
	}
}
