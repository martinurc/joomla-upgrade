<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('text');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\TextField', 'JFormFieldText');
}

class JFormFieldEmailattachment extends TextField
{
	protected function getInput()
	{
		$html 	= parent::getInput();
		$file 	= $this->value;
		$folder = $file && file_exists($file) ? '&folder=' . urlencode(dirname($file)) : '';
		$url 	= Route::_('index.php?option=com_rsform&controller=files&task=display&tmpl=component' . $folder);
		$html  .= '<a href="' . $url . '" onclick="openRSModal(this.href); return false;" class="btn btn-secondary"><span class="rsficon rsficon-file-text-o"></span> ' . Text::_('RSFP_SELECT_FILE') . '</a>';

		if ($file && !file_exists($file))
		{
			$html .= '<div class="alert alert-danger">' . Text::_('RSFP_EMAILS_ATTACH_FILE_WARNING') . '</div>';
		}

		return $html;
	}
}
