<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Router\Route;

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\FormField', 'JFormField');
}

class JFormFieldRichtext extends FormField
{
	protected function getInput()
	{
		$text 		= Text::_($this->element['button']);
		$opener 	= $this->name;
		$formId 	= $this->form->getValue('FormId');
		$url 		= 'index.php?option=com_rsform&task=richtext.%s&opener=%s&formId=%d&tmpl=component';
		$edit 		= "var url = '" . sprintf($url, 'show', urlencode($opener), $formId) . "'; ";

		if ($this->element['mode-toggler'])
		{
			$modeId = (string) $this->element['mode-toggler'];

			$edit .= "if (jQuery('[name={$modeId}]:checked').val() === '0') { url += '&noEditor=1'; } ";
		}
		$edit .= "openRSModal(url);";

		$preview = "openRSModal('" . Route::_(sprintf($url, 'preview', urlencode($opener), $formId)) . "', 'RichtextPreview')";

		return '<button class="btn btn-secondary" onclick="' . htmlspecialchars($edit, ENT_COMPAT, 'utf-8') . '" type="button"><span class="rsficon rsficon-pencil-square"></span><span class="inner-text">' . $text . '</span></button>
		<button class="btn btn-secondary" onclick="' . $preview . '" type="button"><span class="rsficon rsficon-eye"></span><span class="inner-text">' . Text::_('RSFP_PREVIEW') . '</span></button>';
	}
}
