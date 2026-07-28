<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsformControllerFiles extends RsformController
{
	public function display($cachable = false, $urlparams = false) {
		Factory::getApplication()->input->set('view', 'files');
		Factory::getApplication()->input->set('layout', 'default');
		
		parent::display($cachable, $urlparams);
	}
	
	public function upload() {
		// Check for request forgeries
		$this->checkToken();

		// Get the model
		$model  = $this->getModel('files');
		
		$folder = $model->getCurrent();
		$result = $model->upload();
		$file 	= $model->getUploadFile();
		
		if ($result) {
			$msg = Text::sprintf('COM_RSFORM_SUCCESSFULLY_UPLOADED', $file);
			$this->setMessage($msg);
		} else {
            $msg = Text::sprintf('COM_RSFORM_FAILED_TO_UPLOAD_IN', $file, $folder);
			$this->setMessage($msg, 'error');
		}
		
		$this->setRedirect('index.php?option=com_rsform&controller=files&task=display&folder='.urlencode($folder).'&tmpl=component');
	}
}