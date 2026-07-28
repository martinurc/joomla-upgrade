<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

class RsformModelFormajax extends BaseDatabaseModel
{
	public static function sortFields($a,$b)
	{
		if ($a->Ordering == $b->Ordering) return 0;
		return ($a->Ordering < $b->Ordering) ? -1 : 1;
	}
	
	protected function getTooltip($name)
	{
		$tooltip = '';
		
		if (Factory::getLanguage()->hasKey('RSFP_COMP_FIELD_'.$name.'_DESC')) {
			$title = Text::_('RSFP_COMP_FIELD_'.$name);
			$content = Text::_('RSFP_COMP_FIELD_'.$name.'_DESC');
			$tooltip .= ' class="fieldHasTooltip" data-content="'. $content .'" data-title="' . $title . '"';
		}
		
		return $tooltip;
	}

	public function getComponentFields()
	{
		$db = Factory::getDbo();
		$lang = Factory::getLanguage();
		$return = array(
			'general'		=> array(),
			'validations' 	=> array(),
			'attributes' 	=> array(),
			'editor'		=> array()
		);
		$data = $this->getComponentData();

		$general		= array('NAME','CAPTION','LABEL','DEFAULTVALUE','ITEMS','DESCRIPTION','COMPONENTTYPE');
		$validations	= array('REQUIRED','VALIDATIONRULE','VALIDATIONMESSAGE','VALIDATIONEXTRA', 'VALIDATIONDATE');
		$editor			= array('TEXT');

		$componentId = $this->getComponentId();
		$componentType = $this->getComponentType();

		$query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__rsform_component_type_fields'))
            ->where($db->qn('ComponentTypeId') . ' = ' . $db->q($componentType))
            ->order($db->qn('Ordering') . ' ' . $db->escape('asc'));
		$results = $db->setQuery($query)->loadObjectList();

		$translatable = RSFormProHelper::getTranslatableProperties();

		foreach ($results as $i => $result)
		{
			if ($result->FieldName == 'ADDITIONALATTRIBUTES')
			{
				$results[$i]->Ordering = 1001;
			}
		}

		usort($results, array($this, 'sortFields'));

		$multilanguageDisabled = RSFormProHelper::getConfig('global.disable_multilanguage');
		$translateIcon = RSFormProHelper::translateIcon();

		foreach ($results as $result)
		{
			$field = new stdClass();
			$field->name = $result->FieldName;
			$field->caption = $lang->hasKey('RSFP_COMP_FIELD_'.$field->name) ? Text::_('RSFP_COMP_FIELD_'.$field->name) : $field->name;
			$field->label = '<label for="'.$field->name.'" id="caption' . $field->name.'" '.$this->getTooltip($field->name).'>'.$field->caption.'</label>';
			$field->body = '';
			$field->type = $result->FieldType;
			$field->translatable = !$multilanguageDisabled && in_array($result->FieldName, $translatable) && $result->FieldType != 'hiddenparam' && $result->FieldType != 'hidden';

			$additional = 'class="form-control"';

			switch ($result->FieldType)
			{
				case 'color':
				case 'textbox':
				{
					if ($componentId > 0)
						$value = isset($data[$field->name]) ? $data[$field->name] : '';
					else
					{
						$values = RSFormProHelper::isCode($result->FieldValues);

						if ($lang->hasKey('RSFP_COMP_FVALUE_'.$values))
							$value = Text::_('RSFP_COMP_FVALUE_'.$values);
						else
							$value = $values;
					}

					if ($result->Properties != ''){
						$additional .= ' data-properties="'. $result->Properties .'"';
					}

					$type = $result->FieldType == 'textbox' ? 'text' : 'color';

					$field->body .= '<input type="' . $type . '" id="'.$field->name.'" name="param['.$field->name.']" value="'.RSFormProHelper::htmlEscape($value).'" '.$additional.' />';

					if ($field->translatable)
					{
						$field->body = RSFormProAdapterGrid::inputAppend($field->body, $translateIcon);
					}
				}
					break;

				case 'textarea':
				{
					if ($componentId > 0)
					{
						if (!isset($data[$field->name]))
						{
							$data[$field->name] = '';
						}

						if ($lang->hasKey('RSFP_COMP_FVALUE_'.$data[$field->name]))
						{
							$value = Text::_('RSFP_COMP_FVALUE_'.$data[$field->name]);
						}
						else
						{
							$value = $data[$field->name];
						}
					}
					else
					{
						$values = RSFormProHelper::isCode($result->FieldValues);

						if ($lang->hasKey('RSFP_COMP_FVALUE_'.$values))
						{
							$value = Text::_('RSFP_COMP_FVALUE_'.$values);
						}
						else
						{
							$value = $values;
						}
					}

					if ($result->Properties != '')
					{
						$additional .= ' data-properties="'. $result->Properties .'"';
						$additional .= ' data-tags="' .RSFormProHelper::htmlEscape($value). '" ';
					}

					$field->body .= '<textarea id="'.$field->name.'" name="param['.$field->name.']" rows="5" cols="20" '. $additional .'>'.RSFormProHelper::htmlEscape($value).'</textarea>';

					if ($field->translatable)
					{
						$field->body = RSFormProAdapterGrid::inputAppend($field->body, $translateIcon);
					}
				}
					break;

				case 'select':
				case 'selectmultiple':
				{
					$additional = 'class="form-select"';

					/**
					 * determine if we have a json in the properties.
					 * used to create the conditional fields
					 * the JSON should have the following structure
					 * case -> value -> array (fields to be toggled)
					 */
					if (json_decode($result->Properties))
					{
						$additional .= ' data-toggle="' . RSFormProHelper::htmlEscape($result->Properties) . '" data-properties="toggler"';
					}
					
					// set the multiple attribute and select size if needed
					if ($result->FieldType == 'selectmultiple') {
						$additional .= ' size="5" multiple="multiple"';
					}

					if (in_array($field->name, array('VALIDATIONRULE', 'VALIDATIONMULTIPLE')))
                    {
                        $additional .=  'onchange="changeValidation(this);"';
                    }

					$field->body .= '<select name="param['.$field->name.']'.($result->FieldType == 'selectmultiple' ? '[]' : '').'" '. $additional .' id="'.$field->name.'">';

					if (!isset($data[$field->name]))
						$data[$field->name] = '';
						
					if ($result->FieldType == 'selectmultiple') {
						if(!empty($data[$field->name])) {
							$data[$field->name] = explode(',', $data[$field->name]);
						}
					}

					$result->FieldValues = str_replace("\r", '', $result->FieldValues);
					$items = RSFormProHelper::isCode($result->FieldValues);
					$items = explode("\n",$items);
					foreach ($items as $item)
					{
						$buf = explode('|', $item, 2);

						$option_value = $buf[0];
						$option_shown = count($buf) == 1 ? $buf[0] : $buf[1];

						if ($lang->hasKey('RSFP_COMP_FVALUE_'.$option_shown))
							$label = Text::_('RSFP_COMP_FVALUE_'.$option_shown);
						else
							$label = $option_shown;

						$field->body .= '<option '.($componentId > 0 && ((!is_array($data[$field->name]) && $data[$field->name] == $option_value) || (is_array($data[$field->name]) && in_array($option_value, $data[$field->name]))) ? 'selected="selected"' : '').' value="'.$option_value.'">'.RSFormProHelper::htmlEscape($label).'</option>';
					}
					$field->body .= '</select>';
				}
					break;

				case 'hidden':
				{
					$values = $result->FieldValues;
					if (defined('RSFP_COMP_FVALUE_'.$values))
						$value = constant('RSFP_COMP_FVALUE_'.$values);
					else
						$value = $values;

					$field->body = '<input type="hidden" id="'.$field->name.'" name="'.$field->name.'" value="'.RSFormProHelper::htmlEscape($value).'" />';
				}
					break;

				case 'hiddenparam':
					$field->body = '<input type="hidden" id="'.$field->name.'" name="param['.$field->name.']" value="'.RSFormProHelper::htmlEscape($result->FieldValues).'" />';
					break;
			}

			if (in_array($field->name, $general) || $result->FieldType == 'hidden' || $result->FieldType == 'hiddenparam')
				$return['general'][] = $field;
			elseif (in_array($field->name, $validations) || strpos($field->name, 'VALIDATION') !== false)
				$return['validations'][] = $field;
			elseif (in_array($field->name, $editor))
				$return['editor'][] = $field;
			else
				$return['attributes'][] = $field;
		}

		return $return;
	}

