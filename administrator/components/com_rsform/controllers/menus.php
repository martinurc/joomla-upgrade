<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class RsformControllerMenus extends RsformController
{
	public function cancelForm()
	{
		$app 	= Factory::getApplication();
		$formId = $app->input->getInt('formId');

		$app->redirect('index.php?option=com_rsform&view=forms&layout=edit&formId='.$formId);
	}

	public function cancel()
	{
		Factory::getApplication()->redirect('index.php?option=com_rsform&view=forms');
	}
}