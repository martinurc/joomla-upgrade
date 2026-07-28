<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/field.php';

class RSFormProFieldButton extends RSFormProField
{
	protected $baseClass = 'rsform-button';
	
	// backend preview
	public function getPreviewInput()
	{
		$reset		= $this->getProperty('RESET', 'NO');
		$label		= $this->getProperty('LABEL', '');
		$resetLabel	= $this->getProperty('RESETLABEL', '');
		
		$html = '';
		$html .= '<button type="button" class="btn btn-secondary">'.$this->escape($label).'</button>';
		if ($reset) {
			$html .= '&nbsp;&nbsp;<button type="reset" class="btn btn-danger">'.$this->escape($resetLabel).'</button>';
		}
		
		return $html;
	}
	
	// functions used for rendering in front view
	public function getFormInput() {
		$name		= $this->getName();
		$id			= $this->getId();
		$label		= $this->getProperty('LABEL', '');
		$reset		= $this->getProperty('RESET', 'NO');
		$allowHtml	= $this->getProperty('ALLOWHTML', 'NO');
		$attr		= $this->getAttributes('button');
		$type 		= 'button';
		$additional = '';
		
		// Start building the HTML input
		$html = '<button';
		// Parse Additional Attributes
		if ($attr) {
			foreach ($attr as $key => $values) {
				// @new feature - Some HTML attributes (type) can be overwritten
				// directly from the Additional Attributes area
				if ($key == 'type' && strlen($values)) {
					${$key} = $values;
					continue;
				}
				$additional .= $this->attributeToHtml($key, $values);
			}
		}
		// Set the type
		$html .= ' type="'.$this->escape($type).'"';
		// Name & id
		$html .= ' name="'.$this->escape($name).'"'.
				 ' id="'.$this->escape($id).'"';
		// Additional HTML
		$html .= $additional;
		// Add the label & close the tag
		$html .= ' >' . ($allowHtml ? $label : $this->escape($label)) . '</button>';
		
		// Do we need to append a reset button?
		if ($reset) {
			$label	 	 = $this->getProperty('RESETLABEL', '');
			$attr	 	 = $this->getAttributes('reset');
			$additional  = '';
			$html 		.= ' ';
			
			// Parse Additional Attributes
			if ($attr) {
				foreach ($attr as $key => $values) {
					$additional .= $this->attributeToHtml($key, $values);
				}
			}
			
			// Start building the HTML input for the reset button
			$html .= '<button';
			// Set the type
			$html .= ' type="reset"';
			// Additional HTML
			$html .= $additional;
			// Add the label & close the tag
			$html .= ' >' . ($allowHtml ? $label : $this->escape($label)) . '</button>';
		}
		
		return $html;
	}
	
	// @desc All buttons should have a class for easy styling
	public function getAttributes($type='button') {
		$attr = parent::getAttributes();
		if (strlen($attr['class'])) {
			$attr['class'] .= ' ';
		}
		if ($type == 'button') {
			$attr['class'] .= $this->baseClass;
		} elseif ($type == 'reset') {
			$attr['class'] .= 'rsform-reset-button';
			$attr['onclick'] = 'RSFormPro.resetElements('.$this->formId.')';
		} elseif ($type == 'previous') {
			$attr['class'] .= 'rsform-button-prev';
			if (!isset($attr['onclick'])) {
				$attr['onclick'] = '';
			} else {
				$attr['onclick'] = rtrim($attr['onclick'], ';');
			}
		}
		
		return $attr;
	}
	
	// @desc Gets the previous button so it can be attached next to the last Submit button
	// 		 Handles last button so that this doesn't show up unless this is the last button.
	protected function getPreviousButton() {
		$componentId = $this->componentId;
		$pages 		 = RSFormProHelper::componentExists($this->formId, RSFORM_FIELD_PAGEBREAK);
		$class 		 = substr(get_class($this), -strlen('SubmitButton'));
		
		if ($class == 'SubmitButton') {
			$buttons 	 = $this->getProperty('SUBMITS', array());
			$last		 = $componentId == end($buttons);
		}
			
		$html = '';
		if (($class == 'SubmitButton' && $pages && $last) || ($class != 'SubmitButton' && count($pages) > 1)) {
			$label  	= $this->getProperty('PREVBUTTON', Text::_('JPREV'));
			$label		= empty($label) ? Text::_('JPREV') : $label;
			$allowHtml	= $this->getProperty('ALLOWHTML', 'NO');
			$id			= $this->getId();
			$attr		= $this->getAttributes('previous');
			$additional = '';
			$totalPages = count($pages);
			$prevPage	= $totalPages - 1;
			$formId		= $this->formId;
			
			// Parse Additional Attributes
			if ($attr) {
				if (strlen($attr['onclick'])) {
					$attr['onclick'] .= ';';
				}
				$attr['onclick'] .= "rsfp_changePage($formId, $prevPage, $totalPages)";				
				foreach ($attr as $key => $values) {
					$additional .= $this->attributeToHtml($key, $values);
				}
			}
			
			// Start building the HTML input
			$html = '<button';
			// Set the type
			$html .= ' type="button"';
			// Id
			$html .= ' id="'.$this->escape($id).'Prev"';
			// Additional HTML
			$html .= $additional;
			// Add the label & close the tag
			$html .= ' >' . ($allowHtml ? $label : $this->escape($label)) . '</button>';
		}
		
		return $html;
	}
}