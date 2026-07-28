<?php
/**
 * @name		Framework over Joomla for CK
 * @copyright	Copyright (C) 2023. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @author		Cedric Keiflin - https://www.template-creator.com - https://www.joomlack.fr - https://www.ceikay.com
 */

namespace Cookiesck;

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Plugin\PluginHelper;
use Cookiesck\CKInput;
use Cookiesck\CKText;

const EXTENSION_NAME = 'cookiesck';
const ADMIN_PATH = JPATH_ROOT . '/administrator/components/com_' . EXTENSION_NAME;
const BASE_PATH = JPATH_BASE . '/components/com_' . EXTENSION_NAME;
const BASE_URL = '/index.php?option=com_' . EXTENSION_NAME;
const ADMIN_GENERAL_URL = '/administrator/index.php?option=com_' . EXTENSION_NAME;

/**
 * CK Development Framework Layer
 */
class CKFof {

	static $keepMessages = false;

	static protected $environment = 'com_' . EXTENSION_NAME; // for joomla only

	static protected $application;

	static protected $document;

	static protected $input;

	public static function getApplication() {
		if (empty(self::$application)) {
			return \Joomla\CMS\Factory::getApplication();
		}
	}

	public static function getDocument() {
		if (empty(self::$document)) {
			return \Joomla\CMS\Factory::getDocument();
		}
	}

	public static function getInput() {
		$app = CKFof::getApplication();
		if (empty(self::$input)) {
			if (JVERSION >= 6) {
				self::$input = $app->getInput();
			} else {
				self::$input = $app->input;
			}
		}
		return self::$input;
	}

	public static function userCan($task, $environment = false) {
		$environment = $environment ? $environment : self::$environment;
		$user = CKFof::getUser();
		switch ($task) {
			case 'edit' :
				// return current_user_can('edit_plugins');
				return $user->authorise('core.edit', $environment);
			break;
			case 'create' :
				// return current_user_can('manage_options');
				return $user->authorise('core.create', $environment);
			break;
			case 'manage' :
				// return current_user_can('manage_options');
				return $user->authorise('core.manage', $environment);
			break;
			case 'admin' :
				// return current_user_can('manage_options');
				return $user->authorise('core.admin', $environment);
			break;
			default :
				// return current_user_can('edit_plugins');
				return $user->authorise($task, $environment);
		}
	}

