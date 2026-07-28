<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/button.php';

class RSFormProFieldSubmitButton extends RSFormProFieldButton
{
	// backend preview
	public function getPreviewInput()
	{
		$reset		= $this->getProperty('RESET', 'NO');
		$label		= $this->getProperty('LABEL', '');
		$resetLabel	= $this->getProperty('RESETLABEL', '');
		$html 		= '';

		$html .= '<button type="button" class="btn btn-primary">'.$this->escape($label).'</button>';
		if ($reset) {
			$html .= '&nbsp;&nbsp;<button type="reset" class="btn btn-danger">'.$this->escape($resetLabel).'</button>';
		}
		
		return $html;
	}
	
	// functions used for rendering in front view
	public function getFormInput() {
		// Change the base CSS class
		// Each button type (button, submit, image) needs a different class
		$this->baseClass = 'rsform-submit-button';
		
		$name		= $this->getName();
		$id			= $this->getId();
		$label		= $this->getProperty('LABEL', '');
		$reset		= $this->getProperty('RESET', 'NO');
		$allowHtml	= $this->getProperty('ALLOWHTML', 'NO');
		$attr		= $this->getAttributes('button');
		$type 		= $this->getProperty('INPUTTYPE', 'submit');
		$additional = '';
		$html 		= '';
		
		// Handle pages
		$html .= $this->getPreviousButton();
		
		// Start building the HTML input
		$html .= '<button';
		
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
}