<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\File;

class RsformModelFiles extends BaseDatabaseModel
{
	protected $_folder = null;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->_folder = JPATH_SITE;

		$folder = Factory::getApplication()->input->getString('folder');
		if ($folder !== null && is_dir($folder))
		{
			$folder = rtrim($folder, '\\/');
			$this->_folder = $folder;
		}
	}
	
	public function getFolders() {
		$return = array();
		
		$folders = Folder::folders($this->_folder);
		foreach ($folders as $folder) {
			$return[] = (object) array(
				'name' 		=> $folder,
				'fullpath' 	=> $this->_folder . DIRECTORY_SEPARATOR . $folder
			);
		}
		
		return $return;
	}
	
	public function getFiles() {
		$return = array();
		
		$files = Folder::files($this->_folder);
		foreach ($files as $file) {
			$return[] = (object) array(
				'name' 		=> $file,
				'fullpath' 	=> $this->_folder . DIRECTORY_SEPARATOR . $file
			);
		}
		
		return $return;
	}
	
	public function getElements()
	{
		$elements = explode(DIRECTORY_SEPARATOR, $this->_folder);
		$navigation_path = '';
		
		if(!empty($elements))
			foreach($elements as $i=>$element)
			{
				$navigation_path .= $element;
				$newelement = new stdClass();
				$newelement->name = $element;
				$newelement->fullpath = $navigation_path;
				$elements[$i] = $newelement;
				$navigation_path .= DIRECTORY_SEPARATOR;
			}
		
		return $elements;
	}
	
	public function getCurrent() {
		return $this->_folder;
	}
	
	public function getPrevious() {
		$elements = explode(DIRECTORY_SEPARATOR, $this->_folder);
		if (count($elements) > 1)
			array_pop($elements);
		return implode(DIRECTORY_SEPARATOR, $elements);
	}
	
	public function upload() {
	    $upload = Factory::getApplication()->input->files->get('upload');
		if (!$upload['error'])
		{
			try
			{
				return File::upload($upload['tmp_name'], $this->getCurrent() . '/' . $upload['name']);
			}
			catch (Exception $e)
			{
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				return false;
			}
        }

		return false;
	}
	
	public function getCanUpload() {
		return is_writable($this->_folder);
	}
	
	public function getUploadFile() {
        $upload = Factory::getApplication()->input->files->get('upload');
		
		return $upload['name'];
	}
}