	public static function getUser($id = 0) {
		if (JVERSION >= 6) {
			if ($id) {
				// Get the UserFactory 
				$userFactory = Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class);
				// to get User object for user with id = 99
				$user = $userFactory->loadUserById($id);
				return $user;
			}
			return Factory::getApplication()->getIdentity();

		} else {
			if ($id) {
				return Factory::getUser($id);
			}
			return Factory::getUser();
		}
	}

	public static function isAdmin() {
		return CKFof::getApplication()->isClient('administrator') ;
	}

	public static function isSite() {
		return CKFof::getApplication()->isClient('site') ;
	}

	// public static function _die($msg = '') {
		// $msg = $msg ? $msg : CKText::_('JERROR_ALERTNOAUTHOR');
		// die($msg);
	// }

	public static function getCurrentUri() {
//		$uri = \Joomla\CMS\Factory::getURI();
		$uri = \Joomla\CMS\Uri\Uri::getInstance();
		return $uri->toString();
	}

	public static function redirect($url = '', $msg = '', $type = '') {
		if (! $url) {
			$url = self::getCurrentUri();
		}
		if ($msg) {
			self::enqueueMessage($msg, $type);
		}
		CKFof::getApplication()->redirect($url);
		// If the headers have been sent, then we cannot send an additional location header
		// so we will output a javascript redirect statement.
		/*if (headers_sent())
		{
			self::$keepMessages = true;
			echo "<script>document.location.href='" . str_replace("'", '&apos;', $url) . "';</script>\n";
		}
		else
		{
			self::$keepMessages = true;
			// All other browsers, use the more efficient HTTP header method
			header('HTTP/1.1 303 See other');
			header('Location: ' . $url);
			header('Content-Type: text/html; charset=UTF-8');
		}*/
	}

	public static function enqueueMessage($msg, $type = 'message') {
		// add the information message
		CKFof::getApplication()->enqueueMessage(CKText::_($msg), $type);
	}

	public static function displayMessages() {
		// manage the information messages
		// not needed in joomla
	}

	public static function getToken($name = '') {
		return \Joomla\CMS\Factory::getSession()->getFormToken() . '=1';
	}

	public static function renderToken($name = '') {
		if (class_exists('JHtml')) {
			echo \JHtml::_('form.token');
		} else {
			echo \Joomla\CMS\HTML\HTMLHelper::_('form.token');
		}
	}

	public static function checkToken($token = '') {
		if (! CKSession::checkToken()) {
			$msg = CKText::_('Invalid token');
			die($msg);
		}
	}

	public static function checkAjaxToken($json = true) {
		// check the token for security
		if (! CKSession::checkToken('get')) {
			$msg = CKText::_('JINVALID_TOKEN');
			if ($json === false) {
				die($msg);
			}
			echo '{"result": "0", "message": "' . $msg . '"}';
			exit;
		}
		return true;
	}

	public static function getDbo() {
		return \Joomla\CMS\Factory::getDbo();
	}

	// For SQL identifier : table name for example
	public static function dbQuoteName($name) {
		$db = self::getDbo();
		return $db->quoteName($name);
	}

	// for SQL value
	public static function dbQuote($name) {
		$db = self::getDbo();
		return $db->quote($name);
	}

	public static function dbLoadObjectList($query) {
		$db = self::getDbo();
		// $query = $db->getQuery(true);
		$db->setQuery($query);
		$results = $db->loadObjectList();

		return $results;
	}

	public static function dbLoadObject($query) {
		$db = self::getDbo();
		$db->setQuery($query);
		$results = $db->loadObject();

		return $results;
	}

	public static function dbLoadResult($query) {
		$db = self::getDbo();
		// $query = $db->getQuery(true);
		$db->setQuery($query);
		$result = $db->loadResult();

		return $result;
	}

	public static function dbExecute($query) {
		$db = self::getDbo();
		$db->setQuery($query);
		$result = $db->execute();

		return $result;
	}

	public static function dbLoadColumn($tableName, $column) {
		$db = self::getDbo();
		$query = $db->getQuery(true);
		$query->select($column);
		$query->from($tableName);

		$db->setQuery($query);
		$result = $db->loadColumn();

		return $result;
	}

	public static function dbCheckTableExists($tableName) {
		$db = self::getDbo();
		$tablesList = $db->getTableList();

		$tableName = str_replace('#__', $db->getPrefix(), $tableName);
		$tableExists = in_array($tableName, $tablesList);
		return $tableExists;
	}

	public static function dbLoadTable($tableName) {
		$db = self::getDbo();
		$tableName = self::getTableName($tableName);
		$query = "DESCRIBE  " . $tableName;
		$db->setQuery($query);
		$columns = $db->loadObjectList();

		$table = new \stdClass();
		foreach ($columns as $col) {
			$table->{$col->Field} = '';
		}

		return $table;
	}

	public static function dbLoad($tableName, $id) {
		// if no existing row, then load empty table
		if ($id == 0) return self::dbLoadTable($tableName);

		$db = self::getDbo();
		$query = "SELECT * FROM " . $tableName . " WHERE id = " . (int)$id;
		$db->setQuery($query);
		$result = $db->loadAssoc();

		if (! $result) return self::dbLoadTable($tableName);

		$result = self::convertArrayToObject($result);

		return $result;
	}

	public static function dbBindData($table, $data) {
		if (is_object($table)) $table = self::convertObjectToArray($table);
		if (is_object($data)) $data = self::convertObjectToArray($data);

		foreach ($table as $col => $val) {
			if (isset($data[$col])) $table[$col] = $data[$col];
		}

		return $table;
	}

	public static function getTableName($tableName) {
		return $tableName;
	}

	public static function getTableStructure($tableName) {
		$db = self::getDbo();
		$query = "SHOW COLUMNS FROM " . $tableName;
		$db->setQuery($query);

		return $db->loadObjectList('Field');
	}

	public static function dbStore($tableName, $data, $format = array()) {
		$db = self::getDbo();
		if (is_object($data)) $data = self::convertObjectToArray($data);

		// Create a new query object.
		$query = $db->getQuery(true);
		$columsData = self::getTableStructure($tableName);

		if ((int)$data['id'] === 0) {
			$columns = array();
			$values = array();
			$fields = self::dbLoadTable($tableName);


			foreach($fields as $key => $val) {
				$columns[] = $key;
				if (isset($data[$key]) && !empty($data[$key])) {
					$values[] = is_numeric($data[$key]) ? $data[$key] : $db->quote($data[$key]);
				} else {
					if (strpos($columsData[$key]->Type, 'int') === 0 || strpos($columsData[$key]->Type, 'tinyint') === 0) {
						$values[] = 0;
					} else if (strpos($columsData[$key]->Type, 'date') === 0) {
						$values[] = $db->quote('0000-00-00 00:00:00');
//						array_pop($columns);
					} else {
						$values[] = $db->quote('');
					}
				}
			}

			// Prepare the insert query.
			$query
				->insert($db->quoteName($tableName))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));

			// Set the query using our newly populated query object and execute it.
			$db->setQuery($query);
			if ($db->execute()) {
				$id = $db->insertid();
			} else {
				return false;
			}
		} else {
			// Fields to update.
			$fields = self::dbLoadTable($tableName);
			$fieldsToInsert = array();
			foreach($fields as $key => $val) {
				if (strpos($columsData[$key]->Type, 'date') === 0 && empty($data[$key])) {
					continue;
				}
				if (isset($data[$key])) {
					$value = is_numeric($data[$key]) ? (int)$data[$key] : $db->quote($data[$key]);
				} else {
					continue;
				}
				$fieldsToInsert[] = $db->quoteName($key) . ' = ' . $value;
			}
			// Conditions for which records should be updated.
			$conditions = array(
				$db->quoteName('id') . ' = ' . $data['id']
			);

			$query->update($db->quoteName($tableName))->set($fieldsToInsert)->where($conditions);

			// Set the query using our newly populated query object and execute it.
			$db->setQuery($query);
			if ($db->execute()) {
				$id = $data['id'];
			} else {
				return false;
			}
		}

		return $id;
	}

	public static function dbUpdate($tableName, $id, $fields) {
		// Create a new query object.
		$db = self::getDbo();
		$query = $db->getQuery(true);

		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = ' . $id
		);

		$fieldsToInsert = array();
		foreach ($fields as $key => $value) {
			$fieldsToInsert[] = $db->quoteName($key) . ' = ' . $value;
		}

		$query->update($db->quoteName($tableName))->set($fieldsToInsert)->where($conditions);

		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);
		if ($result = $db->execute()) {
			return true;
		} else {
			return false;
		}
	}

	public static function dbDelete($tableName, $id) {
		$db = CKFof::getDbo();

		$query = $db->getQuery(true);

		$conditions = array(
			$db->quoteName('id') . ' = ' . (int) $id
//			, $db->quoteName('profile_key') . ' = ' . $db->quote('custom.%')
		);

		$query->delete($db->quoteName($tableName));
		$query->where($conditions);

		$db->setQuery($query);

		$result = $db->execute();

		return $result;
	}

	public static function convertObjectToArray($data) {
		return (array) $data;
	}

	public static function convertArrayToObject(array $array, $class = 'stdClass', $recursive = true)
	{
		$obj = new $class;

		foreach ($array as $k => $v)
		{
			if ($recursive && is_array($v))
			{
				$obj->$k = static::toObject($v, $class);
			}
			else
			{
				$obj->$k = $v;
			}
		}

		return $obj;
	}

	public static function dump($anything){
			echo "<pre>";
				var_dump($anything);
			echo "</pre>";
	}

	public static function print_r($anything){
		echo "<pre>";
				print_r($anything);
			echo "</pre>";
	}

	public static function addToolbarTitle($title, $image = '') {
		\Joomla\CMS\Toolbar\ToolbarHelper::title($title, $image);
	}

	public static function getToolbar() {
		// Get the toolbar object instance
		$bar = \Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
		return $bar;
	}

	public static function addToolbarButton($name, $html, $id) {
		$bar = self::getToolbar();
		$bar->appendButton($name, $html, $id);
	}

	public static function addToolbarPreferences() {
		$bar = self::getToolbar();
		// add the options of the component
		if (self::userCan('admin')) {
			if (class_exists('JToolBarHelper')) {
				\JToolBarHelper::preferences(self::$environment);
			} else {
				$bar->preferences(self::$environment);
			}
		}
	}

	private static function getFileName($file) {
		$f = explode('/', $file);
		$fileName = end($f);
		$f = explode('.', $fileName);
		$ext = end($f);
		$fileName = str_replace('.' . $ext, '', $fileName);

		return $fileName;
	}

	public static function addScriptDeclaration($js) {
		echo '<script>' . $js . '</script>';
	}

	public static function addScriptDeclarationInline($js) {
		echo '<script>' . $js . '</script>';
	}

	public static function addScript($file) {
		$doc = \Joomla\CMS\Factory::getDocument();
		$doc->addScript($file);
	}

	public static function addScriptInline($file) {
		echo '<script src="' . $file . '"></script>';
	}

	public static function addStyleDeclaration($css) {
		$doc = \Joomla\CMS\Factory::getDocument();
		$doc->addStyleDeclaration($css);
	}

	public static function addStyleDeclarationInline($css) {
		echo '<style>' . $css . '</style>';
	}

	public static function addStylesheet($file) {
		$doc = \Joomla\CMS\Factory::getDocument();
		$doc->addStylesheet($file);
	}

	public static function addStylesheetInline($file) {
		echo '<link href="' . $file . '"" rel="stylesheet" />';
	}

	public static function error($msg, $code = '') {
		throw new \Exception($msg, $code);
	}

	public static function raiseWarning($code, $msg) {
		throw new \Exception($msg, $code);
	}

	public static function triggerEvent($name, $e = array()) {
		if (version_compare(JVERSION,'4') < 1) {
			$dispatcher = \JEventDispatcher::getInstance();
			return $dispatcher->trigger($name, $e);
		} else {
			return CKFof::getApplication()->triggerEvent($name, $e);
		}
	}

	public static function importPlugin($group) {
		if (version_compare(JVERSION,'4') < 1) {
			\JPluginHelper::importPlugin($group);
		} else {
			\Joomla\CMS\Plugin\PluginHelper::importPlugin($group);
		}
	}

	public static function getModel($modelName, $classPrefix = '') {
		$classPrefix = $classPrefix ? $classPrefix : ucfirst(EXTENSION_NAME);
		include_once ADMIN_PATH . '/helpers/ckmodel.php';
		return CKModel::getInstance($modelName, $classPrefix);
	}

	public static function getUserState($name, $default = '', $type = 'string') {
		$session = \Joomla\CMS\Factory::getSession();
		$state = $session->get(self::$environment . '.' . $name, $default);

		return $state;
	}

	public static function setUserState($name, $value) {
		$session = \Joomla\CMS\Factory::getSession();
		$session->set(self::$environment . '.' . $name, $value);
	}

	public static function getSession($name, $default = null) {
		$namespace = self::$environment;

		if (isset($_SESSION[$namespace][$name]))
		{
			return $_SESSION[$namespace][$name];
		}
		return $default;
	}

	public static function setSession($name, $value) {
		$namespace = self::$environment;

		$old = isset($_SESSION[$namespace][$name]) ? $_SESSION[$namespace][$name] : null;

		if ($value === null)
		{
			unset($_SESSION[$namespace][$name]);
		}
		else
		{
			$_SESSION[$namespace][$name] = $value;
		}

		return $old;
	}

	public static function _($text) {
		return CKText::_($text);
	}

	public static function base($full = false) {
		return CKUri::base($full);
	}

	public static function root($full = false) {
		return CKUri::root($full);
	}

	public static function jquery() {
		if (version_compare(JVERSION, '5') >= 0) {
			$wa = \Joomla\CMS\Factory::getDocument()->getWebAssetManager();
			$wa->useScript('jquery');
		} else {
			\JHtml::_('jquery.framework', true);
		}
	}
}




