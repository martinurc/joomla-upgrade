<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

class RsformController extends BaseController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		HTMLHelper::_('jquery.framework');
		HTMLHelper::_('behavior.core');

        HTMLHelper::_('script', 'com_rsform/admin/placeholders.js', array('relative' => true, 'version' => 'auto'));
        HTMLHelper::_('script', 'com_rsform/admin/script.js', array('relative' => true, 'version' => 'auto'));
        HTMLHelper::_('script', 'com_rsform/admin/jquery.tag-editor.js', array('relative' => true, 'version' => 'auto'));
        HTMLHelper::_('script', 'com_rsform/admin/jquery.caret.min.js', array('relative' => true, 'version' => 'auto'));
        HTMLHelper::_('script', 'com_rsform/admin/validation.js', array('relative' => true, 'version' => 'auto'));
        HTMLHelper::_('script', 'com_rsform/admin/tablednd.js', array('relative' => true, 'version' => 'auto'));

        HTMLHelper::_('stylesheet', 'com_rsform/admin/style.css', array('relative' => true, 'version' => 'auto'));
        HTMLHelper::_('stylesheet', 'com_rsform/admin/jquery.tag-editor.css', array('relative' => true, 'version' => 'auto'));
        HTMLHelper::_('stylesheet', 'com_rsform/rsicons.css', array('relative' => true, 'version' => 'auto'));

		if (version_compare(JVERSION, '5.0', '>='))
		{
			HTMLHelper::_('stylesheet', 'com_rsform/admin/style50.css', array('relative' => true, 'version' => 'auto'));
		}
		if (version_compare(JVERSION, '4.0', '>='))
		{
			HTMLHelper::_('stylesheet', 'com_rsform/admin/style40.css', array('relative' => true, 'version' => 'auto'));
			HTMLHelper::_('script', 'com_rsform/admin/script40.js', array('relative' => true, 'version' => 'auto'));

			Factory::getDocument()->addScriptDeclaration("RSFormPro.isJ4 = true;");
		}
		else
		{
			HTMLHelper::_('stylesheet', 'com_rsform/admin/style30.css', array('relative' => true, 'version' => 'auto'));

			Factory::getDocument()->addScriptDeclaration("RSFormPro.isJ4 = false;");
		}

		if (RSFormProHelper::getConfig('global.disable_multilanguage'))
		{
			Factory::getDocument()->addStyleDeclaration(".rsfp-translate-icon:before { content: ''; }");
		}
	}

	public function layoutsGenerate()
	{
		/* @var $model RsformModelForms */

		$model = $this->getModel('forms');
		$model->getForm();
		$model->_form->FormLayoutName = Factory::getApplication()->input->getCmd('layoutName');
		$model->autoGenerateLayout();

		echo $model->_form->FormLayout;
		exit();
	}

	public function layoutsSaveName()
	{
		$formId = Factory::getApplication()->input->getInt('formId');
		$name 	= Factory::getApplication()->input->getCmd('formLayoutName');

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__rsform_forms'))
			->set($db->qn('FormLayoutName') . ' = ' . $db->q($name))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));
		$db->setQuery($query)->execute();

		exit();
	}

	public function plugin()
	{
		Factory::getApplication()->triggerEvent('onRsformBackendSwitchTasks');
	}
}