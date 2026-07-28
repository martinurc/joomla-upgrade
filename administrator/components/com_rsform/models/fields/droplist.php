<?php
/**
* @package RSForm! Pro
* @copyright (C) 2015 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-3.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

FormHelper::loadFieldClass('list');

class JFormFieldDroplist extends ListField
{
	protected $type = 'Droplist';

	protected function getInput()
	{
		HTMLHelper::_('jquery.framework');

		$document = Factory::getDocument();
		HTMLHelper::_('stylesheet', 'com_rsform/admin/droplist.css', array('relative' => true, 'version' => 'auto'));
		HTMLHelper::_('script', 'com_rsform/admin/droplist.js', array('relative' => true, 'version' => 'auto'));

		$document->addScriptDeclaration('
		jQuery(document).ready(function ($) {
			$(\'#' . $this->id . '\').ddslick({
				onSelected: function(data) {
					if ($(document.getElementsByName(\''.$this->name.'\')).length == 0) {
						$(\'#' . $this->id . '\').append(\'<input type="hidden" name="'.$this->name.'" />\');
					}
					$(document.getElementsByName(\''.$this->name.'\')[0]).val(data.selectedData.value);
				}
			});
			
			$(\'#' . $this->id . ' .dd-option\').hover(function() {
				$(this).children(\':last\').addClass(\'custom-tooltip\');
				$(this).children(\':last\').offset({ top: $(this).offset().top - 15, left: $(this).offset().left + 50 });
			}, function() {
				$(this).children(\':last\').removeClass(\'custom-tooltip\');
			});
		});
		');
		
		return parent::getInput();
	}
}