/*************************************
*** Classes for the MVC			   ***
**************************************/


class CKController {

	protected $input;

	protected $model;

	protected $name;

	protected $prefix;

	protected $view;

	protected $redirect;

	protected static $instance;

	protected static $views;

	function __construct() {
		$this->input = CKFof::getInput();
	}

	static function getInstance($prefix) {
		if (is_object(self::$instance))
		{
			return self::$instance;
		}

		// Check for a controller.task command.
		$input = CKFof::getInput();
		$cmd = $input->get('task', '', 'cmd');
		if (strpos($cmd, '.') !== false)
		{
			// Explode the controller.task command.
			list ($name, $task) = explode('.', $cmd);

			// Define the controller filename and path.
			$file = self::createFileName('controller', array('name' => $name));
			$path = BASE_PATH . '/controllers/' . $file;
			$backuppath = BASE_PATH . '/controller/' . $file;

			// Reset the task without the controller context.
			$input->set('task', $task);
		}
		else
		{
			// Base controller.
			$name = null;

			// Define the controller filename and path.
			$file       = self::createFileName('controller', array('name' => 'controller'));
			$path       = BASE_PATH . '/' . $file;
		}

		// Get the controller class name.
		$class = ucfirst((string) $prefix) . 'Controller' . ucfirst((string) $name);

		// Include the class if not present.
		if (!class_exists($class))
		{
			// If the controller file path exists, include it.
			if (file_exists($path))
			{
				include_once $path;
			}
			else
			{
				throw new \InvalidArgumentException(CKText::sprintf('ERROR_INVALID_CONTROLLER', $type, $format));
			}
		}

		// Instantiate the class.
		if (!class_exists($class))
		{
			throw new \InvalidArgumentException(CKText::sprintf('ERROR_INVALID_CONTROLLER_CLASS', $class));
		}

		// Instantiate the class, store it to the static container, and return it
		return self::$instance = new $class();
	}