	public function getComponentData()
	{
		$componentId = $this->getComponentId();
		$data 		 = array();
		
		if ($componentId > 0)
		{
			$data = RSFormProHelper::getComponentProperties($componentId);
		}

		return $data;
	}

	public function getComponentType()
	{
		return Factory::getApplication()->input->get->getInt('componentType');
	}

	public function getComponentId()
	{
		$cid    = Factory::getApplication()->input->getInt('componentId');
		$cids   = Factory::getApplication()->input->get('cid', array(), 'array');
		if (is_array($cids) && count($cids)) {
			$cids = array_map('intval', $cids);
			$cid = $cids;
		}

		return $cid;
	}

	public function getComponent()
	{
		$componentId 		= $this->getComponentId();
		$return 	 		= new stdClass();
		$return->published 	= 1;

		if ($componentId)
		{
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->select($db->qn('Published'))
				->from($db->qn('#__rsform_components'))
				->where($db->qn('ComponentId') . ' = ' . $db->q($componentId));

			$return->published = $db->setQuery($query)->loadResult();
		}

		// required?
		$data = $this->getComponentData();
		if (isset($data['REQUIRED']))
		{
			$return->required = $data['REQUIRED'] == 'YES';
		}

		return $return;
	}

	public function componentsChangeStatus($published)
	{
		$componentId = $this->getComponentId();
		$db          = $this->getDbo();
		
		$query = $db->getQuery(true)
			->update($db->qn('#__rsform_components'))
			->set($db->qn('Published') . ' = ' . $db->q($published));
		
		if (is_array($componentId))
		{
			$query->where($db->qn('ComponentId') . ' IN (' . implode(',', $componentId) . ')');
		}
		else
		{
			$query->where($db->qn('ComponentId') . ' = ' . $db->q($componentId));
		}

		$db->setQuery($query)->execute();
	}

	public function componentsChangeRequired()
	{
		$componentId = $this->getComponentId();
		$task 		 = strtolower(Factory::getApplication()->input->getWord('task'));
		$required 	 = $task == 'componentssetrequired' ? 'YES' : 'NO';
		$db          = $this->getDbo();
		
		$query = $db->getQuery(true)
			->update($db->qn('#__rsform_properties'))
			->set($db->qn('PropertyValue') . ' = ' . $db->q($required))
			->where($db->qn('PropertyName') . ' = ' . $db->q('REQUIRED'));
		
		if (is_array($componentId))
		{
			$query->where($db->qn('ComponentId') . ' IN (' . implode(',', $componentId) . ')');
		}
		else
		{
			$query->where($db->qn('ComponentId') . ' = ' . $db->q($componentId));
		}

		$db->setQuery($query)->execute();
	}

	public function getPublished()
	{
		$component = $this->getComponent();

		return HTMLHelper::_('select.booleanlist', 'Published', '', $component->published);
	}
}