<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\FormField', 'JFormField');
}

require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

class JFormFieldDirectorystatus extends FormField
{
	public function getInput()
	{
		$formId = Factory::getApplication()->input->getInt('formId');

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->qn('formId'))
			->from($db->qn('#__rsform_directory'))
			->where($db->qn('formId') . ' = ' . $db->q($formId));

		$status = $db->setQuery($query)->loadResult();

		if ($status)
		{
			return '<span class="badge bg-success badge-success">' . Text::_('RSFP_SUBM_DIR_ENABLED') . '</span>';
		}
		else
		{
			return '<span class="badge badge-important bg-danger">' . Text::_('RSFP_SUBM_DIR_DISABLED') . '</span><p><small>' . Text::_('RSFP_SUBM_DIR_DISABLED_INSTRUCTIONS') . '</small></p>';
		}
	}
}