	protected static function createFileName($type, $parts = array())
	{
		$filename = '';

		switch ($type)
		{
			case 'controller':

				$filename = strtolower($parts['name'] . '.php');
				break;

			case 'view':

				$filename = strtolower($parts['name'] . '/view.html.php');
				break;
		}

		return $filename;
	}

	// public function getModel($base = '\Cookiesck\CKModel') {
		// if (empty($this->model)) {
			// $name = $this->getName();
			// include_once(BASE_PATH . '/helpers/ckmodel.php');
			// include_once(BASE_PATH . '/models/' . strtolower($name) . '.php');
			// $className = ucfirst($base) . ucfirst($name);
			// $this->model = new $className;
		// }
		// return $this->model;
	// }

	public function getView($name = '', $type = 'html', $prefix = '')
	{
		// @note We use self so we only access stuff in this class rather than in all classes.
		if (!isset(self::$views))
		{
			self::$views = array();
		}

		if (empty($name))
		{
			$name = $this->getName();
		}

		if (empty($prefix))
		{
			$prefix = $this->getPrefix() . 'View';
		}

		if (empty(self::$views[$name][$type][$prefix]))
		{
			if ($view = $this->createView($name, $prefix))
			{
				self::$views[$name][$type][$prefix] = & $view;
			}
			else
			{
				throw new \Exception(CKText::sprintf('ERROR_VIEW_NOT_FOUND', $name, $type, $prefix), 404);
			}
		}

		return self::$views[$name][$type][$prefix];
	}

	protected function createView($name, $prefix = '')
	{
		// Clean the view name
		$viewName = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$classPrefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

		// Build the view class name
		$viewClass = $classPrefix . ucfirst($viewName);

		if (!class_exists($viewClass))
		{
			$path = BASE_PATH . '/views/' . $this->createFileName('view', array('name' => $viewName));

			if (!file_exists($path))
			{
				return null;
			}

			include_once $path;

			if (!class_exists($viewClass))
			{
				throw new \Exception(CKText::_('ERROR_VIEW_CLASS_NOT_FOUND : ' . $viewClass . ' - ' . $path), 500);
			}
		}

		return new $viewClass();
	}

	public function display() {
		$viewName = $this->input->get('view', $this->getName());
		$viewLayout = $this->input->get('layout', 'default', 'string');

		$view = $this->getView($viewName, 'html', '');
		$view->setName($viewName);

		// Get/Create the model
		if ($model = $this->getModel($viewName))
		{
			// Push the model into the view (as default)
			$view->setModel($model);
		}

		$view->display();

		return $this;
	}

	public function getModel($name = '', $prefix = '', $config = array())
	{
		if (empty($name))
		{
			$name = ucfirst($this->getName());
		}

		if (empty($prefix))
		{
			$prefix = ucfirst($this->getPrefix());
		}

		$model = $this->createModel($name, $prefix, $config);

		return $model;
	}

	protected function createModel($name, $prefix = '', $config = array())
	{
		// Clean the model name
		$modelName = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$classPrefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

		return CKModel::getInstance($modelName, $classPrefix, $config);
	}


	public function execute($task) {
		if (! $task) $task = 'display';
		if (is_callable(array($this, $task))) {
			return $this->$task();
		}
		else
		{
			throw new \Exception(CKText::sprintf('ERROR_TASK_NOT_FOUND', $task), 404);
		}

		return;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getName()
	{
		if (empty($this->name))
		{
			$r = null;

			if (!preg_match('/Controller(.*)/i', get_class($this), $r))
			{
				throw new \Exception(CKText::_('Error : Can not get controller name'), 500);
			}

			$this->name = strtolower($r[1]);
		}

		return $this->name;
	}

	public function getPrefix()
	{
		if (empty($this->prefix))
		{
			$r = null;

			if (!preg_match('/(.*)Controller/i', get_class($this), $r))
			{
				throw new \Exception(CKText::_('Error : Can not get controller name'), 500);
			}

			$this->prefix = strtolower($r[1]);
		}

		return $this->prefix;
	}

	public function add() {
		return $this->edit(0);
	}

	public function edit($id = null, $appendUrl = '') {
		$editIds = $this->input->get('cid', $id, 'array');
		if (is_array($editIds)) {
			$editId = (int) $editIds[0];
		} else {
			$editId = (int) $this->input->get('id', $id, 'int');
		}

		$model = $this->getModel($this->getName());
		$model->checkout($editId);

		// Redirect to the edit screen.
		CKFof::redirect(CKUri::base(true) . BASE_URL . '&view=' . $this->getName() . '&layout=edit&id=' . $editId . $appendUrl);
	}

	public function copy() {
		$editIds = $this->input->get('cid', null, 'array');
		if (count($editIds)) {
			$id = (int) $editIds[0];
		} else {
			$id = (int) $this->input->get('id', null, 'int');
		}

		$model = $this->getModel($this->getName());
		if ($model->copy($id)) {
			CKFof::enqueueMessage('Item copied with success');
		} else {
			CKFof::enqueueMessage('Error : Item not copied', 'error');
		}

		if (! $this->redirect) $this->redirect = CKUri::root(true) . ADMIN_GENERAL_URL;

		// Redirect to the edit screen.
		CKFof::redirect($this->redirect);
	}

	public function delete() {
		$ids = $this->input->get('cid', null, 'array');
		if (count($ids)) {
			$ids = (array) $ids;
		} else {
			$ids = (array) $this->input->get('id', null, 'array');
		}
		$model = $this->getModel($this->getName());
		foreach($ids as $id) {
			$return = $model->delete($id);
			if ($return) {
				CKFof::enqueueMessage('Item deleted with success');
			} else {
				CKFof::enqueueMessage('Error : Item not deleted', 'error');
			}
		}

		if (! $this->redirect) $this->redirect = CKUri::root(true) . ADMIN_GENERAL_URL;

		// Redirect to the edit screen.
		CKFof::redirect($this->redirect);
	}

	public function trash() {
		$ids = $this->input->get('cid', null, 'array');
		if (count($ids)) {
			$ids = (array) $ids;
		} else {
			$ids = (array) $this->input->get('id', null, 'array');
		}
		$model = $this->getModel($this->getName());
		foreach($ids as $id) {
			$return = $model->trash($id);
			if ($return) {
				CKFof::enqueueMessage('CK_ITEM_TRASH_SUCCESS');
			} else {
				CKFof::enqueueMessage('CK_ITEM_TRASH_FAILED', 'error');
			}
		}

		// Redirect to the edit screen.
		CKFof::redirect(CKUri::root(true) . ADMIN_GENERAL_URL);
	}

	public function publish() {
		$ids = $this->input->get('cid', null, 'array');
		if (count($ids)) {
			$ids = (array) $ids;
		} else {
			$ids = (array) $this->input->get('id', null, 'array');
		}
		if (! empty($ids)) {
			$model = $this->getModel($this->getName());
			foreach($ids as $id) {
				if ($model->publish($id)) {
					CKFof::enqueueMessage('Item published with success');
				} else {
					CKFof::enqueueMessage('Error : Item not published', 'error');
				}
			}
		}

		// Redirect to the edit screen.
		CKFof::redirect(CKUri::root(true) . ADMIN_GENERAL_URL);
	}

	public function unpublish() {
		$ids = $this->input->get('cid', null, 'array');
		if (count($ids)) {
			$ids = (array) $ids;
		} else {
			$ids = (array) $this->input->get('id', null, 'array');
		}

		if (! empty($ids)) {
			$model = $this->getModel($this->getName());
			foreach($ids as $id) {
				if ($model->unpublish($id)) {
					CKFof::enqueueMessage('Item unpublished with success');
				} else {
					CKFof::enqueueMessage('Error : Item not unpublished', 'error');
				}
			}
		}

		// Redirect to the edit screen.
		CKFof::redirect(CKUri::root(true) . ADMIN_GENERAL_URL);
	}
}


class CKModel {

	var $_item = null;

	private static $instance = array();

	protected $input;

	protected $table;

	protected $__state_set = null;

	protected $state;

	protected $pagination;

	private static $name;

	private static $prefix;

	function __construct() {
		$this->input = CKFof::getInput();
		$this->state = new \Cookiesck\CKObject();
	}

	static function getInstance($name, $prefix, $config = array()) {
		self::$name = $name;
		self::$prefix = $prefix;
		if (isset(self::$instance[$name . $prefix]) && is_object(self::$instance[$name . $prefix]))
		{
			return self::$instance[$name . $prefix];
		}

		$basePath = BASE_PATH;
		// Check for a controller.task command.
		$input = CKFof::getInput();

		// Define the controller filename and path.
		$file       = strtolower($name . '.php');
		$path       = $basePath . '/models/' . $file;

		// Get the controller class name.
		$class = ucfirst($prefix) . 'Model' . ucfirst($name);

		// Include the class if not present.
		if (!class_exists($class))
		{
			// If the controller file path exists, include it.
			if (file_exists($path))
			{
				include_once $path;
			}
			else
			{
//				throw new \InvalidArgumentException(CKText::sprintf('ERROR_INVALID_MODEL', $type, $format));
			
				return false;
			}
		}

		// Instantiate the class.
		if (!class_exists($class))
		{
			throw new \InvalidArgumentException(CKText::sprintf('ERROR_INVALID_MODEL_CLASS', $class));
		}

		// Instantiate the class, store it to the static container, and return it
		return self::$instance[$name . $prefix] = new $class();
	}

	public function save($data) {

	}

	public function delete($id) {
		$return = CKFof::dbDelete( $this->table, (int)$id );
		return $return;
	}

	public function trash($id) {
		$fields = array('state' => '-2');
		$return = CKFof::dbUpdate($this->table, (int)$id, $fields);
		return $return;
	}

	public function setState($property, $value = null)
	{
		return $this->state->set($property, $value);
	}

	public function getState($property = null, $default = null)
	{
		if (!$this->__state_set)
		{
			// Protected method to auto-populate the model state.
			$this->populateState();

			// Set the model state set flag to true.
			$this->__state_set = true;
		}

		return $property === null ? $this->state : $this->state->get($property, $default);
	}

	protected function populateState()
	{
		$config = \Joomla\CMS\Factory::getConfig();
		$state = CKFof::getUserState(self::$prefix . '.' . self::$name, null);

		// first request, or custom user request
		if ($state === null || $this->input->get('state_request', 0, 'int') === 1) {
			$this->state = new CKObject();
			$this->state->set('filter_order', $this->input->get('filter_order', 'a.id'));
			$this->state->set('filter_order_Dir', $this->input->get('filter_order_Dir', 'asc'));
			$this->state->set('filter_search', $this->input->get('filter_search', '', 'string'));
			$this->state->set('limitstart', $this->input->get('limitstart', 0));
			$this->state->set('limit_total', $this->input->get('limittotal', 0));
			$this->state->set('limit', $this->input->get('limit', $config->get('list_limit')));
			$state = CKFof::setUserState(self::$prefix . '.' . self::$name, $this->state->getData());
		} else {
			$this->state = new CKObject($state);
		}
	}

	public function getPagination($total = null, $start = null, $limit = null)
	{
		if (!$this->pagination)
		{
			$total = $this->state->get('limit_total', $total);
//			$total = $this->getTotal();
			$start = $this->state->get('limitstart', $start);
			$limit = $this->state->get('limit', $limit);

			$this->pagination = new CKPagination($total, $start, $limit);
		}

		return $this->pagination;
	}

	public function getTotal($query) {
		$db = CKFof::getDbo();
		$query = clone $query;
		$query->clear('select')->clear('order')->clear('limit')->clear('offset')->select('COUNT(*)');
		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	public function copy($id) {
		$row = CKFof::dbLoad($this->table, (int)$id);
		$row->id = 0;
		$row->title = $row->title . ' - copy';

		$newid = CKFof::dbStore($this->table, $row);

		return $newid;
	}

	public function checkout($id) {
		$user = CKFof::getUser();
		$query= "SELECT checked_out from " . $this->table . " WHERE id = " . (int)$id;
		$checkedout = CKFof::dbLoadResult($query);

		if ($checkedout && $checkedout != $user->id) return false;

		return CKFof::dbUpdate($this->table, $id, array('checked_out' => $user->id));
	}

	public function checkin($id) {
		$user		= CKFof::getUser();
		$row = CKFof::dbLoad($this->table, (int)$id);
		$canCheckIn = $row->checked_out == $user->get('id') || CKFof::userCan('core.edit.state');
		if ($canCheckIn) {
			return CKFof::dbUpdate($this->table, $id, array('checked_out' => '0'));
		}
		return false;
	}

	public function publish($id) {
		$row = CKFof::dbLoad($this->table, (int)$id);
		$row->state = 1;

		$result = CKFof::dbStore($this->table, $row);

		return $result;
	}

	public function unpublish($id) {
		$row = CKFof::dbLoad($this->table, (int)$id);
		$row->state = 0;

		$result = CKFof::dbStore($this->table, $row);

		return $result;
	}
}


class CKView {

	protected $name;

	protected $model;

	protected $input;

	protected $item;

	protected $state;

	protected $items;

	protected $pagination;

	public function __construct() {
		$this->input = CKFof::getInput();
		// check if the user has the rights to access this page
		if (	(CKFof::isAdmin() 
				|| $this->input->get('layout') == 'edit' 
				|| $this->input->get('task') == 'edit')
				&& !CKFof::userCan('edit')) {
			CKFof::_die('CKView restriction');
		}
	}

	/**
	 * Magic method since PHP 8.2
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string $name, mixed $value): void {

	}

	public function display($tpl = 'default') {
		if ($tpl === null) $tpl = 'default';
		if ($this->model) {
			$this->state = $this->model->getState();
			$this->pagination = $this->model->getPagination();
		}

		$tpl = $this->input->get('layout', $tpl);
		include_once BASE_PATH . '/views/' . strtolower($this->name) . '/tmpl/' . $tpl . '.php';
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setModel($model) {
		$this->model = $model;
	}

	public function get($func) {
		$model = $this->getModel();
		if ($model === false) return false;
		$funcName = 'get' . ucfirst($func);
		return $model->$funcName();
	}

	public function getModel() {
		if (empty($this->model)) {
			$file = BASE_PATH . '/models/' . strtolower($this->name) . '.php';
			if (! file_exists($file)) return false;
			include_once($file);
			$className = '\\' . ucfirst(EXTENSION_NAME) . '\CKModel' . ucfirst($this->name);
			$this->model = new $className;
		}
		return $this->model;
	}
}

