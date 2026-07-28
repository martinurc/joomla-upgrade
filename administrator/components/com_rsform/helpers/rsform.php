<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\Utilities\IpHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Form;

require_once __DIR__.'/constants.php';
require_once __DIR__.'/config.php';
require_once __DIR__.'/version.php';
require_once __DIR__.'/assets.php';

Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_rsform/tables');

// Let's run some workarounds

// Disable caching for the current view (eg. com_content with Content Plugin)
$cache = Factory::getCache(Factory::getApplication()->input->getCmd('option'), 'view');
$cache->setCaching(false);

// Disable caching for the current component
$cache = Factory::getCache(Factory::getApplication()->input->getCmd('option'));
$cache->setCaching(false);

$lang = Factory::getLanguage();
Factory::getLanguage()->load('com_rsform', JPATH_ADMINISTRATOR);

class RSFormProHelper
{
	public static $captchaFields = array(
		RSFORM_FIELD_CAPTCHA,
		RSFORM_FIELD_HASHCASH,
	);

    public static $formShown;

	public static function getDate($date)
	{
		static $mask;
		if (!$mask) {
			$mask = RSFormProHelper::getConfig('global.date_mask');
			if (!$mask) {
				$mask = 'Y-m-d H:i:s';
			}
		}
		if ($date === null || $date === '0000-00-00 00:00:00' || $date === Factory::getDbo()->getNullDate())
		{
			return '';
		}
		return HTMLHelper::_('date', $date, $mask);
	}

    public static function mailRoute($url, $xhtml = true)
    {
	    $app        = Factory::getApplication();
	    $force_ssl  = (int) $app->get('force_ssl');
	    $ssl_option = Route::TLS_IGNORE;
	    if ($force_ssl === 2)
	    {
		    $ssl_option = Route::TLS_FORCE;
	    }

	    return Route::link('site', $url, $xhtml, $ssl_option, true);
    }

	public static function getTooltipText($title, $content='') {
		return HTMLHelper::_('tooltipText', $title, $content, 0, 0);
	}

	public static function getTooltipClass()
	{
		static $class = false;

		if (!$class)
		{
			$class = 'hasTooltip';

			HTMLHelper::_('bootstrap.tooltip', '.' . $class);
		}

		return $class;
	}

	public static function showEditor($name, $html, $options = array())
	{
		if (!isset($options['syntax']))
		{
			$options['syntax'] = 'html';
		}
		if (!isset($options['readonly']))
		{
			$options['readonly'] = false;
		}
		if (!isset($options['id']))
		{
			$options['id'] = OutputFilter::stringURLSafe($name);
		}
		
		$use_editor = RSFormProHelper::getConfig('global.codemirror');
		$editor 	= 'codemirror';

		if ($use_editor)
		{
			$plugin = PluginHelper::getPlugin('editors', $editor);
			if (empty($plugin))
			{
				$use_editor = false;
			}
			else
			{
				if (!is_string($plugin->params) && is_callable(array($plugin->params, 'toString')))
				{
					$plugin->params = $plugin->params->toString();
				}
			}
		}
		
		if ($use_editor)
		{
			$instance = Editor::getInstance($editor);
			
			$html = $instance->display($name, static::htmlEscape($html), '100%', 300, 75, 20, $buttons = false, $options['id'], $asset = null, $author = null, array('syntax' => $options['syntax'], 'readonly' => $options['readonly']));
		}
		else
		{
			if (empty($options['id']))
			{
				$options['id'] = $name;
			}
			$readonly = '';
			if (!empty($options['readonly']))
			{
				$readonly = 'readonly';
			}
			
			$html = '<textarea class="' . $options['classes'] . '" ' . $readonly . ' rows="20" cols="75" name="' . static::htmlEscape($name) . '" id="' . static::htmlEscape($options['id']) . '">' . static::htmlEscape($html) . '</textarea>';
		}
		
		return $html;
	}

	public static function getComponentId($name, $formId=0)
	{
		static $cache = array();

		if (empty($formId))
		{
			$formId = Factory::getApplication()->input->getInt('formId');
			if (empty($formId))
			{
				$post   = Factory::getApplication()->input->get('form', array(),'array');
				$formId = isset($post['formId']) ? $post['formId'] : 0;
			}
		}

		if (!isset($cache[$formId][$name]))
			$cache[$formId][$name] = RSFormProHelper::componentNameExists($name, $formId, 0, 'ComponentId');

		return $cache[$formId][$name];
	}

	public static function getComponentTypeId($name, $formId=0)
	{
		static $cache = array();

		if (empty($formId))
		{
			$formId = Factory::getApplication()->input->getInt('formId');
			if (empty($formId))
			{
				$post   = Factory::getApplication()->input->get('form', array(),'array');
				$formId = isset($post['formId']) ? $post['formId'] : 0;
			}
		}

		if (!isset($cache[$formId][$name])) {
			$cache[$formId][$name] = RSFormProHelper::componentNameExists($name, $formId, 0, 'ComponentTypeId');
		}

		return $cache[$formId][$name];
	}

	public static function createList($results, $value = 'value', $text = 'text')
	{
		$list = array();
		if (is_array($results))
		{
			foreach ($results as $result)
			{
				if (is_object($result))
				{
					$list[] = $result->{$value} . '|' . $result->{$text};
                }
                elseif (is_array($result))
				{
					$list[] = $result[$value] . '|' . $result[$text];
                }
            }
        }

		return implode("\n", $list);
	}

	public static function displayForm($formId, $is_module=false)
	{
        static::$formShown = true;

		$mainframe 	= Factory::getApplication();
		$doc 		= Factory::getDocument();
		$session 	= Factory::getSession();
		$form 		= RSFormProHelper::getForm($formId);

		if (empty($form) || !$form->Published)
		{
			if ($is_module)
			{
				$mainframe->enqueueMessage(Text::sprintf('RSFP_FORM_DOES_NOT_EXIST', $formId), 'warning');
				return false;
			}
			else
			{
				throw new Exception(Text::sprintf('RSFP_FORM_DOES_NOT_EXIST', $formId), 404);
			}
		}

		// Check form access level
		if (!$is_module && $form->Access != '')
		{
			$canView = false;

			// Forms shown in the backend are accessible
			if ($mainframe->isClient('administrator'))
            {
                $canView = true;
            }
            else
            {
                // If we have a menu item, inherit access from that.
                if ($active = $mainframe->getMenu()->getActive())
                {
                    if ($query = $active->query)
                    {
                        if (isset($query['option']) && isset($query['view']) && isset($query['formId']))
                        {
                            if ($query['option'] == 'com_rsform' && $query['view'] == 'rsform' && $query['formId'] == $formId)
                            {
                                $canView = true;
                            }
                        }
                    }
                }
            }

			if (!$canView)
			{
				$user = Factory::getUser();
				if (!in_array($form->Access,$user->getAuthorisedViewLevels()))
				{
                    $redirectUrl = RSFormProHelper::getConfig('access_login') ? Route::_('index.php?option=com_users&view=login&return=' . base64_encode(Uri::getInstance()->toString())) : Uri::root();
                    $redirectMessage = RSFormProHelper::getConfig('access_login') ? Text::_('COM_RSFORM_FORM_CANNOT_BE_ACCESSED_PLEASE_LOGIN') : Text::sprintf('RSFP_FORM_CANNOT_BE_ACCESSED', $formId);
					// Error, the form cannot be accessed
					$mainframe->enqueueMessage($redirectMessage, 'warning');
					$mainframe->redirect($redirectUrl);
					return false;
				}
			}
		}

		$lang 		  = RSFormProHelper::getCurrentLanguage($formId);
		$translations = RSFormProHelper::getTranslations('forms', $formId, $lang);
		if ($translations)
		{
			foreach ($translations as $field => $value)
			{
				if (isset($form->{$field}))
					$form->{$field} = $value;
			}
		}

		if (!$is_module)
		{
			if ($form->MetaDesc)
			{
				$doc->setMetaData('description', $form->MetaDesc);
			}

			if ($form->MetaKeywords)
			{
				$doc->setMetaData('keywords', $form->MetaKeywords);
			}

			if ($form->MetaTitle)
			{
				$doc->setTitle($form->FormTitle);
			}
		}

		$formparams = $session->get('com_rsform.formparams.formId'.$formId);

		// Form has been processed ?
		if ($formparams && !empty($formparams->formProcessed))
		{
			// Must show Thank You Message
			if ($form->ShowThankyou)
			{
				return RSFormProHelper::showThankYouMessage($formId);
			}

			// Clear
			$session->clear('com_rsform.formparams.formId'.$formId);

			// Must show small message
			if ($formparams->showSystemMessage)
			{
				$mainframe->enqueueMessage(Text::_('RSFP_THANKYOU_SMALL'));
			}

			if ($form->ScrollToThankYou)
			{
				// scroll the window to the Thank You Message
				RSFormProAssets::addScriptDeclaration("RSFormProUtils.addEvent(window, 'load', function(){ RSFormPro.scrollToElement(document.getElementById('system-message-container')); });");
			}
		}

		// Check if the configured limit of submissions has been reached
		if (!empty($form->LimitSubmissions))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__rsform_submissions'))
				->where($db->qn('FormId') . ' = ' . $db->q($formId));

			$limit = $db->setQuery($query)->loadResult();

			if ($limit >= $form->LimitSubmissions)
			{
				return Text::_('COM_RSFORM_LIMIT_SUBMISSIONS_HAS_BEEN_REACHED');
			}
		}

		if ($form->DisableSubmitButton)
		{
			RSFormProAssets::addScriptDeclaration("RSFormProUtils.addEvent(window, 'load', function(){ RSFormPro.setDisabledSubmit('{$formId}', " . ($form->AjaxValidation ? 'true' : 'false') . "); });");
		}
		
		// Must process form
		$post = $mainframe->input->post->get('form', array(), 'array');
		if (isset($post['formId']) && $post['formId'] == $formId)
		{
			$invalid = RSFormProHelper::processForm($formId);
			// Did not pass validation - show the form
			if ($invalid)
			{
				if ($form->ScrollToError)
				{
					RSFormProAssets::addScriptDeclaration("RSFormProUtils.addEvent(window, 'load', function(){ RSFormPro.gotoErrorElement({$formId}); });");
				}
				// We call this again because the array might have been modified by processForm()
                $post = $mainframe->input->post->get('form', array(), 'array');
				return RSFormProHelper::showForm($formId, $post, $invalid);
			}
		}

		// Default - show the form
		$get = $mainframe->input->get->get('form', array(), 'array');
		return RSFormProHelper::showForm($formId, $get);
	}

	public static function getEditor()
    {
        static $editor;

        // Get the editor configuration setting
        if (is_null($editor))
        {
            $editor = Factory::getApplication()->get('editor');
        }

		return Editor::getInstance($editor);
    }

	public static function WYSIWYG($name, $content, $hiddenField, $width, $height, $col, $row)
	{
		$editor = RSFormProHelper::getEditor();
		$params = array('relative_urls' => '0', 'cleanup_save' => '0', 'cleanup_startup' => '0', 'cleanup_entities' => '0');

		$id = trim(substr($name, 4), '][');
		$content = $editor->display($name, $content , $width, $height, $col, $row, true, $id, null, null, $params);

		return $content;
	}

	public static function getOtherCalendars($type = RSFORM_FIELD_CALENDAR) {
		$list 	= array();

		$formId 	 = Factory::getApplication()->input->getInt('formId');
		$componentId = Factory::getApplication()->input->getInt('componentId');

		$list[] = array(
			'value' => '',
			'text' => 'NO_DATE_MODIFIER'
		);

		if ($calendars = self::componentExists($formId, $type)) {
			// remove our current calendar from the list
			if ($componentId) {
				$pos = array_search($componentId, $calendars);
				if ($pos !== false) {
					unset($calendars[$pos]);
				}
			}
			// any calendars left?
			if ($calendars) {
				$all_data = self::getComponentProperties($calendars);
				foreach ($calendars as $calendar) {
					$data =& $all_data[$calendar];
					$list[] = array(
						'value' => 'min '.$calendar,
						'text' => Text::sprintf('RSFP_CALENDAR_SETS_MINDATE', $data['NAME'])
					);
					$list[] = array(
						'value' => 'max '.$calendar,
						'text' => Text::sprintf('RSFP_CALENDAR_SETS_MAXDATE', $data['NAME'])
					);
				}
			}
		}

		return self::createList($list);
	}

	public static function getValidationClass() {
		if (file_exists(JPATH_SITE.'/components/com_rsform/helpers/customvalidation.php')) {
			require_once JPATH_SITE.'/components/com_rsform/helpers/customvalidation.php';
			return 'RSFormProCustomValidations';
		} else {
			require_once JPATH_SITE.'/components/com_rsform/helpers/validation.php';
			return 'RSFormProValidations';
		}
	}

	public static function getValidationRules($asArray = false, $removeMultiple = false) {
		$results = get_class_methods(static::getValidationClass());
		
		// Add 'none' as first validation rule
		unset($results[array_search('none', $results)]);
		array_unshift($results, 'none');
		
		// remove the multiple validation because the multiple rules has already been selected, also the none validation is not necessary
        // $removeMultiple can also be an array of rules to remove
		if ($removeMultiple)
        {
            if (!is_array($removeMultiple))
            {
                $removeMultiple = array('multiplerules', 'none');
            }

	        foreach ($removeMultiple as $validationRule)
	        {
		        $pos = array_search($validationRule, $results);
		        if ($pos !== false)
		        {
			        unset($results[$pos]);
		        }
	        }
		}
		
		if ($asArray) {
			return $results;
		} else {
			// Let's sort
			usort($results, array('RSFormProHelper', 'sortValidations'));
			return implode("\n", $results);
		}
	}

	public static function sortValidations($a, $b)
	{
        if ($a !== $b)
		{
			if ($a === 'none')
			{
				return -1;
			}
			if ($b === 'none')
			{
				return 1;
			}
        }

		$lang = Factory::getLanguage();

		if ($lang->hasKey('RSFP_COMP_FVALUE_' . $a))
		{
			$a = Text::_('RSFP_COMP_FVALUE_' . $a);
		}

		if ($lang->hasKey('RSFP_COMP_FVALUE_' . $b))
		{
			$b = Text::_('RSFP_COMP_FVALUE_' . $b);
		}

		return strcmp($a, $b);
	}

	public static function getDateValidationClass() {
		if (file_exists(JPATH_SITE.'/components/com_rsform/helpers/customdatevalidation.php')) {
			require_once JPATH_SITE.'/components/com_rsform/helpers/customdatevalidation.php';
			return 'RSFormProCustomDateValidations';
		} else {
			require_once JPATH_SITE.'/components/com_rsform/helpers/datevalidation.php';
			return 'RSFormProDateValidations';
		}
	}

	public static function getDateValidationRules($asArray = false) {
		$results = get_class_methods(static::getDateValidationClass());

		// Add 'none' as first validation rule
		unset($results[array_search('none', $results)]);
		array_unshift($results, 'none');

		if ($asArray) {
			return $results;
		} else {
			return implode("\n", $results);
		}
	}

	public static function getEmailAttachOptions()
    {
        $formId = Factory::getApplication()->input->getInt('formId');
        $db     = Factory::getDbo();

        $options = array(
            'useremail',
            'adminemail'
        );

        $query = $db->getQuery(true)
            ->select($db->qn('id'))
            ->select($db->qn('subject'))
            ->from($db->qn('#__rsform_emails'))
            ->where($db->qn('type') . ' = ' . $db->q('additional'))
            ->where($db->qn('formId') . ' = ' . $db->q($formId));

        if ($emails = $db->setQuery($query)->loadObjectList())
        {
            foreach ($emails as $email)
            {
                $options[] = $email->id . '|' . $email->subject;
            }
        }

        return implode("\n", $options);
    }

	public static function readConfig($force = false)
	{
		$config = RSFormProConfig::getInstance();

		if ($force)
		{
			$config->reload();
		}

		return $config->getData();
	}

	public static function getConfig($name = null)
	{
		$config = RSFormProConfig::getInstance();

		if ($name === null)
		{
			return $config->getData();
		}

		return $config->get($name);
	}

	public static function componentNameExists($componentName, $formId, $currentComponentId = 0, $column = 'ComponentId')
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select($db->qn('c.' . $column))
			->from($db->qn('#__rsform_properties', 'p'))
			->join('left', $db->qn('#__rsform_components', 'c') . ' ON (' . $db->qn('p.ComponentId') . ' = ' . $db->qn('c.ComponentId') . ')')
			->where($db->qn('c.FormId') . ' = ' . $db->q($formId))
			->where($db->qn('p.PropertyName') . ' = ' . $db->q('NAME'))
			->where($db->qn('p.PropertyValue') . ' = ' . $db->q($componentName));

		if ($currentComponentId)
		{
			$query->where($db->qn('c.ComponentId') . ' <> ' . $db->q($currentComponentId));
		}

		return $db->setQuery($query)->loadResult();
	}

	public static function getCurrentLanguage($formId=null)
	{
		$mainframe = Factory::getApplication();
		$lang 	   = Factory::getLanguage();
		$session   = Factory::getSession();
		$formId    = !$formId ? $mainframe->input->getInt('formId') || $mainframe->input->getInt('FormId') : $formId;

		// editing in backend ?
		if ($mainframe->isClient('administrator'))
		{
			if ($mainframe->input->getCmd('task') == 'submissions.edit' || ($mainframe->input->getCmd('view') == 'submissions' && $mainframe->input->getCmd('layout') == 'edit'))
			{
				$cid = $mainframe->input->get('cid', array(), 'array');

				require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
				if ($submission = RSFormProSubmissionsHelper::getSubmission(reset($cid), false))
				{
					return $submission->Lang;
				}
			}

			if (RSFormProHelper::getConfig('global.disable_multilanguage'))
			{
				return RSFormProHelper::getConfig('global.default_language');
			}

			return $session->get('com_rsform.form.formId'.$formId.'.lang', $lang->getTag());
		}
		// frontend
		else
		{
			// If it's a directory, get the language of the submission
			if ($mainframe->isClient('site') && // only site app, ignore cli
                ($active = $mainframe->getMenu()->getActive()) && // get active menu
				isset($active->query, $active->query['option'], $active->query['view']) // make sure we have option & query
				&& $active->query['option'] == 'com_rsform' && $active->query['view'] == 'directory' // make sure it's a Directory view
				&& ($params = $active->getParams()) // we have params
				&& $params->get('enable_directory') && $params->get('formId') == $formId // this form matches
				&& ($id = $mainframe->input->getInt('id'))) // it's an edit request
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
				if ($submission = RSFormProSubmissionsHelper::getSubmission($id, false))
				{
					return $submission->Lang;
				}
			}

			if (RSFormProHelper::getConfig('global.disable_multilanguage'))
			{
				return RSFormProHelper::getConfig('global.default_language');
			}

			return $lang->getTag();
		}
	}

	public static function &getComponentProperties($components, $translate = true)
	{
		static $cache = array(
            0 => array(),
            1 => array()
        );

        $translate = (int) $translate;

		if (is_numeric($components))
		{
			$componentIds = array($components);
			$single		  = $components;
		}
		else
		{
			$componentIds = array();
			$single		  = false;

			if (!is_array($components))
			{
				$components = array($components);
			}

			foreach ($components as $componentId)
			{
				if (is_object($componentId) && !empty($componentId->ComponentId))
				{
					$componentIds[] = (int) $componentId->ComponentId;
				}
				elseif (is_array($componentId) && !empty($componentId['ComponentId']))
				{
					$componentIds[] = (int) $componentId['ComponentId'];
				}
				else
				{
					$componentIds[] = (int) $componentId;
				}
			}
		}

		$componentIds = array_filter($componentIds);

		if ($componentIds)
		{
			if ($newComponentIds = array_diff($componentIds, array_keys($cache[$translate])))
			{
				$all_data		= &$cache[$translate];
				$db 			= Factory::getDbo();
				$query 			= $db->getQuery(true);

				$query->select($db->qn('PropertyName'))
					->select($db->qn('PropertyValue'))
					->select($db->qn('ComponentId'))
					->from($db->qn('#__rsform_properties'))
					->where($db->qn('ComponentId').' IN ('.implode(',', $newComponentIds).')');

				if ($results = $db->setQuery($query)->loadObjectList()) {
					foreach ($results as $result) {
						if (!isset($all_data[$result->ComponentId])) {
							$all_data[$result->ComponentId] = array('componentId' => $result->ComponentId);
						}

						$all_data[$result->ComponentId][$result->PropertyName] = $result->PropertyValue;
					}
				}

				// Guess the form ID
				$query = $db->getQuery(true);
				$query->select($db->qn('FormId'))
					->from($db->qn('#__rsform_components'))
					->where($db->qn('ComponentId').'='.$db->q(reset($newComponentIds)));
				$formId = $db->setQuery($query)->loadResult();

				// language
                if ($translate)
                {
                    $lang 		  = RSFormProHelper::getCurrentLanguage($formId);
                    $translations = RSFormProHelper::getTranslations('properties', $formId, $lang);
                    foreach ($all_data as $componentId => $properties) {
                    	// Don't translate again if not needed
                    	if (!in_array($componentId, $newComponentIds))
						{
							continue;
						}

                        foreach ($properties as $property => $value) {
                            $reference_id = $componentId.'.'.$property;
                            if (isset($translations[$reference_id])) {
                                $properties[$property] = $translations[$reference_id];
                            }
                        }
                        $all_data[$componentId] = $properties;
                    }
                }
			}
		}

		if ($single) {
			if (!empty($cache[$translate][$single])) {
				return $cache[$translate][$single];
			}
		} else {
			$results = array();
			foreach ($componentIds as $componentId) {
				$results[$componentId] = &$cache[$translate][$componentId];
			}

			return $results;
		}

		$blank = array();
		
		return $blank;
	}

	public static function isCode($value)
    {
		if (self::hasCode($value))
        {
            try
            {
	            return eval($value);
            }
            catch (Throwable $e)
            {
	            Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
	            return '';
            }
		}

		return $value;
	}

	public static function hasCode($value) {
		return (strpos($value, '<code>') !== false);
	}

	public static function getIcon($type) {
		$icon = '';

		switch ($type) {
			case 'calendar': 		$icon = 'calendar-o'; break;
			case 'gmaps': 			$icon = 'map-marker'; break;
			case 'hidden': 			$icon = 'texture'; break;
			case 'jQueryCalendar':  $icon = 'calendar'; break;
			case 'rangeSlider':  	$icon = 'th-list'; break;
			case 'php': 			$icon = 'code'; break;
			case 'support': 		$icon = 'ticket'; break;
		}
		return '<span class="rsficon rsficon-'.$icon.'" style="font-size:24px;margin-right:5px"></span>';
	}

	public static function htmlEscape($val)
	{
		return empty($val) ? $val : htmlentities($val, ENT_COMPAT, 'UTF-8');
	}

	public static function explode($value)
	{
		if (!is_array($value))
		{
			$value = str_replace(array("\r\n", "\r"), "\n", $value);
			$value = explode("\n", $value);
		}

		return $value;
	}

	public static function readFile($file, $download_name = null, $die = true)
	{
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

		if ($ext == 'tgz' || $ext == 'gz') {
			// Needed when some servers with GZIP compression perform double encoding
			if (is_callable('ini_set')) {
				if (is_callable('ini_get') && ini_get('zlib.output_compression')) {
					ini_set('zlib.output_compression', 'Off');
				}

				ini_set('output_buffering', 'Off');
				ini_set('output_handler', '');
			}
			header('Content-Encoding: none');
		}

		if (empty($download_name))
		{
			$download_name = basename($file);
		}

		$fsize = filesize($file);

		header("Cache-Control: public, must-revalidate");
		header('Cache-Control: pre-check=0, post-check=0, max-age=0');
		if (!preg_match('#MSIE#', $_SERVER['HTTP_USER_AGENT']))
			header("Pragma: no-cache");
		header("Expires: 0");
		header("Content-Description: File Transfer");
		header("Expires: Sat, 01 Jan 2000 01:00:00 GMT");
		if (preg_match('#Opera#', $_SERVER['HTTP_USER_AGENT']))
			header("Content-Type: application/octetstream");
		else
			header("Content-Type: application/octet-stream");
		header("Content-Length: ".(string) ($fsize));
		header('Content-Disposition: attachment; filename="'.$download_name.'"');
		header("Content-Transfer-Encoding: binary\n");
		ob_end_flush();
		RSFormProHelper::readFileChunked($file);

		if ($die)
		{
			exit();
		}
	}

	public static function readFileChunked($filename, $retbytes=true)
	{
		$chunksize = 1*(1024*1024); // how many bytes per chunk
		$cnt = 0;
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		while (!feof($handle)) {
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			if ($retbytes) {
				$cnt += strlen($buffer);
			}
		}
		$status = fclose($handle);
		if ($retbytes && $status) {
			return $cnt; // return num. bytes delivered like readfile() does.
		}
		return $status;
	}

	public static function getReplacements($SubmissionId, $skip_globals=false)
	{
		// Small hack
		return RSFormProHelper::sendSubmissionEmails($SubmissionId, true, $skip_globals);
	}

	public static function sendSubmissionEmails($SubmissionId, $only_return_replacements=false, $skip_globals=false)
	{
		$db             = Factory::getDbo();
        $mainframe      = Factory::getApplication();
        $secret         = $mainframe->get('secret');
		$u              = Uri::getInstance();
		$SubmissionId   = (int) $SubmissionId;
        $placeholders   = array();
        $values         = array();
        $Itemid         = $mainframe->input->getInt('Itemid');
        $Itemid         = $Itemid ? '&Itemid='.$Itemid : '';

        // Get the submission
        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
        $submission = RSFormProSubmissionsHelper::getSubmission($SubmissionId);

        if (!$submission)
        {
            return false;
        }

		$formId = $submission->FormId;

        // Load our form
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__rsform_forms'))
            ->where($db->qn('FormId') . ' = ' . $db->q($formId));

		$form = $db->setQuery($query)->loadObject();

		// Multiple items separators
		$form->MultipleSeparator = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $form->MultipleSeparator);

		// If our submission has no language, simply inherit it from the form
		if (empty($submission->Lang))
		{
			if (!empty($form->Lang))
            {
                $submission->Lang = $form->Lang;
            }
			else
			{
				$submission->Lang = Factory::getLanguage()->getDefault();
			}

            $object = (object) array(
                'SubmissionId'  => $submission->SubmissionId,
                'Lang'          => $submission->Lang
            );

			$db->updateObject('#__rsform_submissions', $object, array('SubmissionId'));
		}

		// Get translations
		$translations = RSFormProHelper::getTranslations('forms', $form->FormId, $submission->Lang);
		if ($translations)
        {
            foreach ($translations as $field => $value)
            {
                if (isset($form->{$field}))
                {
                    $form->{$field} = $value;
                }
            }
        }

        $userEmailUploads       = array();
        $adminEmailUploads      = array();
        $additionalEmailUploads = array();

        $nodecimals = (int) RSFormProHelper::getConfig('calculations.nodecimals');
        $decimal    = RSFormProHelper::getConfig('calculations.decimal');
        $thousands  = RSFormProHelper::getConfig('calculations.thousands');

		if ($components = RSFormProHelper::getComponents($formId))
        {
            // Get all properties (default to English)
            $all_data = RSFormProHelper::getComponentProperties($components, false);

            // Translate properties in requested language
            if ($translations = RSFormProHelper::getTranslations('properties', $form->FormId, $submission->Lang))
            {
                foreach ($all_data as $componentId => $properties)
                {
                    foreach ($properties as $property => $value)
                    {
                        $reference_id = $componentId.'.'.$property;
                        if (isset($translations[$reference_id]))
                        {
                            $properties[$property] = $translations[$reference_id];
                        }
                    }
                    $all_data[$componentId] = $properties;
                }
            }

            foreach ($components as $component)
            {
                if (!isset($all_data[$component->ComponentId]))
                {
                    continue;
                }

                $property = $all_data[$component->ComponentId];

                // {component:caption}
                $placeholders[] = '{'.$property['NAME'].':caption}';
                // Hidden fields don't have a caption
                if (in_array($component->ComponentTypeId, array(RSFORM_FIELD_HIDDEN, RSFORM_FIELD_TICKET)))
                {
                    $values[] = $property['NAME'];
                }
                else
                {
                    $values[] = isset($property['CAPTION']) ? $property['CAPTION'] : '';
                }

                // {component:description}
                $placeholders[] = '{'.$property['NAME'].':description}';
                $values[] = isset($property['DESCRIPTION']) ? $property['DESCRIPTION'] : '';

                // {component:descriptionhtml}
				$placeholders[] = '{'.$property['NAME'].':descriptionhtml}';
				$values[] = isset($property['DESCRIPTION']) ? self::htmlEscape($property['DESCRIPTION']) : '';

                // {component:name}
                $placeholders[] = '{'.$property['NAME'].':name}';
                $values[] = $property['NAME'];

                // {component:value}
                $placeholders[] = '{'.$property['NAME'].':value}';
                $value = '';
                if (isset($submission->values[$property['NAME']]))
                {
                    $value = $submission->values[$property['NAME']];

                    // Check if this is an upload field
                    if ($component->ComponentTypeId == RSFORM_FIELD_FILEUPLOAD)
                    {
                    	$separator = '<br />';
                    	if (!empty($property['FILESSEPARATOR']))
						{
							$separator = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $property['FILESSEPARATOR']);
						}

                    	if (!empty($property['MULTIPLE']) && $property['MULTIPLE'] === 'YES')
						{
							$value = RSFormProHelper::explode($value);
						}
						else
						{
							$value = array($value);
						}

						$actualValues = array();
						foreach ($value as $actualValue)
						{
							// If we have a value, create a link, otherwise no point in doing that
							if (strlen($actualValue))
							{
								$fileUrl = static::mailRoute('index.php?option=com_rsform&task=submissions.viewfile&hash=' . md5($submission->SubmissionId . $secret . $property['NAME']) . '&file=' . md5($actualValue) . $Itemid);
								$actualValues[] = '<a href="'. $fileUrl . '">'.RSFormProHelper::htmlEscape(basename($actualValue)).'</a>';
							}
							else
							{
								$actualValues[] = '';
							}
						}

						$actualValues = array_filter($actualValues);

						$value = implode($separator, $actualValues);
                    }

                    // Check if this is a multiple field
                    if (in_array($component->ComponentTypeId, array(RSFORM_FIELD_SELECTLIST, RSFORM_FIELD_CHECKBOXGROUP, RSFORM_FIELD_SURVEYTABLE)) || isset($property['ITEMS']))
                    {
                        $value = str_replace("\n", $form->MultipleSeparator, $value);
                    }

                    if ($component->ComponentTypeId == RSFORM_FIELD_TEXTAREA && $form->TextareaNewLines && $property['WYSIWYG'] == 'NO')
                    {
                        $value = nl2br($value);
                    }
                }
                if ($component->ComponentTypeId == RSFORM_FIELD_FREETEXT)
                {
                    $value = $property['TEXT'];
                }
                $values[] = $value;

                // {component:text}
                // {component:price}
                if (isset($property['ITEMS']))
                {
                    $placeholders[] = '{'.$property['NAME'].':text}';
                    $placeholders[] = '{'.$property['NAME'].':price}';
                    if (isset($submission->values[$property['NAME']]))
                    {
                        $all_texts  = array();
                        $all_prices = array();

                        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fields/fielditem.php';
                        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/fieldmultiple.php';
                        $field = new RSFormProFieldMultiple(array(
                            'formId' 			=> $formId,
                            'componentId' 		=> $component->ComponentId,
                            'data' 				=> $property,
                            'value' 			=> array('formId' => $formId, $property['NAME'] => explode("\n", $submission->values[$property['NAME']])),
                            'invalid' 			=> false
                        ));

                        if ($items = $field->getItems())
                        {
                            foreach ($items as $item)
                            {
                                $item = new RSFormProFieldItem($item);

                                if ($item->value === $field->getItemValue($item))
                                {
                                    $all_texts[] = $item->label;
                                    $all_prices[] = $item->flags['price'] !== false ? (float) $item->flags['price'] : 0;
                                }
                            }
                        }

                        if ($all_texts)
                        {
                            $values[] = implode($form->MultipleSeparator, $all_texts);
                            $values[] = number_format(array_sum($all_prices), $nodecimals, $decimal, $thousands);
                        }
                        else
                        {
                            $values[] = $submission->values[$property['NAME']];
                            $values[] = '';
                        }
                    }
                    else
                    {
                        $values[] = '';
                        $values[] = '';
                    }
                }

                // {component:map}
				if ($component->ComponentTypeId == RSFORM_FIELD_GMAPS)
				{
					$placeholders[] = '{'.$property['NAME'].':map}';
                    $mapw = (int) preg_replace('/[^0-9]/', '', $property['MAPWIDTH']);
                    $maph = (int) preg_replace('/[^0-9]/', '', $property['MAPHEIGHT']);

					if (!empty($submission->values[$property['NAME']]))
					{
						$values[] = '<img width="' . self::htmlEscape($mapw) . '" height="' . self::htmlEscape($maph) . '" class="rsfp-gmap-image" src="https://maps.googleapis.com/maps/api/staticmap?key=' . urlencode(RSFormProHelper::getConfig('google.api_key')) . '&amp;markers=' . urlencode($submission->values[$property['NAME']]) . '&amp;center=' . urlencode($submission->values[$property['NAME']]) . '&amp;zoom=' . urlencode($property['MAPZOOM']) . '&amp;size=' . urlencode($mapw . 'x' . $maph) . '&amp;maptype=' . urlencode(strtolower($property['MAPTYPE'])) . '">';
					}
					else
					{
						$values[] = '';
					}
				}

                // {component:path}
                // {component:localpath}
                // {component:filename}
				// {component:image}
                if ($component->ComponentTypeId == RSFORM_FIELD_FILEUPLOAD)
                {
					$hasMultiple = !empty($property['MULTIPLE']) && $property['MULTIPLE'] === 'YES';

					$separator = '<br />';
					if (!empty($property['FILESSEPARATOR']))
					{
						$separator = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $property['FILESSEPARATOR']);
					}

					if (!empty($submission->values[$property['NAME']]))
					{
						if ($hasMultiple)
						{
							$value = RSFormProHelper::explode($submission->values[$property['NAME']]);
						}
						else
						{
							$value = array($submission->values[$property['NAME']]);
						}
					}
					else
					{
						$value = array();
					}

                    $placeholders[] = '{'.$property['NAME'].':path}';
                    $placeholders[] = '{'.$property['NAME'].':localpath}';
                    $placeholders[] = '{'.$property['NAME'].':filename}';
                    $placeholders[] = '{'.$property['NAME'].':image}';
                    $placeholders[] = '{'.$property['NAME'].':localimage}';
                    $placeholders[] = '{'.$property['NAME'].':count}';

					$parsed = array(
						'path' 			=> array(),
						'localpath' 	=> array(),
						'filename' 		=> array(),
						'image'			=> array(),
						'localimage'	=> array()
					);
					foreach ($value as $actualValue)
					{
						$filepath = substr_replace($actualValue, Uri::root(), 0, strlen(JPATH_SITE)+1);
						$filepath = str_replace(array('\\', '\\/', '//\\'), '/', $filepath);

						$parsed['path'][]   	= $filepath;
						$parsed['localpath'][]  = $actualValue;
						$parsed['filename'][]	= basename($actualValue);
						$parsed['image'][]		= '<img src="' . self::htmlEscape($filepath) . '">';
						$parsed['localimage'][]	= '<img src="' . self::htmlEscape($actualValue) . '">';
					}

					$values[] = implode($separator, $parsed['path']);
					$values[] = implode($separator, $parsed['localpath']);
					$values[] = implode($separator, $parsed['filename']);
					$values[] = implode($separator, $parsed['image']);
					$values[] = implode($separator, $parsed['localimage']);
					$values[] = count($value);

                    // Handle attach to email settings.
                    if (!empty($property['EMAILATTACH']))
                    {
                        if ($parts = explode(',', trim($property['EMAILATTACH'])))
                        {
                            if (in_array('useremail', $parts))
                            {
                                $userEmailUploads[] = $property['NAME'];
                            }

                            if (in_array('adminemail', $parts))
                            {
                                $adminEmailUploads[] = $property['NAME'];
                            }

                            if ($filtered = array_filter($parts, 'intval'))
                            {
                                foreach ($filtered as $emailId)
                                {
                                    if (!isset($additionalEmailUploads[$emailId]))
                                    {
                                        $additionalEmailUploads[$emailId] = array();
                                    }

                                    $additionalEmailUploads[$emailId][] = $property['NAME'];
                                }
                            }
                        }
                    }

					if ($hasMultiple)
					{
						foreach ($value as $index => $actualValue)
						{
							$placeholders[] = '{'.$property['NAME'] . '_' . $index . ':value}';
							$placeholders[] = '{'.$property['NAME'] . '_' . $index . ':path}';
							$placeholders[] = '{'.$property['NAME'] . '_' . $index . ':localpath}';
							$placeholders[] = '{'.$property['NAME'] . '_' . $index . ':filename}';
							$placeholders[] = '{'.$property['NAME'] . '_' . $index . ':image}';
							$placeholders[] = '{'.$property['NAME'] . '_' . $index . ':localimage}';

							$fileUrl = static::mailRoute('index.php?option=com_rsform&task=submissions.viewfile&hash=' . md5($submission->SubmissionId . $secret . $property['NAME']) . '&file=' . md5($actualValue) . $Itemid);

							$values[] = '<a href="'. $fileUrl . '">'.RSFormProHelper::htmlEscape(basename($actualValue)).'</a>';
							$values[] = $parsed['path'][$index];
							$values[] = $parsed['localpath'][$index];
							$values[] = $parsed['filename'][$index];
							$values[] = $parsed['image'][$index];
							$values[] = $parsed['localimage'][$index];
						}
					}
                }
            }
        }

		$user = Factory::getUser($submission->UserId);
		if (empty($user->id))
        {
            $user = Factory::getUser(0);
        }

		$confirmation_hash 	= md5($submission->SubmissionId.$formId.$submission->DateSubmitted);
		$hash_link 			= 'index.php?option=com_rsform&task=confirm&hash='.$confirmation_hash;
        $delete_link 		= 'index.php?option=com_rsform&view=deletesubmission&hash='.$submission->SubmissionHash;
		$confirmation 		= static::mailRoute($hash_link);
		$deletion 		    = static::mailRoute($delete_link);

		if (!$skip_globals)
		{
		    $global_placeholders = array(
                '{global:username}'     	 => $user->username,
                '{global:userid}'       	 => $user->id,
                '{global:useremail}'    	 => $user->email,
                '{global:fullname}'     	 => $user->name,
                '{global:userip}'       	 => $submission->UserIp,
                '{global:date_added}'   	 => RSFormProHelper::getDate($submission->DateSubmitted),
                '{global:utc_date_added}'    => $submission->DateSubmitted,
                '{global:sitename}'     	 => $mainframe->get('sitename'),
                '{global:siteurl}'      	 => Uri::root(),
                '{global:confirmation}' 	 => $confirmation,
                '{global:confirmation_hash}' => $confirmation_hash,
                '{global:confirmedip}'	     => $submission->ConfirmedIp,
                '{global:confirmeddate}'     => $submission->ConfirmedDate ? RSFormProHelper::getDate($submission->ConfirmedDate) : '',
                '{global:utc_confirmeddate}' => $submission->ConfirmedDate,
                '{global:deletion}'     	 => $deletion,
                '{global:deletion_hash}'     => $submission->SubmissionHash,
                '{global:submissionid}' 	 => $submission->SubmissionId,
                '{global:submission_id}' 	 => $submission->SubmissionId,
                '{global:mailfrom}'     	 => $mainframe->get('mailfrom'),
                '{global:fromname}'     	 => $mainframe->get('fromname'),
                '{global:formid}'       	 => $formId,
                '{global:language}'     	 => $submission->Lang,
				'{global:formtitle}'		 => $form->FormTitle
            );

		    $placeholders = array_merge($placeholders, array_keys($global_placeholders));
		    $values = array_merge($values, array_values($global_placeholders));
		}

		$mainframe->triggerEvent('onRsformAfterCreatePlaceholders', array(array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submission' => $submission)));

		if ($only_return_replacements)
        {
            return array($placeholders, $values);
        }

		if ($form->ConfirmSubmission && !empty($form->ConfirmSubmissionDefer))
		{
            $defer = json_decode($form->ConfirmSubmissionDefer);

            if (!is_array($defer))
            {
                $defer = array();
            }

            // If submission is confirmed, do not resend emails that have been already sent before user confirmed
            if ($submission->confirmed)
            {
                $defer = array_diff(array('UserEmail', 'AdminEmail', 'AdditionalEmails'), $defer);
            }
		}

        if (empty($defer))
        {
            $defer = array();
        }

		// We do this here again so we grab all placeholders, even those injected by plugins
		if ($form->UserEmailGenerate || $form->AdminEmailGenerate)
		{
			foreach ($components as $component)
			{
				if (!isset($all_data[$component->ComponentId]))
				{
					continue;
				}

				$property = $all_data[$component->ComponentId];

				$captionPlaceholder = '{' . $property['NAME'] . ':caption}';
				$valuePlaceholder = '{' . $property['NAME'] . ':value}';

				if ($component->ComponentTypeId == RSFORM_FIELD_GMAPS)
				{
					$valuePlaceholder = '{' . $property['NAME'] . ':map}';
				}

                if (in_array($component->ComponentTypeId, RSFormProHelper::$captchaFields) || $component->ComponentTypeId == RSFORM_FIELD_FREETEXT)
                {
                    continue;
                }

				$mainframe->triggerEvent('onRsformCreateEmailLine', array(array('form' => &$form, 'submission' => $submission, 'typeId' => $component->ComponentTypeId, 'captionPlaceholder' => &$captionPlaceholder, 'valuePlaceholder' => &$valuePlaceholder)));

				if (Factory::getDocument()->getDirection() === 'rtl')
				{
					$htmlTemplate = '<p dir="rtl"><strong>%s</strong> %s</p>' . "\n";
					$textTemplate = "%s: %s\n";
				}
				else
				{
					$htmlTemplate = "<p><strong>%s</strong> %s</p>\n";
					$textTemplate = "%s: %s\n";
				}

				if ($form->UserEmailGenerate)
				{
					$emailLine = $form->UserEmailMode ? $htmlTemplate : $textTemplate;
					$form->UserEmailText .= "{if {$valuePlaceholder}}" . sprintf($emailLine, $captionPlaceholder, $valuePlaceholder) . "{/if}";
				}
				if ($form->AdminEmailGenerate)
				{
					$emailLine = $form->AdminEmailMode ? $htmlTemplate : $textTemplate;
					$form->AdminEmailText .= "{if {$valuePlaceholder}}" . sprintf($emailLine, $captionPlaceholder, $valuePlaceholder) . "{/if}";
				}
			}
		}

		// RSForm! Pro Scripting - User Email Text
		// performance check
        foreach (array('UserEmailTo', 'UserEmailCC', 'UserEmailBCC', 'UserEmailFrom', 'UserEmailReplyTo', 'UserEmailReplyToName', 'UserEmailFromName', 'UserEmailText', 'UserEmailSubject') as $property)
        {
	        if (strpos($form->{$property}, '{/if}') !== false)
	        {
		        require_once __DIR__ . '/scripting.php';
		        RSFormProScripting::compile($form->{$property}, $placeholders, $values);
            }
        }

		$userEmail = array(
			'to'        	=> str_replace($placeholders, $values, $form->UserEmailTo),
			'cc'        	=> str_replace($placeholders, $values, $form->UserEmailCC),
			'bcc'       	=> str_replace($placeholders, $values, $form->UserEmailBCC),
			'from'      	=> str_replace($placeholders, $values, $form->UserEmailFrom),
			'replyto'   	=> str_replace($placeholders, $values, $form->UserEmailReplyTo),
			'replytoName'   => str_replace($placeholders, $values, $form->UserEmailReplyToName),
			'fromName'  	=> str_replace($placeholders, $values, $form->UserEmailFromName),
			'text'      	=> str_replace($placeholders, $values, $form->UserEmailText),
			'subject'   	=> str_replace($placeholders, $values, $form->UserEmailSubject),
			'mode'      	=> $form->UserEmailMode,
			'files'     	=> array(),
			'recipientName' => ''
		);

		// user cc
		if (strpos($userEmail['cc'], ',') !== false)
        {
            $userEmail['cc'] = explode(',', $userEmail['cc']);
        }

		// user bcc
		if (strpos($userEmail['bcc'], ',') !== false)
        {
            $userEmail['bcc'] = explode(',', $userEmail['bcc']);
        }

		$file = str_replace($placeholders, $values, $form->UserEmailAttachFile);
		if ($form->UserEmailAttach && file_exists($file))
        {
            $userEmail['files'][] = $file;
        }

		// Need to attach files
		// User Email
        if ($userEmailUploads)
        {
            foreach ($userEmailUploads as $name)
            {
                if (!empty($submission->values[$name]))
                {
					$userEmail['files'] = array_merge($userEmail['files'], RSFormProHelper::explode($submission->values[$name]));
                }
            }
        }

		// RSForm! Pro Scripting - Admin Email Text
		// performance check
		foreach (array('AdminEmailTo', 'AdminEmailCC', 'AdminEmailBCC', 'AdminEmailFrom', 'AdminEmailReplyTo', 'AdminEmailReplyToName', 'AdminEmailFromName', 'AdminEmailText', 'AdminEmailSubject') as $property)
		{
			if (strpos($form->{$property}, '{/if}') !== false)
			{
				require_once __DIR__ . '/scripting.php';
				RSFormProScripting::compile($form->{$property}, $placeholders, $values);
			}
		}

		$adminEmail = array(
			'to'        	=> str_replace($placeholders, $values, $form->AdminEmailTo),
			'cc'        	=> str_replace($placeholders, $values, $form->AdminEmailCC),
			'bcc'       	=> str_replace($placeholders, $values, $form->AdminEmailBCC),
			'from'      	=> str_replace($placeholders, $values, $form->AdminEmailFrom),
			'replyto'   	=> str_replace($placeholders, $values, $form->AdminEmailReplyTo),
			'replytoName'   => str_replace($placeholders, $values, $form->AdminEmailReplyToName),
			'fromName'  	=> str_replace($placeholders, $values, $form->AdminEmailFromName),
			'text'      	=> str_replace($placeholders, $values, $form->AdminEmailText),
			'subject'   	=> str_replace($placeholders, $values, $form->AdminEmailSubject),
			'mode'      	=> $form->AdminEmailMode,
			'files'     	=> array(),
			'recipientName' => ''
		);

		// admin cc
		if (strpos($adminEmail['cc'], ',') !== false)
        {
            $adminEmail['cc'] = explode(',', $adminEmail['cc']);
        }

		// admin bcc
		if (strpos($adminEmail['bcc'], ',') !== false)
        {
            $adminEmail['bcc'] = explode(',', $adminEmail['bcc']);
        }

		// Admin Email
        if ($adminEmailUploads)
        {
            foreach ($adminEmailUploads as $name)
            {
                if (!empty($submission->values[$name]))
                {
					$adminEmail['files'] = array_merge($adminEmail['files'], RSFormProHelper::explode($submission->values[$name]));
                }
            }
        }

		$mainframe->triggerEvent('onRsformBeforeUserEmail', array(array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'SubmissionId' => $SubmissionId, 'userEmail'=>&$userEmail)));

        if ($defer && in_array('UserEmail', $defer))
        {
            $userEmail['to'] = '';
        }

		// Script called before the User Email is sent.
		try
		{
			eval($form->UserEmailScript);
		}
		catch (Throwable $e)
		{
			Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
		}

		// mail users
		if ($userEmail['to'])
		{
			$recipients = !is_array($userEmail['to']) ? explode(',', $userEmail['to']) : $userEmail['to'];

			RSFormProHelper::sendMail($userEmail['from'], $userEmail['fromName'], $recipients, $userEmail['subject'], $userEmail['text'], $userEmail['mode'], !empty($userEmail['cc']) ? $userEmail['cc'] : null, !empty($userEmail['bcc']) ? $userEmail['bcc'] : null, $userEmail['files'], !empty($userEmail['replyto']) ? $userEmail['replyto'] : '', !empty($userEmail['replytoName']) ? $userEmail['replytoName'] : null, $userEmail['recipientName'], $formId);
		}
        $mainframe->triggerEvent('onRsformAfterUserEmail', array(array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'SubmissionId' => $SubmissionId, 'userEmail'=>&$userEmail)));

		$mainframe->triggerEvent('onRsformBeforeAdminEmail', array(array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'SubmissionId' => $SubmissionId, 'adminEmail'=>&$adminEmail)));

		if ($defer && in_array('AdminEmail', $defer))
		{
			$adminEmail['to'] = '';
		}

		// Script called before the Admin Email is sent.
		try
		{
			eval($form->AdminEmailScript);
		}
		catch (Throwable $e)
		{
			Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
		}

		//mail admins
		if ($adminEmail['to'])
		{
			$recipients = !is_array($adminEmail['to']) ? explode(',', $adminEmail['to']) : $adminEmail['to'];

			RSFormProHelper::sendMail($adminEmail['from'], $adminEmail['fromName'], $recipients, $adminEmail['subject'], $adminEmail['text'], $adminEmail['mode'], !empty($adminEmail['cc']) ? $adminEmail['cc'] : null, !empty($adminEmail['bcc']) ? $adminEmail['bcc'] : null, $adminEmail['files'], !empty($adminEmail['replyto']) ? $adminEmail['replyto'] : '', !empty($adminEmail['replytoName']) ? $adminEmail['replytoName'] : null, $adminEmail['recipientName'], $formId);
		}
        $mainframe->triggerEvent('onRsformAfterAdminEmail', array(array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'SubmissionId' => $SubmissionId, 'adminEmail'=>&$adminEmail)));

		// Additional emails
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__rsform_emails'))
            ->where($db->qn('type') . ' = ' . $db->q('additional'))
            ->where($db->qn('formId') . ' = ' . $db->q($formId))
            ->where($db->qn('from') . ' != ' . $db->q(''));
		if ($emails = $db->setQuery($query)->loadObjectList())
		{
			$translations = RSFormProHelper::getTranslations('emails', $formId, $submission->Lang);

			foreach ($emails as $email)
			{
			    foreach (array('fromname', 'subject', 'message', 'replytoname') as $value)
                {
                    if (isset($translations[$email->id . '.' . $value]))
                    {
                        $email->{$value} = $translations[$email->id . '.' . $value];
                    }
                }

				if (empty($email->fromname) || empty($email->subject) || empty($email->message))
				{
					continue;
				}

				// RSForm! Pro Scripting - Additional Email Text
				// performance check
				foreach (array('to', 'cc', 'bcc', 'from', 'replyto', 'replytoname', 'fromname', 'message', 'subject') as $property)
				{
					if ($email->{$property} && strpos($email->{$property}, '{/if}') !== false)
					{
						require_once __DIR__ . '/scripting.php';
						RSFormProScripting::compile($email->{$property}, $placeholders, $values);
					}
				}

				$additionalEmail = array(
					'to'        	=> str_replace($placeholders, $values, $email->to),
					'cc'        	=> str_replace($placeholders, $values, $email->cc),
					'bcc'       	=> str_replace($placeholders, $values, $email->bcc),
					'from'      	=> str_replace($placeholders, $values, $email->from),
					'replyto'   	=> str_replace($placeholders, $values, $email->replyto),
					'replytoName'   => str_replace($placeholders, $values, $email->replytoname),
					'fromName'  	=> str_replace($placeholders, $values, $email->fromname),
					'text'      	=> str_replace($placeholders, $values, $email->message),
					'subject'   	=> str_replace($placeholders, $values, $email->subject),
					'mode'      	=> $email->mode,
					'files'     	=> array(),
					'recipientName' => ''
				);

				if (isset($additionalEmailUploads, $additionalEmailUploads[$email->id]))
                {
                    foreach ($additionalEmailUploads[$email->id] as $name)
                    {
                        if (!empty($submission->values[$name]))
                        {
							$additionalEmail['files'] = array_merge($additionalEmail['files'], RSFormProHelper::explode($submission->values[$name]));
                        }
                    }
                }

				// additional cc
				if (strpos($additionalEmail['cc'], ',') !== false)
                {
                    $additionalEmail['cc'] = explode(',', $additionalEmail['cc']);
                }

				// additional bcc
				if (strpos($additionalEmail['bcc'], ',') !== false)
                {
                    $additionalEmail['bcc'] = explode(',', $additionalEmail['bcc']);
                }

				$mainframe->triggerEvent('onRsformBeforeAdditionalEmail', array(array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'SubmissionId' => $SubmissionId, 'additionalEmail'=>&$additionalEmail)));

				if ($defer && in_array('AdditionalEmails', $defer))
				{
					$additionalEmail['to'] = '';
				}

				try
				{
					eval($form->AdditionalEmailsScript);
				}
				catch (Throwable $e)
				{
					Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
				}

				// mail users
				if ($additionalEmail['to'])
				{
					$recipients = !is_array($additionalEmail['to']) ? explode(',', $additionalEmail['to']) : $additionalEmail['to'];

					RSFormProHelper::sendMail($additionalEmail['from'], $additionalEmail['fromName'], $recipients, $additionalEmail['subject'], $additionalEmail['text'], $additionalEmail['mode'], !empty($additionalEmail['cc']) ? $additionalEmail['cc'] : null, !empty($additionalEmail['bcc']) ? $additionalEmail['bcc'] : null, $additionalEmail['files'], !empty($additionalEmail['replyto']) ? $additionalEmail['replyto'] : '', !empty($additionalEmail['replytoName']) ? $additionalEmail['replytoName'] : null, $additionalEmail['recipientName'], $formId);
				}

                $mainframe->triggerEvent('onRsformAfterAdditionalEmail', array(array('form' => &$form, 'placeholders' => &$placeholders, 'values' => &$values, 'submissionId' => $SubmissionId, 'SubmissionId' => $SubmissionId, 'additionalEmail'=>&$additionalEmail)));
			}
		}

		return array($placeholders, $values);
	}

	public static function escapeArray(&$val, &$key)
	{
		$db = Factory::getDbo();
		$val = $db->escape($val);
		$key = $db->escape($key);
	}

	public static function quoteArray(&$val, $key) {
		static $db;
		if (!$db) {
			$db = Factory::getDbo();
		}

		$val = $db->q($val);
	}

	public static function componentExists($formId, $componentTypeId, $published = 1)
	{
		$formId = (int) $formId;
		$db     = Factory::getDbo();

        if (is_array($componentTypeId))
        {
            $componentTypeId = array_map('intval', $componentTypeId);
        }
        else
        {
            $componentTypeId = array((int) $componentTypeId);
        }

        $query = $db->getQuery(true)
            ->select($db->qn('ComponentId'))
            ->from($db->qn('#__rsform_components'))
            ->where($db->qn('ComponentTypeId') . ' IN (' . implode(',', $componentTypeId) . ')')
            ->where($db->qn('FormId') . ' = ' . $db->q($formId));
        if ($published)
        {
            $query->where($db->qn('Published') . ' = ' . $db->q(1));
        }

        return $db->setQuery($query)->loadColumn();
	}

	// conditions
	public static function getConditions($formId, $lang=null)
	{
	    require_once __DIR__ . '/conditions.php';

		return RSFormProConditions::getConditions($formId, $lang);
	}

	public static function showForm($formId, $val=array(), $validation=array())
	{
		$mainframe 	= Factory::getApplication();
		$doc 		= Factory::getDocument();
		$user 		= Factory::getUser();
		$u 			= RSFormProHelper::getURL();
		$formId 	= (int) $formId;
		$logged     = $user->id;
		$form 		= RSFormProHelper::getForm($formId);

		$lang 		  = RSFormProHelper::getCurrentLanguage();
		$translations = RSFormProHelper::getTranslations('forms', $form->FormId, $lang);
		if ($translations)
		{
			foreach ($translations as $field => $value)
			{
				if (isset($form->{$field}))
				{
					$form->{$field} = $value;
				}
			}
		}

		$mainframe->triggerEvent('onRsformFrontendBeforeShowForm', array($formId, &$form));

		$nonce = '';
		if (PluginHelper::isEnabled('system', 'httpheaders'))
		{
			$app    = Factory::getApplication();
			$plugin = PluginHelper::getPlugin('system', 'httpheaders');
			$params = new Registry();
			$params->loadString($plugin->params);

			$cspEnabled          = (int) $params->get('contentsecuritypolicy', 0);
			$cspClient           = (string) $params->get('contentsecuritypolicy_client', 'site');
			$nonceEnabled        = (int) $params->get('nonce_enabled', 0);

			if ($cspEnabled && ($app->isClient($cspClient) || $cspClient === 'both'))
			{
				if ($nonceEnabled)
				{
					$nonce = Factory::getApplication()->get('csp_nonce');
				}
			}
		}

		if ($form->JS)
		{
			if (strpos($form->JS, '{nonce}') !== false)
			{
				$form->JS = str_replace('{nonce}', $nonce, $form->JS);
			}
			RSFormProAssets::addCustomTag($form->JS);
		}
		if ($form->CSS)
		{
			if (strpos($form->CSS, '{nonce}') !== false)
			{
				$form->CSS = str_replace('{nonce}', $nonce, $form->CSS);
			}
			RSFormProAssets::addCustomTag($form->CSS);
		}

		if ($form->ScrollToError) {
			RSFormProAssets::addScriptDeclaration('RSFormPro.scrollToError = true;');
		}

        if (RSFormProHelper::getConfig('global.scroll_page')) {
            RSFormProAssets::addScriptDeclaration('RSFormPro.scrollPage = true;');

            if ($offset = (int) RSFormProHelper::getConfig('global.scroll_page_offset')) {
                RSFormProAssets::addScriptDeclaration('RSFormPro.scrollPageOffset = ' . json_encode($offset) . ';');
            }
        }

		RSFormProAssets::addStyleSheet(HTMLHelper::_('stylesheet', 'com_rsform/front.css', array('pathOnly' => true, 'relative' => true)));
		RSFormProAssets::addScript(HTMLHelper::_('script', 'com_rsform/script.js', array('pathOnly' => true, 'relative' => true)));

		// calendars
		$YUICalendars = RSFormProHelper::componentExists($formId, RSFORM_FIELD_CALENDAR);
		$jQueryCalendars = RSFormProHelper::componentExists($formId, RSFORM_FIELD_JQUERY_CALENDAR);
		$rangeSliders = RSFormProHelper::componentExists($formId, RSFORM_FIELD_RANGE_SLIDER);

		$formLayout = $form->FormLayout;

		unset($form->FormLayout);
		$errorMessage = $form->ErrorMessage;
		unset($form->ErrorMessage);

		$components = RSFormProHelper::getComponents($formId);

		$pages			= array();
		$page_progress  = array();
		$submits		= array();
		foreach ($components as $component)
		{
			if ($component->ComponentTypeId == RSFORM_FIELD_PAGEBREAK)
				$pages[] = $component->ComponentId;
			elseif ($component->ComponentTypeId == RSFORM_FIELD_SUBMITBUTTON)
				$submits[] = $component->ComponentId;
		}

		$find 	  = array();
		$replace  = array();

		try
		{
			eval($form->ScriptBeforeDisplay);
		}
		catch (Throwable $e)
		{
			Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
		}

		$start_page = 0;
		if (!empty($validation)) {
			foreach ($components as $component)
			{
				if (in_array($component->ComponentId, $validation)) {
					break;
				}
				if ($component->ComponentTypeId == RSFORM_FIELD_PAGEBREAK)
					$start_page++;
			}
		}

		// stores the error class names found in the form layout
		$layoutErrorClass = array();
		$fieldErrorClass  = array();

		$layoutName = (string) preg_replace('/[^A-Z0-9]/i', '', $form->FormLayoutName);
		
		// keep the loaded framework class for further purpose
		$layoutClassLoaded = false;
		if (file_exists(__DIR__.'/formlayouts/'.$layoutName.'.php')) {
			require_once __DIR__.'/formlayouts/'.$layoutName.'.php';

			$class = 'RSFormProFormLayout'.$layoutName;
			if (class_exists($class)) {
				$layout = new $class();
				
				$layoutClassLoaded = $layout;
				if ($form->LoadFormLayoutFramework) {
					$layout->loadFramework();
				}

				// Return the specific layout error class
				$layoutErrorClass[$layoutName] = $layout->errorClass;
				$fieldErrorClass[$layoutName] = $layout->fieldErrorClass;
			}
		} else {
			$layoutErrorClass[$layoutName] = '';
			$fieldErrorClass[$layoutName] = 'rsform-error';
		}
		
		if ($doc->getDirection() == 'rtl')
			RSFormProAssets::addStyleSheet(HTMLHelper::_('stylesheet', 'com_rsform/front-rtl.css', array('pathOnly' => true, 'relative' => true)));

		$hasAjax = (bool) $form->AjaxValidation;

		static $hasAjaxRoot = false;

		$all_data = RSFormProHelper::getComponentProperties($components);
		foreach ($components as $component) {
			if (in_array($component->ComponentTypeId, RSFormProHelper::$captchaFields))
			{
				if ($logged && $form->RemoveCaptchaLogged)
				{
					continue;
				}
			}

			$data 						= $all_data[$component->ComponentId];
			$data['componentTypeId'] 	= $component->ComponentTypeId;
			$data['ComponentTypeName'] 	= $component->ComponentTypeName;
			$data['Order'] 				= $component->Order;

			// Pagination
			if ($component->ComponentTypeId == RSFORM_FIELD_PAGEBREAK)
			{
				// Set flag to load Ajax scripts
				if (!empty($data['VALIDATENEXTPAGE']) && $data['VALIDATENEXTPAGE'] == 'YES') {
					$hasAjax = true;
				}
				$data['PAGES'] 	 	= $pages;
				$page_progress[]	= array(
				    'show' => !empty($data['DISPLAYPROGRESS']) && in_array($data['DISPLAYPROGRESS'], array('YES', 'AUTO')),
                    'text' => !empty($data['DISPLAYPROGRESSMSG']) ? $data['DISPLAYPROGRESSMSG'] : '',
                    'auto' => !empty($data['DISPLAYPROGRESS']) && $data['DISPLAYPROGRESS'] == 'AUTO'
                );
			}
			elseif ($component->ComponentTypeId == RSFORM_FIELD_SUBMITBUTTON)
			{
				$data['SUBMITS'] = $submits;
				if ($component->ComponentId == end($submits))
                {
                    $page_progress[] = array(
                        'show' => !empty($data['DISPLAYPROGRESS']) && in_array($data['DISPLAYPROGRESS'], array('YES', 'AUTO')),
                        'text' => !empty($data['DISPLAYPROGRESSMSG']) ? $data['DISPLAYPROGRESSMSG'] : '',
                        'auto' => !empty($data['DISPLAYPROGRESS']) && $data['DISPLAYPROGRESS'] == 'AUTO'
                    );
                }
			}

			// Error classes
			$errorClass = '';
			if (!empty($validation) && in_array($component->ComponentId, $validation)) {
				$errorClass = $layoutErrorClass[$layoutName];
			}
			$find[] = '{'.$component->name.':errorClass}';
			$replace[] 	= $errorClass;

			// Caption
			$caption = '';
			if (isset($data['SHOW']) && $data['SHOW'] == 'NO') {
				$caption = '';
			} elseif (isset($data['CAPTION'])) {
				$caption = $data['CAPTION'];
			}
			$find[] 	= '{'.$component->name.':caption}';
			$replace[] 	= $caption;

			// Body
			$out	   = '';
			$invalid   = in_array($component->ComponentId, $validation);

			// Some filtering in the field type
			$type 	= (string) preg_replace('/[^A-Z0-9_\.-]/i', '', (string) $data['ComponentTypeName']);
			$type 	= ltrim($type, '.');

			$layouts = array(
				// Path to the layout (overridden) class
				'RSFormProField'.$layoutName.$type => __DIR__.'/fields/'.strtolower($layoutName).'/'.strtolower($type).'.php',

				// Path to the fallback (basic) class
				'RSFormProField'.$type => __DIR__.'/fields/'.strtolower($type).'.php'
			);

			// For legacy reasons...
			$r = array(
				'ComponentTypeId' => $data['componentTypeId'],
				'Order'			  => isset($data['Order']) ? $data['Order'] : 0
			);

			$mainframe->triggerEvent('onRsformBackendBeforeCreateFrontComponentBody', array(array(
				'out' 			=> &$out,
				'formId' 		=> $formId,
				'componentId' 	=> $component->ComponentId,
				'data' 			=> &$data,
				'value' 		=> &$val
			)));

			$config = array(
				'formId' 			=> $formId,
				'componentId' 		=> $component->ComponentId,
				'data' 				=> $data,
				'value' 			=> $val,
				'invalid' 			=> $invalid,
				'errorClass' 		=> $layoutErrorClass[$layoutName],
				'fieldErrorClass' 	=> $fieldErrorClass[$layoutName]
			);

			foreach ($layouts as $class => $file) {
				if (file_exists($file)) {
					// If class doesn't exist, load the file
					if (!class_exists($class)) {
						require_once $file;
					}

					// Create the field
					$field = new $class($config);

					// Return the output
					$out .= $field->output;

					// do not load the other class again if one is already initiated
					break;
				}
			}

			$mainframe->triggerEvent('onRsformBackendAfterCreateFrontComponentBody', array(array(
				'out' 			=> &$out,
				'formId' 		=> $formId,
				'componentId' 	=> $component->ComponentId,
				'data' 			=> $data,
				'value' 		=> $val,
				'r'				=> $r,
				'invalid' 		=> $invalid
			)));

			$find[] 	= '{'.$component->name.':body}';
			$replace[] 	= $out;

			// Description
			$description = '';
			if (isset($data['SHOW']) && $data['SHOW'] == 'NO') {
				$description = '';
			} elseif (isset($data['DESCRIPTION'])) {
				$description = $data['DESCRIPTION'];
			}
			$find[] 	= '{'.$component->name.':description}';
			$replace[] 	= $description;

			// {component:descriptionhtml}
			$find[] = '{'.$component->name.':descriptionhtml}';
			$replace[] = self::htmlEscape($description);

			// Validation message
			$validationMessage 	= '';
			if (isset($data['SHOW']) && $data['SHOW'] == 'NO') {
				$validationMessage = '';
			} elseif (isset($data['VALIDATIONMESSAGE'])) {
				if (!empty($validation) && in_array($component->ComponentId, $validation)) {
					$validationMessage = '<span id="component'.$component->ComponentId.'" class="formError">'.$data['VALIDATIONMESSAGE'].'</span>';
				} else {
					$validationMessage = '<span id="component'.$component->ComponentId.'" class="formNoError">'.$data['VALIDATIONMESSAGE'].'</span>';
				}
			}
			$find[] 	= '{'.$component->name.':validation}';
			$replace[] 	= $validationMessage;
		}
		unset($all_data);


		$mainframe->triggerEvent('onRsformFrontendInitFormDisplay', array(array(
			'find'		 => &$find,
			'replace'	 => &$replace,
			'formLayout' => &$formLayout,
			'formId'	 => $formId
		)));

		// Global placeholders
		$global = array(
			'{global:formid}'		=> $form->FormId,
			'{global:formtitle}'	=> $form->FormTitle,
			'{global:username}'		=> $user->get('username'),
			'{global:userip}'		=> IpHelper::getIp(),
			'{global:userid}'		=> $user->get('id'),
			'{global:useremail}'	=> $user->get('email'),
			'{global:fullname}'		=> $user->get('name'),
			'{global:sitename}'		=> $mainframe->get('sitename'),
			'{global:siteurl}'		=> Uri::root(),
			'{global:mailfrom}'		=> $mainframe->get('mailfrom'),
			'{global:fromname}'		=> $mainframe->get('fromname')
		);

		$find 	 = array_merge($find, array_keys($global));
		$replace = array_merge($replace, array_values($global));

		// Error placeholder
		$error = '';
		if (!empty($validation)) {
			$error = $errorMessage;
            $error = str_replace('{error_count}', is_array($validation) ? count($validation) : 0, $error);
		} elseif ($hasAjax) {
			$error = '<div id="rsform_error_'.$formId.'" style="display: none;">'.$errorMessage.'</div>';
            $error = str_replace('{error_count}', '<span id="rsform_error_count_' . $formId . '"></span>', $error);
		}

		$find[] 	= '{error}';
		$replace[] 	= $error;

        // Check for {if} scripting inside Form Layout
        if (strpos($formLayout, '{/if}') !== false)
        {
            require_once __DIR__ . '/scripting.php';
            RSFormProScripting::compile($formLayout, $find, $replace);
        }

		// Replace all placeholders
		$formLayout = str_replace($find, $replace, $formLayout);

		$formLayout .= '<input type="hidden" name="form[formId]" value="'.$formId.'"/>';

		if ($layoutClassLoaded && is_callable(array($layoutClassLoaded, 'modifyForm')))
		{
			$layoutClassLoaded->modifyForm($form);
		}

		$CSSClass 	= $form->CSSClass ? ' class="'.RSFormProHelper::htmlEscape(trim($form->CSSClass)).'"' : '';
		$CSSId 		= $form->CSSId ? ' id="'.RSFormProHelper::htmlEscape(trim($form->CSSId)).'"' : '';
		$CSSName 	= $form->CSSName ? ' name="'.RSFormProHelper::htmlEscape(trim($form->CSSName)).'"' : '';
		$u 			= $form->CSSAction ? $form->CSSAction : $u;
		$CSSAdditionalAttributes = $form->CSSAdditionalAttributes ? ' '.trim($form->CSSAdditionalAttributes) : '';

		if (!empty($pages))
		{
			$total_pages 	  = count($pages)+1;
			$step			  = round(100/$total_pages, 2);
			$replace_progress = array('{page}', '{total}', '{percent}');
			$with_progress 	  = array(1, $total_pages, $step*1);

			$progress 		 = reset($page_progress);
			$progress_script = '';
			if ($layoutClassLoaded && $progress['auto']) {
				$progress['text'] = $layoutClassLoaded->progressContent;
			} 
			$formLayout = '<div id="rsform_progress_'.$formId.'" class="rsformProgress">'.($progress['show'] ? str_replace($replace_progress, $with_progress, $progress['text']) : '').'</div>'."\n".$formLayout;
			foreach ($page_progress as $p => $progress)
			{
				$progress['text'] = str_replace(array("\r", "\n"), array('', '\n'), addcslashes($progress['text'], "'"));
				if ($layoutClassLoaded && $progress['auto']) {
					$progress['text'] = $layoutClassLoaded->progressContent;
				}
				$replace_progress = array('{page}', '{total}', '{percent}');
				$with_progress 	  = array($p+1, $total_pages, $p+1 == $total_pages ? 100 : $step*($p+1));
				$progress_script .= "if (page == ".$p.") document.getElementById('rsform_progress_".$formId."').innerHTML = '".($progress['show'] ? str_replace($replace_progress, $with_progress, $progress['text']) : '')."';";
			}
			RSFormProAssets::addScriptDeclaration('function rsfp_showProgress_' . $formId . '(page) { ' . $progress_script . ' }');
		}

		$encType = '';
		if (RSFormProHelper::componentExists($formId, RSFORM_FIELD_FILEUPLOAD)) {
			$encType = ' enctype="multipart/form-data"';
		}

		$useCsrf = RSFormProHelper::getConfig('use_csrf');
		$token = $useCsrf ? HTMLHelper::_('form.token') : '';

		// Try to keep session alive, using try because Joomla! 4 will trigger exceptions when used in onAfterRender()
		if ($useCsrf)
		{
			try
			{
				HTMLHelper::_('behavior.keepalive');
			}
			catch (Exception $e)
			{

			}
		}

		$formLayout = '<form method="post" '.$CSSId.$CSSClass.$CSSName.$CSSAdditionalAttributes.$encType.' action="'.RSFormProHelper::htmlEscape($u).'">' . $formLayout . "\n" . $token . '</form>';

		require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/prices.php';
		if ($prices = RSFormProPrices::getInstance($formId)->getPrices()) {
			$script = '';
			foreach ($prices as $componentName => $values) {
				$script .= "RSFormProPrices['".addslashes($formId.'_'.$componentName)."'] = ".json_encode($values).";\n";
			}
			RSFormProAssets::addScriptDeclaration($script);
		}

		if ($YUICalendars || $jQueryCalendars)
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/calendar.php';

			// render the YUI Calendars
			if ($YUICalendars)
			{
				$calendar = RSFormProCalendar::getInstance('YUICalendar');
				RSFormProAssets::addScriptDeclaration($calendar->printInlineScript($formId));
			}

			// render the jQuery Calendars
			if ($jQueryCalendars)
			{
				$calendar = RSFormProCalendar::getInstance('jQueryCalendar');
				RSFormProAssets::addScriptDeclaration($calendar->printInlineScript($formId));
			}
		}
		
		if (!empty($rangeSliders)) {
			require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/rangeslider.php';
			$rangeSlider = RSFormProRangeSlider::getInstance();
			RSFormProAssets::addScriptDeclaration($rangeSlider->printInlineScript($formId));
		}

		if (!empty($pages)) {
			RSFormProAssets::addScriptDeclaration(sprintf('document.addEventListener(\'DOMContentLoaded\', function(){ RSFormPro.initialPage = true; rsfp_changePage(%d, %d, %d); RSFormPro.initialPage = false; });', $formId, $start_page, count($pages)));
		}

		if ($hasAjax && !$hasAjaxRoot) {
			$hasAjaxRoot = true;
			RSFormProAssets::addScriptDeclaration('RSFormPro.Ajax.URL = ' . json_encode(Route::_('index.php?option=com_rsform&task=ajaxValidate', false)) . ';');
		}

		$validationParams = array(
			'parent' => $layoutErrorClass[$layoutName],
			'field'  => $fieldErrorClass[$layoutName]
		);

		if ($form->AjaxValidation) {
		    RSFormProAssets::addScriptDeclaration(
"RSFormProUtils.addEvent(window, 'load', function(){
    RSFormPro.Ajax.overrideSubmit(" . $formId . ", " . json_encode($validationParams) . ", " . ($form->DisableSubmitButton ? 'true' : 'false') . ");
});");
		} else {
			RSFormProAssets::addScriptDeclaration(
"RSFormProUtils.addEvent(window, 'load', function(){
	RSFormPro.setHTML5Validation('" . $formId . "', " . ($form->DisableSubmitButton ? 'true' : 'false') . ", " . json_encode($validationParams) . ", " . count($pages) . ");  
});");
		}

		// Allow plugins to inject code with their own Ajax script
		$ajaxScript = '';
		$mainframe->triggerEvent('onRsformFrontendAJAXScriptCreate', array(array('script' => &$ajaxScript, 'formId' => $formId)));

		if ($hasAjax || $ajaxScript) {
			$script = 'ajaxExtraValidationScript['.$formId.'] = function(task, formId, data){ '."\n";
			$script .= 'var formComponents = {};'."\n";
			foreach ($components as $component) {
				if (in_array($component->ComponentTypeId, array(RSFORM_FIELD_BUTTON, RSFORM_FIELD_FILEUPLOAD, RSFORM_FIELD_FREETEXT, RSFORM_FIELD_HIDDEN, RSFORM_FIELD_SUBMITBUTTON, RSFORM_FIELD_TICKET, RSFORM_FIELD_PAGEBREAK))) {
					continue;
				}

				$script .= "formComponents[".$component->ComponentId."]='".$component->name."';";
			}
			$script .= "\n".'RSFormPro.Ajax.displayValidationErrors(formComponents, task, formId, data);'."\n";
			// has this been modified?
			if ($ajaxScript) {
				$script .= $ajaxScript;
			}
			$script .= '};'."\n";

			RSFormProAssets::addScriptDeclaration($script);
		}

        require_once __DIR__ . '/conditions.php';
		if ($conditions = RSFormProConditions::getConditions($formId, $lang))
		{
			RSFormProAssets::addScriptDeclaration(RSFormProConditions::buildJS($formId, $conditions));
		}
		unset($conditions);

		if ($calculations = RSFormProHelper::getCalculations($formId)) {
			require_once __DIR__.'/calculations.php';

			$script = 'function rsfp_Calculations' . $formId . '(){' . "\n";
			$script .= "\t var i, thevalue;\n";

			foreach ($calculations as $calculation) {
				$expression = RSFormProCalculations::expression($calculation, $formId);
				$script .= "\n".$expression."\n";
			}

			$script .= "\n".'}';
			$script .= RSFormProCalculations::getFields($calculations,$formId);
			$script .= "\n".'RSFormPro.Calculations.addEvents('.$formId.',rsfpCalculationFields'.$formId.');';
			$script .= "\n" . 'document.addEventListener(\'DOMContentLoaded\', rsfp_Calculations' . $formId . ');';

			RSFormProAssets::addScriptDeclaration($script);
		}

		try
		{
			eval($form->ScriptDisplay);
		}
		catch (Throwable $e)
		{
			Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
		}

		//Trigger Event - onBeforeFormDisplay
		$mainframe->triggerEvent('onRsformFrontendBeforeFormDisplay', array(array('formLayout'=>&$formLayout,'formId'=>$formId,'formLayoutName' => $form->FormLayoutName)));
		return $formLayout;
	}

	public static function showThankYouMessage($formId)
	{
		$mainframe 	= Factory::getApplication();
		$db 		= Factory::getDbo();
		$doc 		= Factory::getDocument();
		$session 	= Factory::getSession();
		$formId 	= (int) $formId;

		$query = $db->getQuery(true)
			->select($db->qn(array('ScrollToThankYou', 'ThankYouMessagePopUp', 'FormLayoutName')))
			->from($db->qn('#__rsform_forms'))
			->where($db->qn('FormId') . ' = ' . $db->q($formId));
		$form = $db->setQuery($query)->loadObject();

		RSFormProAssets::addStyleSheet(HTMLHelper::_('stylesheet', 'com_rsform/front.css', array('pathOnly' => true, 'relative' => true)));
		if ($doc->getDirection() == 'rtl')
		{
			RSFormProAssets::addStyleSheet(HTMLHelper::_('stylesheet', 'com_rsform/front-rtl.css', array('pathOnly' => true, 'relative' => true)));
		}

		$formparams = $session->get('com_rsform.formparams.formId'.$formId);
		$output = isset($formparams->thankYouMessage) ? base64_decode($formparams->thankYouMessage) : '';

		if ($formparams->loadLayoutFramework)
		{
            $layoutName = $form->FormLayoutName;
            if (file_exists(__DIR__ . '/formlayouts/' . $layoutName . '.php'))
            {
                require_once __DIR__ . '/formlayouts/' . $layoutName . '.php';

                $class = 'RSFormProFormLayout' . $layoutName;
                if (class_exists($class))
                {
                    $layout = new $class();

                    if (is_callable(array($layout, 'loadFramework')))
                    {
                        $layout->loadFramework();
                    }
                }
            }
        }

		// Clear
		$session->clear('com_rsform.formparams.formId'.$formId);

        if (RSFormProHelper::getConfig('global.trigger_thankyou_content_plugins'))
        {
            $output = HTMLHelper::_('content.prepare', $output, null, 'com_rsform.thankyou');
        }

		//Trigger Event - onAfterShowThankyouMessage
		$mainframe->triggerEvent('onRsformFrontendAfterShowThankyouMessage', array(array('output'=>&$output,'formId'=>&$formId)));

		if ($form->ScrollToThankYou)
		{
			// scroll the window to the Thank You Message
			RSFormProAssets::addScript(HTMLHelper::_('script', 'com_rsform/script.js', array('pathOnly' => true, 'relative' => true)));
			RSFormProAssets::addScriptDeclaration("RSFormProUtils.addEvent(window, 'load', function(){ RSFormPro.scrollToElement(document.getElementById('rsfp-thankyou-scroll{$formId}')); });");
		}

		if ($form->ThankYouMessagePopUp && !$form->ScrollToThankYou)
		{
			RSFormProAssets::addScript(HTMLHelper::_('script', 'com_rsform/script.js', array('pathOnly' => true, 'relative' => true)));
			RSFormProAssets::addScriptDeclaration("RSFormProUtils.addEvent(window, 'load', function(){ RSFormPro.showThankYouPopup(document.getElementById('rsfp-thankyou-popup-container{$formId}')); });");
		}

		return $output;
	}

	public static function processForm($formId)
	{
		$invalid    = array();
		$mainframe  = Factory::getApplication();
        $db         = Factory::getDbo();
		$formId     = (int) $formId;

		// Let's check the token
		if (RSFormProHelper::getConfig('use_csrf') && !Session::checkToken())
		{
			$invalid[] = true;

			$mainframe->enqueueMessage(Text::_('JINVALID_TOKEN_NOTICE'), 'warning');

			return $invalid;
		}

		$form         = RSFormProHelper::getForm($formId);
		$lang 		  = RSFormProHelper::getCurrentLanguage();
		$translations = RSFormProHelper::getTranslations('forms', $formId, $lang);
		if ($translations)
        {
            foreach ($translations as $field => $value)
            {
                if (isset($form->{$field}))
                {
                    $form->{$field} = $value;
                }
            }
        }

		$invalid = RSFormProHelper::validateForm($formId);

		$post = Factory::getApplication()->input->post->get('form', array(), 'array');

		//Trigger Event - onBeforeFormValidation
		$mainframe->triggerEvent('onRsformFrontendBeforeFormValidation', array(array('invalid'=>&$invalid, 'formId' => $formId, 'post' => &$post)));

		$_POST['form'] = $post;

		$isAjax = false;

		try
		{
			eval($form->ScriptProcess);
		}
		catch (Throwable $e)
		{
			Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
		}

		if (!empty($invalid))
		{
			return $invalid;
		}

		$post = $_POST['form'];

		//Trigger Event - onBeforeFormProcess
		$mainframe->triggerEvent('onRsformFrontendBeforeFormProcess', array(array('post' => &$post)));

		if (empty($invalid))
		{
			$user = Factory::getUser();

            $SubmissionHash = ApplicationHelper::getHash(UserHelper::genRandomPassword());

			// Add to db (submission)
			$date = Factory::getDate();

            $submission = (object) array(
                'FormId'         => $formId,
                'DateSubmitted'  => $date->toSql(),
                'UserIp'         => IpHelper::getIp(),
                'Username'       => $user->username,
                'UserId'         => $user->id,
                'Lang'           => RSFormProHelper::getCurrentLanguage(),
                'confirmed'      => $form->ConfirmSubmission ? 0 : 1,
                'ConfirmedIP'    => '',
                'ConfirmedDate'  => null,
                'SubmissionHash' => $SubmissionHash
            );

			$db->insertObject('#__rsform_submissions', $submission, 'SubmissionId');

			$SubmissionId = $submission->SubmissionId;

			// get the form components
			$formComponents = RSFormProHelper::getComponents($formId);
			// check if files have been submitted
			$files = Factory::getApplication()->input->files->get('form', null, 'raw');

			foreach ($formComponents as $component) {
				$type 	= (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $component->ComponentTypeName);
				$type 	= ltrim($type, '.');

				$fieldTypeClass = 'RSFormProField' . $type;
				$fieldTypeFile  = __DIR__ . '/fields/' . strtolower($type) . '.php';

				if (file_exists($fieldTypeFile))
				{
					// If class doesn't exist, load the file
					if (!class_exists($fieldTypeClass))
					{
						require_once $fieldTypeFile;
					}
					
					$config = array(
						'formId'        => $formId,
						'componentId'   => $component->ComponentId,
						'data'          => RSFormProHelper::getComponentProperties($component->ComponentId)
					);

					// access the field class
					$field = new $fieldTypeClass($config);

					$field->processBeforeStore($SubmissionId, $post, $files);
				}
			}

			//Trigger Event - onBeforeStoreSubmissions
			$mainframe->triggerEvent('onRsformFrontendBeforeStoreSubmissions', array(array('formId'=>$formId,'post'=>&$post,'SubmissionId'=>$SubmissionId)));

			// Add to db (values)
			foreach ($post as $key => $val)
			{
				$val = is_array($val) ? implode("\n", $val) : $val;
				$val = RSFormProHelper::stripJava($val);

                $object = (object) array(
                    'SubmissionId'  => $SubmissionId,
                    'FormId'        => $formId,
                    'FieldName'     => $key,
                    'FieldValue'    => $val
                );
                $db->insertObject('#__rsform_submission_values', $object);
			}

			//Trigger Event - onAfterStoreSubmissions
			$mainframe->triggerEvent('onRsformFrontendAfterStoreSubmissions', array(array('SubmissionId'=>$SubmissionId, 'formId'=>$formId)));

			// Send emails
			list($replace, $with) = RSFormProHelper::sendSubmissionEmails($SubmissionId);

			// RSForm! Pro Scripting - Thank You Message
			// performance check
			if (strpos($form->Thankyou, '{/if}') !== false) {
				require_once __DIR__.'/scripting.php';
				RSFormProScripting::compile($form->Thankyou, $replace, $with);
			}

			// Thank You Message
			$thankYouMessage = str_replace($replace, $with, $form->Thankyou);
			$form->ReturnUrl = str_replace($replace, $with, $form->ReturnUrl);

			// Set redirect link
			$u = RSFormProHelper::getURL();

			// Create the Continue button
			$continueButton = '';
			if ($form->ShowContinue)
			{
				// Create goto link
				$goto = 'document.location.reload(true);';

				if (!empty($form->ReturnUrl))
					$goto = "document.location='".addslashes($form->ReturnUrl)."';";

				// Continue button
				$continueButtonLabel = Text::_('RSFP_THANKYOU_BUTTON');
                $continueButton = '<input type="button" class="rsform-submit-button btn btn-primary" name="continue" value="'.Text::_('RSFP_THANKYOU_BUTTON').'" onclick="'.$goto.'"/>';
				if (strpos($continueButtonLabel, 'input')) {
					$continueButton = Text::sprintf('RSFP_THANKYOU_BUTTON',$goto);
				} else {
                    $layoutName = $form->FormLayoutName;
                    if (file_exists(__DIR__.'/formlayouts/'.$layoutName.'.php')) {
                        require_once __DIR__.'/formlayouts/'.$layoutName.'.php';

                        $class = 'RSFormProFormLayout'.$layoutName;
                        if (class_exists($class)) {
                            $layout = new $class();

                            $loadLayoutFramework = $form->LoadFormLayoutFramework;

                            if (is_callable(array($layout, 'generateButton')))
                            {
                                $continueButton = $layout->generateButton($goto);
                            }
                        }
                    }
				}
			}

			// get mappings data
			$mappings = RSFormProHelper::getMappings($formId);

			// get Post to another location
			$silentPost = RSFormProHelper::getSilentPost($formId);

			if ($form->Keepdata && !$form->KeepIP)
			{
				$query = $db->getQuery(true)
					->update($db->qn('#__rsform_submissions'))
					->set($db->qn('UserIp') . ' = ' . $db->q('0.0.0.0'))
					->where($db->qn('SubmissionId') . ' = ' . $db->q($SubmissionId));
				$db->setQuery($query)->execute();
			}

			if (!$form->Keepdata)
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';

				register_shutdown_function(array('RSFormProSubmissionsHelper', 'deleteSubmissions'), $SubmissionId);
			}

			try
			{
				eval($form->ScriptProcess2);
			}
			catch (Throwable $e)
			{
				Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
			}

			if ($form->ScrollToThankYou)
			{
				$scrollToElement = '<div id="rsfp-thankyou-scroll' . $formId . '"></div>';
				$thankYouMessage = $scrollToElement . $thankYouMessage . $continueButton;
			}
			else if ($form->ThankYouMessagePopUp && !$form->ScrollToThankYou)
			{
				// Create goto link
				$gotoLink = '';

				if ($form->ShowContinue)
				{
					if (!empty($form->ReturnUrl))
					{
						$gotoLink = $form->ReturnUrl;
					}
				}
				$gotoLink = '<input type="hidden" id="rsfp-thankyou-popup-return-link" value="'.RSFormProHelper::htmlEscape($gotoLink).'"/>';

				$thankYouMessage = '<div id="rsfp-thankyou-popup-container'.$formId.'">'.$thankYouMessage.$continueButton.$gotoLink.'</div>';
			}
			else
			{
				$thankYouMessage .= $continueButton;
			}

			// Mappings
			$mainframe->triggerEvent('onRsformBeforeMappings', array('SubmissionId' => $SubmissionId, 'formId' => $formId, 'mappings' => &$mappings));
			RSFormProHelper::doMappings($mappings, array('replace' => $replace, 'with' => $with));

			$mainframe->triggerEvent('onRsformBeforeSilentPost', array('SubmissionId' => $SubmissionId, 'formId' => $formId, 'silentPost' => &$silentPost));
			RSFormProHelper::doSilentPost($silentPost, array('replace' => $replace, 'with' => $with, 'post' => $post));

			// Get session object
			$session = Factory::getSession();

			// Populate data
			$formparams = (object) array(
                'loadLayoutFramework'   => !empty($loadLayoutFramework) ? 1 : 0,
				'submissionId' 		    => $SubmissionId,
				'redirectUrl'		    => !$form->ShowThankyou && $form->ReturnUrl ? $form->ReturnUrl : $u,
				'showSystemMessage'     => $form->ShowSystemMessage
			);

			// Store the Thank You Message if option is set
			if ($form->ShowThankyou) {
				$formparams->thankYouMessage = base64_encode($thankYouMessage);
			}

			// Store session data
			$session->set('com_rsform.formparams.formId'.$formId, $formparams);

			// Trigger - After form process
			$mainframe->triggerEvent('onRsformFrontendAfterFormProcess', array(array('SubmissionId' => $SubmissionId, 'formId' => $formId)));

			// If we didn't get redirected through a plugin, mark form as processed to display Thank You Message on next page load
			$formparams->formProcessed = true;

			// Store new session data
			$session->set('com_rsform.formparams.formId'.$formId, $formparams);

			if (!$form->ShowThankyou && $form->ReturnUrl)
			{
                $u = $form->ReturnUrl;
			}

			$mainframe->triggerEvent('onRsformBeforeReturnUrl', array($formId, $SubmissionId, $form, $u));
			$mainframe->redirect($u);
		}

		return false;
	}

    public static function getPreviewFields()
    {
		$formId = Factory::getApplication()->input->getInt('formId');

		$list[] = array(
			'value' => '',
			'text' => 'PLEASE_SELECT_FIELD'
		);

        if ($components = RSFormProHelper::getComponents($formId))
		{
            foreach ($components as $component)
			{
                if (!in_array($component->ComponentTypeId, array(RSFORM_FIELD_PREVIEW, RSFORM_FIELD_BUTTON, RSFORM_FIELD_SUBMITBUTTON, RSFORM_FIELD_PAGEBREAK, RSFORM_FIELD_TICKET, RSFORM_FIELD_HIDDEN, RSFORM_FIELD_FREETEXT)) && !in_array($component->ComponentTypeId, RSFormProHelper::$captchaFields))
				{
                    $list[] = array(
                        'value' => $component->ComponentId,
                        'text'  => ' ' . $component->name // space in front so that this field doesn't get translated
                    );
                }
            }
        }

		return self::createList($list);
    }

	public static function getComponents($formId) {
		static $components = array();

		if (!isset($components[$formId])) {
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			// need to get the component type name so that we can load the specific class
			$query->clear()
				->select($db->qn('p.PropertyValue', 'name'))
				->select($db->qn('c.ComponentId'))
				->select($db->qn('c.ComponentTypeId'))
				->select($db->qn('ct.ComponentTypeName'))
				->select($db->qn('c.Order'))
				->from($db->qn('#__rsform_properties', 'p'))
				->join('LEFT', $db->qn('#__rsform_components', 'c').' ON ('.$db->qn('c.ComponentId').' = '.$db->qn('p.ComponentId').')')
				->join('LEFT', $db->qn('#__rsform_component_types', 'ct').' ON ('.$db->qn('ct.ComponentTypeId').' = '.$db->qn('c.ComponentTypeId').')')
				->where($db->qn('c.FormId') . ' = ' . $db->q($formId))
				->where($db->qn('p.PropertyName') . ' = ' . $db->q('NAME'))
				->where($db->qn('c.Published') . ' = ' . $db->q('1'))
				->order($db->qn('c.Order') . ' ASC');
			$db->setQuery($query);
			$components[$formId] =  $db->loadObjectList();
		}

		return $components[$formId];
	}

	public static function getURL()
	{
		$uri = Uri::getInstance();
		return $uri->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'));
	}

	public static function verifyChecked($componentName, $value, $post)
	{
		if (isset($post[$componentName]))
		{
			if (is_array($post[$componentName]) && in_array($value, $post[$componentName]))
				return 1;

			if (!is_array($post[$componentName]) && $post[$componentName] == $value)
				return 1;

			return 0;
		}

		return 0;
	}

	/**
	 * @param $formId
	 * @param string $validationType
	 * @param int $SubmissionId
	 * @return array
	 * @throws Exception
	 */
	public static function validateForm($formId, $validationType = 'form', $SubmissionId = 0)
	{
		$db 	 	= Factory::getDbo();
		$invalid 	= array();
		$formId  	= (int) $formId;
        $post       = Factory::getApplication()->input->post->get('form', array(), 'array');

		if ($validationType == 'form')
        {
            $form = RSFormProHelper::getForm($formId);

            $_POST['form'] = $post;

	        try
	        {
		        eval($form->ScriptBeforeValidation);
	        }
	        catch (Throwable $e)
	        {
		        Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
	        }

            $post = $_POST['form'];

            Factory::getApplication()->input->post->set('form', $post);
        }

		$query = $db->getQuery(true);
		$query->select($db->qn('c.ComponentId'))
			->select($db->qn('c.ComponentTypeId'))
			->select($db->qn('ct.ComponentTypeName'))
			->from($db->qn('#__rsform_components', 'c'))
			->join('left', $db->qn('#__rsform_component_types', 'ct') . ' ON (' . $db->qn('c.ComponentTypeId') . ' = ' . $db->qn('ct.ComponentTypeId') . ')')
			->where($db->qn('FormId').'='.$db->q($formId))
			->where($db->qn('Published').'='.$db->q(1))
			->order($db->qn('Order').' '.$db->escape('asc'));

		// if $type is directory, we need to validate the fields that are editable in the directory
		if ($validationType == 'directory') {
			$subquery = $db->getQuery(true);
			$subquery->select($db->qn('componentId'))
				->from($db->qn('#__rsform_directory_fields'))
				->where($db->qn('formId').'='.$db->q($formId))
				->where($db->qn('editable').'='.$db->q(1));
			$query->where($db->qn('ComponentId').' IN ('.(string) $subquery.')');
		}

		$db->setQuery($query);

		if ($components = $db->loadObjectList('ComponentId'))
		{
			$componentIds = array_keys($components);
			// load properties
			$all_data = RSFormProHelper::getComponentProperties($componentIds);
			if (empty($all_data)) {
				return $invalid;
			}

			// load conditions
			if ($conditions = RSFormProHelper::getConditions($formId)) {
				foreach ($conditions as $condition) {
					if ($condition->details) {
						$condition_vars = array();
						foreach ($condition->details as $detail) {
							$isChecked 		  = RSFormProHelper::verifyChecked($detail->ComponentName, $detail->value, $post);
							$condition_vars[] = $detail->operator == 'is' ? $isChecked : !$isChecked;
						}
						// this check is performed like this
						// 'all' must be true (ie. no 0s in the array); 'any' can be true (ie. one value of 1 in the array will do)
						$result = $condition->condition == 'all' ? !in_array(0, $condition_vars) : in_array(1, $condition_vars);

						// if the item is hidden, no need to validate it
						if (($condition->action == 'show' && !$result) || ($condition->action == 'hide' && $result)) {
							foreach ($components as $i => $component) {
								if (in_array($component->ComponentId, $condition->component_id)) {
									// ... just remove it from the components array
									unset($components[$i]);
								}
							}
						}
					}
				}
			}

			// load validation rules
			$validations 	 = array_flip(RSFormProHelper::getValidationRules(true));
			$validationClass = RSFormProHelper::getValidationClass();

			// validate through components
			foreach ($components as $component)
			{
				$data 			= $all_data[$component->ComponentId];
				$required 		= !empty($data['REQUIRED']) && $data['REQUIRED'] == 'YES';
				$validationRule = !empty($data['VALIDATIONRULE']) ? $data['VALIDATIONRULE'] : '';

				$type 	= (string) preg_replace('/[^A-Z0-9_\.-]/i', '', (string) $component->ComponentTypeName);
				$type 	= ltrim($type, '.');

				$fieldTypeClass = 'RSFormProField' . $type;
				$fieldTypeFile  = __DIR__ . '/fields/' . strtolower($type) . '.php';

				if (file_exists($fieldTypeFile))
				{
					// If class doesn't exist, load the file
					if (!class_exists($fieldTypeClass))
					{
						require_once $fieldTypeFile;
					}

					$config = array(
						'formId'        => $formId,
						'componentId'   => $component->ComponentId,
						'data'          => $data,
						'value'			=> $post
					);

					// access the field class
					$field = new $fieldTypeClass($config);

					if (is_callable(array($field, 'processValidation')))
					{
						if (!$field->processValidation($validationType, $SubmissionId))
						{
							$invalid[] = $data['componentId'];
						}

						continue;
					}
				}

				// flag to check if we need to run the validation functions
				$runValidations = false;

                // Must have a value if it's required
				if ($required)
				{
					// Field is missing from request
					if (!isset($post[$data['NAME']]))
					{
						$invalid[] = $data['componentId'];
						continue;
					}

					// For convenience
                    $value = $post[$data['NAME']];

					// If it's an array, implode it
                    if (is_array($value))
                    {
                        $value = implode('', $value);
                    }

                    // Remove unwanted spaces
                    $value = trim($value);

                    // Field has no length
                    if (!strlen($value))
                    {
                        $invalid[] = $data['componentId'];
                        continue;
                    }

                    $runValidations = true;
				} else { // not required, perform checks only when something is selected
					// we have a value, make sure it's the correct one
					if (isset($post[$data['NAME']]) && !is_array($post[$data['NAME']]) && strlen(trim($post[$data['NAME']]))) {
						$runValidations = true;
					}
				}

				if ($runValidations && isset($validations[$validationRule]) && !call_user_func(array($validationClass, $validationRule), $post[$data['NAME']], isset($data['VALIDATIONEXTRA']) ? $data['VALIDATIONEXTRA'] : '', $data)) {
					$invalid[] = $data['componentId'];
					continue;
				}
			}
		}

		return $invalid;
	}

	public static function addClass(&$attributes, $className)
	{
		if (preg_match('#class="(.*?)"#is', $attributes, $matches))
			$attributes = str_replace($matches[0], str_replace($matches[1], $matches[1].' '.$className, $matches[0]), $attributes);
		else
			$attributes .= ' class="'.$className.'"';

		return $attributes;
	}

	public static function addOnClick(&$attributes, $onClick)
	{
		if (preg_match('#onclick="(.*?)"#is', $attributes, $matches))
			$attributes = str_replace($matches[0], str_replace($matches[1], $matches[1].'; '.$onClick, $matches[0]), $attributes);
		else
			$attributes .= ' onclick="'.$onClick.'"';

		return $attributes;
	}

	public static function stripJava($val) {
		$filtering = RSFormProHelper::getConfig('global.filtering');

		switch ($filtering)
		{
			default:
			case 'joomla':
				return ComponentHelper::filterText($val);
				break;

			case 'rsform':
				// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
				// this prevents some character re-spacing such as <java\0script>
				// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
				$val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);

				// straight replacements, the user should never need these since they're normal characters
				// this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
				$search = 'abcdefghijklmnopqrstuvwxyz';
				$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				$search .= '1234567890!@#$%^&*()';
				$search .= '~`";:?+/={}[]-_|\'\\';
				for ($i = 0; $i < strlen($search); $i++) {
					// ;? matches the ;, which is optional
					// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

					// &#x0040 @ search for the hex values
					$val = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
					// &#00064 @ 0{0,7} matches '0' zero to seven times
					$val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
				}

				// now the only remaining whitespace attacks are \t, \n, and \r
				// ([ \t\r\n]+)?
				$ra1 = Array('\/([ \t\r\n]+)?javascript', '\/([ \t\r\n]+)?vbscript', ':([ \t\r\n]+)?expression', '<([ \t\r\n]+)?applet', '<([ \t\r\n]+)?meta', '<([ \t\r\n]+)?xml', '<([ \t\r\n]+)?blink', '<([ \t\r\n]+)?link', '<([ \t\r\n]+)?style', '<([ \t\r\n]+)?script', '<([ \t\r\n]+)?embed', '<([ \t\r\n]+)?object', '<([ \t\r\n]+)?iframe', '<([ \t\r\n]+)?frame', '<([ \t\r\n]+)?frameset', '<([ \t\r\n]+)?ilayer', '<([ \t\r\n]+)?layer', '<([ \t\r\n]+)?bgsound', '<([ \t\r\n]+)?title', '<([ \t\r\n]+)?base');
				$ra2 = Array('onabort([ \t\r\n]+)?=', 'onactivate([ \t\r\n]+)?=', 'onafterprint([ \t\r\n]+)?=', 'onafterupdate([ \t\r\n]+)?=', 'onbeforeactivate([ \t\r\n]+)?=', 'onbeforecopy([ \t\r\n]+)?=', 'onbeforecut([ \t\r\n]+)?=', 'onbeforedeactivate([ \t\r\n]+)?=', 'onbeforeeditfocus([ \t\r\n]+)?=', 'onbeforepaste([ \t\r\n]+)?=', 'onbeforeprint([ \t\r\n]+)?=', 'onbeforeunload([ \t\r\n]+)?=', 'onbeforeupdate([ \t\r\n]+)?=', 'onblur([ \t\r\n]+)?=', 'onbounce([ \t\r\n]+)?=', 'oncellchange([ \t\r\n]+)?=', 'onchange([ \t\r\n]+)?=', 'onclick([ \t\r\n]+)?=', 'oncontextmenu([ \t\r\n]+)?=', 'oncontrolselect([ \t\r\n]+)?=', 'oncopy([ \t\r\n]+)?=', 'oncut([ \t\r\n]+)?=', 'ondataavailable([ \t\r\n]+)?=', 'ondatasetchanged([ \t\r\n]+)?=', 'ondatasetcomplete([ \t\r\n]+)?=', 'ondblclick([ \t\r\n]+)?=', 'ondeactivate([ \t\r\n]+)?=', 'ondrag([ \t\r\n]+)?=', 'ondragend([ \t\r\n]+)?=', 'ondragenter([ \t\r\n]+)?=', 'ondragleave([ \t\r\n]+)?=', 'ondragover([ \t\r\n]+)?=', 'ondragstart([ \t\r\n]+)?=', 'ondrop([ \t\r\n]+)?=', 'onerror([ \t\r\n]+)?=', 'onerrorupdate([ \t\r\n]+)?=', 'onfilterchange([ \t\r\n]+)?=', 'onfinish([ \t\r\n]+)?=', 'onfocus([ \t\r\n]+)?=', 'onfocusin([ \t\r\n]+)?=', 'onfocusout([ \t\r\n]+)?=', 'onhelp([ \t\r\n]+)?=', 'onkeydown([ \t\r\n]+)?=', 'onkeypress([ \t\r\n]+)?=', 'onkeyup([ \t\r\n]+)?=', 'onlayoutcomplete([ \t\r\n]+)?=', 'onload([ \t\r\n]+)?=', 'onlosecapture([ \t\r\n]+)?=', 'onmousedown([ \t\r\n]+)?=', 'onmouseenter([ \t\r\n]+)?=', 'onmouseleave([ \t\r\n]+)?=', 'onmousemove([ \t\r\n]+)?=', 'onmouseout([ \t\r\n]+)?=', 'onmouseover([ \t\r\n]+)?=', 'onmouseup([ \t\r\n]+)?=', 'onmousewheel([ \t\r\n]+)?=', 'onmove([ \t\r\n]+)?=', 'onmoveend([ \t\r\n]+)?=', 'onmovestart([ \t\r\n]+)?=', 'onpaste([ \t\r\n]+)?=', 'onpropertychange([ \t\r\n]+)?=', 'onreadystatechange([ \t\r\n]+)?=', 'onreset([ \t\r\n]+)?=', 'onresize([ \t\r\n]+)?=', 'onresizeend([ \t\r\n]+)?=', 'onresizestart([ \t\r\n]+)?=', 'onrowenter([ \t\r\n]+)?=', 'onrowexit([ \t\r\n]+)?=', 'onrowsdelete([ \t\r\n]+)?=', 'onrowsinserted([ \t\r\n]+)?=', 'onscroll([ \t\r\n]+)?=', 'onselect([ \t\r\n]+)?=', 'onselectionchange([ \t\r\n]+)?=', 'onselectstart([ \t\r\n]+)?=', 'onstart([ \t\r\n]+)?=', 'onstop([ \t\r\n]+)?=', 'onsubmit([ \t\r\n]+)?=', 'onunload([ \t\r\n]+)?=', 'style([ \t\r\n]+)?=');
				$ra = array_merge($ra1, $ra2);

				foreach ($ra as $tag)
				{
					$pattern = '#'.$tag.'#i';
					preg_match_all($pattern, $val, $matches);

					foreach ($matches[0] as $match)
						$val = str_replace($match, substr($match, 0, 2).'<x>'.substr($match, 2), $val);
				}

				return $val;
				break;

			case 'none':
				return $val;
				break;
		}
	}

	public static function getTranslations($reference, $formId, $lang, $select = 'value')
	{
		static $selections = array();
		static $langs = array();

		$formId = (int) $formId;
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Do not grab translations if the form is in the same language as the translation.
		if (!isset($langs[$formId])) {

			$query->clear()
				->select($db->qn('Lang'))
				->from('#__rsform_forms')
				->where($db->qn('FormId').' = '.$db->q($formId));
			$db->setQuery($query);
			$langs[$formId] = $db->loadResult();
		}

		$disable_multilanguage 	= RSFormProHelper::getConfig('global.disable_multilanguage');
		$default_language 		= RSFormProHelper::getConfig('global.default_language');

		if ($disable_multilanguage && $default_language == 'en-GB')
		{
			return false;
		}
		elseif (!$disable_multilanguage && $langs[$formId] == $lang)
		{
			return false;
		}
		elseif ($disable_multilanguage && $default_language != 'en-GB')
		{
			$lang = $default_language;
		}

		// build the reference hash
		$hash = md5($reference.$formId.$lang.$select);

		if (!isset($selections[$hash]))
		{
			$selections[$hash] = array();

			// build the proper SQL Query
			$query->clear()
				->select('*')
				->from('#__rsform_translations')
				->where($db->qn('form_id').' = '.$db->q($formId))
				->where($db->qn('lang_code').' = '.$db->q($lang))
				->where($db->qn('reference').' = '.$db->q($reference));
			$db->setQuery($query);

			if ($results = $db->loadObjectList())
			{
				foreach ($results as $result)
				{
					$selections[$hash][$result->reference_id] = ($select == '*') ? $result : (isset($result->$select) ? $result->$select : false);
				}
			}
		}

		return $selections[$hash];
	}

	public static function getTranslatableProperties()
	{
	    static $translatable;

	    if (!$translatable)
        {
            $translatable = array('LABEL', 'RESETLABEL', 'PREVBUTTON', 'NEXTBUTTON', 'CAPTION', 'DESCRIPTION', 'VALIDATIONMESSAGE', 'DEFAULTVALUE', 'ITEMS', 'TEXT', 'REFRESHTEXT', 'DISPLAYPROGRESSMSG', 'WIRE', 'SHOWDAYPLEASE', 'SHOWMONTHPLEASE', 'SHOWYEARPLEASE', 'POPUPLABEL', 'PLACEHOLDER', 'INITIALCOUNTRY', 'QUESTIONS', 'ANSWERS');

            Factory::getApplication()->triggerEvent('onRsformBackendGetTranslatableProperties', array(&$translatable));
        }

        return $translatable;
	}

	public static function translateIcon()
	{
		if (RSFormProHelper::getConfig('global.disable_multilanguage'))
		{
			return '';
		}

		return '<span class="rsficon rsficon-flag fieldHasTooltip rsfp-translatable" data-title="' . Text::_('RSFP_TRANSLATABLE_TITLE') . '" data-content="' . Text::_('RSFP_THIS_ITEM_IS_TRANSLATABLE') . '"></span>';
	}

	public static function mappingsColumns($config, $method, $row = null)
	{
		require_once __DIR__.'/mappings.php';

		return RSFormProMappings::mappingsColumns($config, $method, $row);
	}

	public static function getMappingQuery($row, $config = array())
	{
		require_once __DIR__.'/mappings.php';

		return RSFormProMappings::getMappingQuery($row, $config);
	}

	public static function escapeSql(&$value)
	{
		static $db;
		if (!$db) {
			$db = Factory::getDbo();
		}

		$value = $db->escape($value);
	}

	public static function sendMail($from, $fromname, $recipient, $subject, $body, $mode = 0, $cc = null, $bcc = null, $attachment = null, $replyto = null, $replytoname = null, $recipientname = '', $formId = null)
	{
		static $initLogging;
		static $hasLogging;
		static $verboseLogging;

		if (!$initLogging)
		{
			$initLogging 	= true;
			$hasLogging 	= RSFormProHelper::getConfig('logging');
			$verboseLogging = RSFormProHelper::getConfig('logging_verbose');

			Log::addLogger(array('text_file' => 'rsform_email_log.php'), Log::ALL, array('com_rsform'));
		}

		try
		{
			// Get a Mail instance
			$mail = Factory::getMailer();

			// Allow this to be overridden
			Factory::getApplication()->triggerEvent('onRsformCreateMailer', array(array(
				'mailer' 		=> &$mail,
				'from' 			=> &$from,
				'fromname' 		=> &$fromname,
				'recipient' 	=> &$recipient,
				'subject' 		=> &$subject,
				'body' 			=> &$body,
				'mode' 			=> &$mode,
				'cc' 			=> &$cc,
				'bcc' 			=> &$bcc,
				'attachment' 	=> &$attachment,
				'replyto' 		=> &$replyto,
				'replytoname'	=> &$replytoname,
				'formId'		=> $formId
			)));

			$mail->ClearReplyTos();

            if (!is_array($from))
            {
                $from = trim($from);
            }
            if (!is_array($fromname))
            {
                $fromname = trim($fromname);
            }

			/**
			 * Apparently there are 2 issues:
			 * 1) Some hosts use these in disable_functions and PHPMailer calls them when specifying $this->Sender
			 * 2) Some hosts need to specify $this->Sender otherwise Return-Path gets set to whatever the server address is and fails SPF checks
             * 3) Some hosts DO NOT ALLOW $this->Sender to be specified and throw a "Could not instantiate mail function" error unless you guess the correct email address that's allowed - in this case leaving $from and $fromname blank will not touch the inherited properties from the Factory::createMailer() and should work
			**/
			if (function_exists('escapeshellcmd') && is_callable('escapeshellcmd') && function_exists('escapeshellarg') && is_callable('escapeshellarg'))
			{
				$autoSender = true;
			}
			else
			{
				$autoSender = false;
			}
            if ($from)
            {
	            $mail->setSender(array($from, $fromname, $autoSender));
            }

			$mail->setSubject($subject);
			$mail->setBody($body);

			// Are we sending the email as HTML?
			if ($mode)
			{
				$mail->IsHTML(true);

                if (RSFormProHelper::getConfig('multipart_mails'))
                {
                    $mail->AltBody = strip_tags($body);
                }
			}

			// Some cleanup
			foreach (array('recipient', 'cc', 'bcc') as $array)
			{
				if (is_array($$array))
				{
					// Remove empty values
					$$array = array_filter($$array);

					// Remove whitespace
					$$array = array_filter(array_map('trim', $$array));

					// If it's not an email, remove it
                    $newArray = array();
                    foreach ($$array as $item)
					{
                        try
						{
							if (MailHelper::isEmailAddress($item))
							{
                                $newArray[] = $item;
							}
                        }
                        catch (Exception $e)
						{

                        }
                    }
                    $$array = array_unique($newArray);
				}
			}

			$mail->addRecipient($recipient, $recipientname);
			$mail->addCC($cc);
			$mail->addBCC($bcc);
			// If we leave $type = '' then PHPMailer will try to auto-detect the mime type.
			if ($attachment)
			{
				$mail->addAttachment($attachment, '', 'base64', '');
			}

			// Take care of reply email addresses
			$mail->ClearReplyTos();
			if ($replyto || $replytoname)
			{
				if ($replyto === null)
				{
					$replyto = array();
				}
				if ($replytoname === null)
				{
					$replytoname = array();
				}
				if (!is_array($replyto))
				{
					$replyto = explode(',', $replyto);
				}

				if (!is_array($replytoname))
				{
					$replytoname = explode(',', $replytoname);
				}

                foreach ($replyto as $i => $replyToEmail)
                {
                    $replyToEmail = trim($replyToEmail);

                    // Remove empty lines or if it's not an email address
                    try
					{
						if (empty($replyToEmail) || !MailHelper::isEmailAddress($replyToEmail))
						{
							continue;
						}
                    }
                    catch (Exception $e)
					{
                        continue;
                    }


	                $mail->addReplyTo($replyToEmail, isset($replytoname[$i]) ? trim($replytoname[$i]) : '');
                }
			}

			if ($hasLogging)
			{
				try
				{
					Log::add(sprintf('Form ID #%d Sending email from %s to %s', $formId, (string) $from, is_array($recipient) ? implode(', ', array_values($recipient)) : (string) $recipient), Log::INFO, 'com_rsform');

					if ($verboseLogging)
					{
						Log::add(sprintf('Arguments:\n%s', print_r(func_get_args(), true)), Log::DEBUG, 'com_rsform');
					}
				}
				catch (Exception $logException)
				{
					Factory::getApplication()->enqueueMessage($logException->getMessage(), 'warning');
				}
			}

			return $mail->Send();
		} catch (Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			if ($hasLogging)
			{
				try
				{
					Log::add(sprintf('Form ID #%d Error sending email from %s to %s - message returned is %s', $formId, (string) $from, is_array($recipient) ? implode(', ', array_values($recipient)) : (string) $recipient, $e->getMessage()), Log::ERROR, 'com_rsform');
				}
				catch (Exception $logException)
				{
					Factory::getApplication()->enqueueMessage($logException->getMessage(), 'warning');
					return false;
				}
			}

			return false;
		}
	}

	public static function renderHTML() {
		$args = func_get_args();
		if ($args[0] == 'select.booleanlist') {
			// 0 - type
			// 1 - name
			// 2 - additional
			// 3 - value
			// 4 - yes
			// 5 - no

			// get the radio element
			$radio = FormHelper::loadFieldType('radio');

			// setup the properties
			$name	 	= htmlspecialchars($args[1], ENT_COMPAT, 'utf-8');
			$additional = isset($args[2]) ? (string) $args[2] : '';
			$value		= $args[3];
			$yes 	 	= isset($args[4]) ? htmlspecialchars($args[4], ENT_COMPAT, 'utf-8') : 'JYES';
			$no 	 	= isset($args[5]) ? htmlspecialchars($args[5], ENT_COMPAT, 'utf-8') : 'JNO';

			static $form;

			if (is_null($form))
			{
				$form = new Form('rsformradioform');
			}

			$radio->setForm($form);

			// prepare the xml
			$element = new SimpleXMLElement('<field name="'.$name.'" type="radio" class="btn-group btn-group-yesno"><option '.$additional.' value="0">'.$no.'</option><option '.$additional.' value="1">'.$yes.'</option></field>');

			// run
			$radio->setup($element, $value);

			return $radio->input;
		}
	}

	public static function getAllDirectoryFields($formId) {
		$db		= Factory::getDbo();
		static $cache = array();

		if (!isset($cache[$formId])) {
		    $excludedFields = array(
                RSFORM_FIELD_BUTTON,
                RSFORM_FIELD_CAPTCHA,
				RSFORM_FIELD_HASHCASH,
                RSFORM_FIELD_SUBMITBUTTON,
				RSFORM_FIELD_BUTTON,
                RSFORM_FIELD_PAGEBREAK,
                RSFORM_FIELD_PREVIEW
            );

            Factory::getApplication()->triggerEvent('onRsformBackendGetDirectoryExcludedFields', array(&$excludedFields, $formId));

			$query = $db->getQuery(true);
			$query->select($db->qn('p.PropertyValue','FieldName'))
				->select($db->qn('p.ComponentId','FieldId'))
				->select($db->qn('c.ComponentTypeId','FieldType'))
				->from($db->qn('#__rsform_components','c'))
				->join('left', $db->qn('#__rsform_properties','p').' ON '.$db->qn('c.ComponentId').' = '.$db->qn('p.ComponentId'))
				->where($db->qn('c.FormId').'='.$db->q($formId))
				->where($db->qn('p.PropertyName').' = '.$db->q('NAME'))
				->where($db->qn('c.ComponentTypeId').' NOT IN (' . implode(',', $excludedFields) . ')')
				->where($db->qn('c.Published').'='.$db->q(1))
				->order($db->qn('c.Order').' '.$db->escape('asc'));
			$db->setQuery($query);
			$cache[$formId] = $db->loadObjectList('FieldId');

			$data = RSFormProHelper::getComponentProperties(array_keys($cache[$formId]));
			foreach ($cache[$formId] as $FieldId => $field) {
				$properties =& $data[$FieldId];
				$caption = isset($properties['CAPTION']) ? $properties['CAPTION'] : '';

				$cache[$formId][$FieldId]->FieldCaption = $caption;
			}

			// Add #__rsform_submissions headers.
			$headers = self::getDirectoryStaticHeaders();
            $form = RSFormProHelper::getForm($formId);
            if (!$form->ConfirmSubmission)
            {
                unset($headers[RSFORM_STATIC_CONFIRMED], $headers[RSFORM_STATIC_CONFIRMEDIP], $headers[RSFORM_STATIC_CONFIRMEDDATE]);
            }
			foreach ($headers as $index => $header) {
				$cache[$formId][$index] = (object) array(
					'FieldName' 	=> $header,
					'FieldId'		=> $index,
					'FieldType' 	=> 0,
					'FieldCaption' 	=> Text::_('RSFP_'.$header)
				);
			}

			Factory::getApplication()->triggerEvent('onRsformBackendGetAllDirectoryFields', array(&$cache[$formId], $formId));
		}

		return $cache[$formId];
	}

	public static function getDirectoryStaticHeaders() {
		return array(
			RSFORM_STATIC_DATESUBMITTED => 'DateSubmitted',
			RSFORM_STATIC_USERIP => 'UserIp',
			RSFORM_STATIC_USERNAME => 'Username',
			RSFORM_STATIC_USERID => 'UserId',
			RSFORM_STATIC_LANGUAGE => 'Lang',
			RSFORM_STATIC_CONFIRMED => 'confirmed',
			RSFORM_STATIC_SUBMISSIONID => 'SubmissionId',
			RSFORM_STATIC_CONFIRMEDIP => 'ConfirmedIp',
			RSFORM_STATIC_CONFIRMEDDATE => 'ConfirmedDate'
		);
	}

	public static function getDirectoryFields($formId) {
		static $cache = array();

		if (!isset($cache[$formId])) {
			$db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->qn('#__rsform_directory_fields'))
                ->where($db->qn('formId') . ' = ' . $db->q($formId))
                ->order($db->qn('ordering') . ' ' . $db->escape('ASC'));
			$db->setQuery($query);
			$currentFields = $db->loadObjectList('componentId');

			$allFields = self::getAllDirectoryFields($formId);
			if ($diffFields = array_diff(array_keys($currentFields), array_keys($allFields))) {
				foreach ($diffFields as $fieldId) {
					unset($currentFields[$fieldId]);
				}
			}

			foreach ($allFields as $field) {
				// Hidden fields don't have a caption
				if (in_array($field->FieldType, array(RSFORM_FIELD_HIDDEN, RSFORM_FIELD_TICKET, RSFORM_FIELD_FREETEXT))) {
					$field->FieldCaption = $field->FieldName;
				}

				// Submission ID is not editable.
				if ($field->FieldId == RSFORM_STATIC_SUBMISSIONID)
				{
					$allowEdit = false;
				}
				else
				{
					$allowEdit = true;
				}

				if (!isset($currentFields[$field->FieldId])) { // field has been added after, add it to the end of the list
					$currentFields[] = (object) array(
						'FieldId' 		=> $field->FieldId,
						'FieldName' 	=> $field->FieldName,
						'FieldType'		=> $field->FieldType,
						'FieldCaption' 	=> $field->FieldCaption,
						'formId' 		=> $formId,
						'componentId' 	=> $field->FieldId,
						'viewable' 		=> 0,
						'searchable' 	=> 0,
						'editable' 		=> 0,
						'indetails' 	=> 0,
						'incsv' 		=> 0,
                        'sort'          => 0,
						'ordering' 		=> count($currentFields)+1,
						'allowEdit'     => $allowEdit,
					);
				} else { // just set the name & id for reference
					$currentFields[$field->FieldId]->FieldId 		= $field->FieldId;
					$currentFields[$field->FieldId]->FieldName 		= $field->FieldName;
					$currentFields[$field->FieldId]->FieldCaption 	= $field->FieldCaption;
					$currentFields[$field->FieldId]->FieldType 		= $field->FieldType;
					$currentFields[$field->FieldId]->allowEdit      = $allowEdit;
				}
			}

			// this is to reset the indexes (0, 1, 2, 3)
			$cache[$formId] = array_merge($currentFields, array());
		}

		return $cache[$formId];
	}

	public static function getDirectoryFormProperties($formId, $raw = false)
	{
		static $results = array();

		if (!isset($results[$formId]))
		{
			$results[$formId] = array();

			$db = Factory::getDbo();

			// form multiple separator
			$query = $db->getQuery(true)
				->select($db->qn('MultipleSeparator'))
				->from($db->qn('#__rsform_forms'))
				->where($db->qn('FormId') . ' = ' . $db->q($formId));
			$results[$formId]['multipleSeparator'] = str_replace(array('\n', '\r', '\t'), array("\n", "\r", "\t"), $db->setQuery($query)->loadResult());

			$query = $db->getQuery(true)
				->select($db->qn('ComponentId'))
				->select($db->qn('ComponentTypeId'))
				->from($db->qn('#__rsform_components'))
				->where($db->qn('FormId') . ' = ' . $db->q($formId))
				->order($db->qn('Order') . ' ASC');

			$results[$formId]['uploadFields']	= array();
			$results[$formId]['multipleFields']	= array();
			$results[$formId]['textareaFields']	= array();

			if ($components = $db->setQuery($query)->loadObjectList('ComponentId'))
			{
				$properties = RSFormProHelper::getComponentProperties(array_keys($components), false);
				foreach ($properties as $componentId => $data)
				{
					// Upload fields
					if ($components[$componentId]->ComponentTypeId == RSFORM_FIELD_FILEUPLOAD)
					{
						$results[$formId]['uploadFields'][] = $data['NAME'];
					}
					// Multiple fields
					elseif (in_array($components[$componentId]->ComponentTypeId, array(RSFORM_FIELD_SELECTLIST, RSFORM_FIELD_CHECKBOXGROUP, RSFORM_FIELD_SURVEYTABLE)) || isset($data['ITEMS']))
					{
						$results[$formId]['multipleFields'][] = $data['NAME'];
					}
					elseif ($components[$componentId]->ComponentTypeId == RSFORM_FIELD_TEXTAREA)
					{
						if (!empty($data['WYSIWYG']) && $data['WYSIWYG'] =='NO')
						{
							$results[$formId]['textareaFields'][] = $data['NAME'];
						}
					}
				}
			}

			$results[$formId]['secret'] = Factory::getApplication()->get('secret');
		}

		return $raw ? $results[$formId] : array(
			$results[$formId]['multipleSeparator'],
			$results[$formId]['uploadFields'],
			$results[$formId]['multipleFields'],
			$results[$formId]['textareaFields'],
			$results[$formId]['secret']
		);
	}

	public static function canEdit($formId, $submissionId)
    {
        $user 		 = Factory::getUser();
        $user_groups = Access::getGroupsByUser($user->get('id'));

		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
		$submission = RSFormProSubmissionsHelper::getSubmission($submissionId, false);

		// Submission does not exist, can't allow editing
		if (!$submission)
		{
			return false;
		}

        if ($groups = static::getDirectoryGroups($formId, 'edit'))
        {
            $registry = new Registry;
            $registry->loadString($groups);

            if ($groups = $registry->toArray())
            {
                // Check if the user can edit its own submissions
                if (in_array('own', $groups) && $submission->UserId == $user->get('id') && !$user->get('guest'))
                {
					return true;
                }

                // Check if the current group can edit submissions
                if ($user_groups && $groups && array_intersect($user_groups, $groups))
                {
                	return true;
                }
            }
        }

        return false;
    }

    public static function canDelete($formId, $submissionId)
    {
        $user        = Factory::getUser();
        $user_groups = Access::getGroupsByUser($user->get('id'));

		require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
		$submission = RSFormProSubmissionsHelper::getSubmission($submissionId, false);

		// Submission does not exist, can't allow deleting
		if (!$submission)
		{
			return false;
		}

        if ($groups = static::getDirectoryGroups($formId, 'delete'))
        {
            $registry = new Registry;
            $registry->loadString($groups);

            if ($groups = $registry->toArray())
            {
                // Check if the user can delete its own submissions
				if (in_array('own', $groups) && $submission->UserId == $user->get('id') && !$user->get('guest'))
				{
					return true;
				}

                // Check if the current user can delete submissions
				if ($user_groups && $groups && array_intersect($user_groups, $groups))
				{
					return true;
				}
            }
        }

        return false;
    }

    public static function getDirectoryGroups($formId, $type = 'edit')
    {
        static $cache = array();

        if (!isset($cache[$formId]))
        {
            $cache[$formId] = (object) array(
                'edit'   => null,
                'delete' => null
            );

            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->qn('groups'))
                ->select($db->qn('DeletionGroups'))
                ->from($db->qn('#__rsform_directory'))
                ->where($db->qn('formId') . ' = ' . $db->q($formId));

            if ($result = $db->setQuery($query)->loadObject())
            {
                $cache[$formId]->edit = $result->groups;
                $cache[$formId]->delete = $result->DeletionGroups;
            }
        }

        return $cache[$formId]->{$type};
    }

	public static function getCalculations($formId) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__rsform_calculations'))
            ->where($db->qn('formId') . ' = '. $db->q($formId))
            ->order($db->qn('ordering'));

		return $db->setQuery($query)->loadObjectList();
	}

	public static function getRelativeUploadPath($destination) {
		// Relative path
		// First check - Unix server and the path doesn't start with /
		// Second check - Windows server, path doesn't start with DRIVE:
        if (strlen($destination))
        {
	        if ((DIRECTORY_SEPARATOR === '/' && substr($destination, 0, 1) !== '/') || (DIRECTORY_SEPARATOR === '\\' && substr($destination, 1, 1) != ':'))
	        {
		        $destination = JPATH_SITE . '/' . $destination;
	        }
        }

		return $destination;
	}

	public static function getForm($formId){
		static $form = array();

		$formId = (int) $formId;

		if (!isset($form[$formId])) {
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->clear()
				->select('*')
				->from($db->qn('#__rsform_forms'))
				->where($db->qn('FormId').'='.$db->q($formId));

			$db->setQuery($query);
			$form[$formId] = $db->loadObject();
		}

		return is_object($form[$formId]) ? clone $form[$formId] : false;
	}

	public static function getRawPost()
	{
		return Factory::getApplication()->input->post->getArray(array(), null, 'raw');
	}

	public static function generateQuickAddGlobal($type = 'display', $justArray = false)
	{
		switch ($type)
		{
			default:
			case 'display':
				$placeholders = array(
					'{global:formid}',
					'{global:username}',
					'{global:userip}',
					'{global:userid}',
					'{global:useremail}',
					'{global:fullname}',
					'{global:sitename}',
					'{global:siteurl}',
					'{global:mailfrom}',
					'{global:fromname}',
					'{global:confirmation}',
					'{global:confirmation_hash}',
					'{global:confirmedip}',
					'{global:confirmeddate}',
					'{global:utc_confirmeddate}',
					'{global:deletion}',
					'{global:deletion_hash}',
					'{global:submissionid}',
					'{global:submission_id}',
					'{global:date_added}',
					'{global:utc_date_added}',
					'{global:language}',
					'{global:formtitle}'
				);
				break;

			case 'generate':
				$placeholders = array(
					'{error}',
					'{global:formid}',
					'{global:formtitle}',
					'{global:username}',
					'{global:userip}',
					'{global:userid}',
					'{global:useremail}',
					'{global:fullname}',
					'{global:sitename}',
					'{global:siteurl}',
					'{global:mailfrom}',
					'{global:fromname}'
				);
				break;
		}

		$html = '<strong><u>' . Text::_('COM_RSFORM_GLOBAL_PLACEHOLDERS') . '</u></strong><br />';

		Factory::getApplication()->triggerEvent('onRsformAfterCreateQuickAddGlobalPlaceholders', array(&$placeholders, $type));

		if ($justArray)
		{
			return $placeholders;
		}

		$html .= '<pre>' . implode("\n", $placeholders) . '</pre><br />';

		return $html;
	}

	public static function generateQuickAdd($field, $key)
	{
		return '<strong>' . $field['name'] . '</strong><br/><pre>' . implode("\n", $field[$key]) . '</pre><br />';
	}

	public static function getFormLayouts($formId = 0)
    {
        $layouts = array(
            'html5Layouts' 	 => array('responsive', 'pico', 'bootstrap2', 'bootstrap3', 'bootstrap4', 'bootstrap5', 'uikit', 'uikit3', 'foundation')
        );

        Factory::getApplication()->triggerEvent('onRsformBackendLayoutsDefine', array(&$layouts, $formId));

        return $layouts;
    }

    public static function doSilentPost($silentPost, $config = array())
    {
	    static $initLogging;

	    $enableLogging = RSFormProHelper::getConfig('log_silentpost');

	    if ($enableLogging && !$initLogging)
	    {
		    $initLogging = true;
		    Log::addLogger(array('text_file' => 'rsform_silentpost_log.php'), Log::ALL, array('com_rsform'));
	    }

    	if (array_key_exists('replace', $config))
	    {
	    	$replace = $config['replace'];
	    }
	    if (array_key_exists('with', $config))
	    {
		    $with = $config['with'];
	    }
	    if (array_key_exists('post', $config))
	    {
		    $post = $config['post'];
	    }
	    if (array_key_exists('SubmissionId', $config))
	    {
		    list($replace, $with) = RSFormProHelper::getReplacements($config['SubmissionId']);

		    require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/submissions.php';
		    $submission = RSFormProSubmissionsHelper::getSubmission($config['SubmissionId']);

		    $db = Factory::getDbo();
		    $query = $db->getQuery(true);
		    $query->select($db->qn('p.PropertyValue', 'ComponentName'))
			    ->select($db->qn('ct.ComponentTypeName'))
			    ->select($db->qn('ct.ComponentTypeId'))
			    ->from($db->qn('#__rsform_components','c'))
			    ->join('left', $db->qn('#__rsform_properties','p').' ON '.$db->qn('p.ComponentId').' = '.$db->qn('c.ComponentId'))
			    ->join('left', $db->qn('#__rsform_component_types','ct').' ON '.$db->qn('c.ComponentTypeId').' = '.$db->qn('ct.ComponentTypeId'))
			    ->where($db->qn('c.FormId') . ' = ' . $db->q($submission->FormId))
			    ->where($db->qn('p.PropertyName') . ' = ' . $db->q('NAME'))
			    ->where($db->qn('c.Published') . ' = ' . $db->q(1))
			    ->order($db->qn('c.Order') . ' ' . $db->escape('asc'));

		    $fields = $db->setQuery($query)->loadObjectList();

		    // Generate POST
		    $post = array();
		    foreach ($fields as $field)
		    {
		    	if (!isset($submission->values[$field->ComponentName]))
			    {
			    	continue;
			    }

			    $post[$field->ComponentName] = $submission->values[$field->ComponentName];

		    	if (strpos($field->ComponentTypeName, 'checkbox') !== false || strpos($field->ComponentTypeName, 'select') !== false)
			    {
				    $post[$field->ComponentName] = RSFormProHelper::explode($submission->values[$field->ComponentName]);
			    }
		    }
	    }

	    if (empty($replace) || empty($with) || empty($post))
	    {
		    if ($enableLogging)
		    {
			    Log::add(sprintf('No submission values found for requested submission, can\'t execute mappings for form ID #%d', $silentPost->form_id), Log::ERROR, 'com_rsform');
		    }
	    	return false;
	    }

	    if ($silentPost && !empty($silentPost->url) && !in_array($silentPost->url, array('http://', 'https://')))
	    {
		    $formId = $silentPost->form_id;
            $SubmissionId = str_replace($replace, $with, '{global:submissionid}');
		    if ($enableLogging)
		    {
			    Log::add(sprintf('Preparing Silent Post for form ID #%d, Submission ID #%d', $formId, $SubmissionId), Log::INFO, 'com_rsform');
		    }

		    // Set URL to send data to
		    $url = str_replace($replace, $with, $silentPost->url);

		    if ($enableLogging)
		    {
			    Log::add(sprintf('Initializing Silent Post URL %s', $url), Log::INFO, 'com_rsform');
		    }

		    // Prepare data
		    if (!empty($silentPost->fields))
		    {
			    $data = '';

			    foreach ($silentPost->fields as $field)
			    {
				    $field->name  = str_replace($replace, $with, $field->name);
				    $field->value = str_replace($replace, $with, $field->value);

				    if (strlen($field->name))
				    {
					    $data .= urlencode($field->name).'='.urlencode($field->value).'&';
				    }
			    }

			    $data = rtrim($data, '&');
		    }
		    else
		    {
			    $data = http_build_query($post);
		    }

		    if ($enableLogging)
		    {
			    Log::add(sprintf('Data to be sent: %s', $data), Log::INFO, 'com_rsform');
		    }

		    try
		    {
			    // Do we need to send data silently?
			    if ($silentPost->silent)
			    {
				    // Map headers
				    $silentPostHeaders = array();
				    if (!empty($silentPost->headers))
				    {
					    $hasJson = false;
					    foreach ($silentPost->headers as $field)
					    {
						    $headerName  = str_replace($replace, $with, $field->name);
						    $headerValue = str_replace($replace, $with, $field->value);
						    $silentPostHeaders[$headerName] = $headerValue;

						    if (strtolower($headerName) === 'content-type' && strpos(strtolower($headerValue), 'json') !== false)
						    {
							    $hasJson = true;
						    }
					    }

					    if ($enableLogging)
					    {
						    Log::add(sprintf('Headers to be sent: %s', print_r($silentPostHeaders, true)), Log::INFO, 'com_rsform');
					    }

					    if ($hasJson)
					    {
						    parse_str($data, $dataArray);
						    $data = json_encode($dataArray);
					    }
				    }

				    // Get HTTP connector
				    $http = HttpFactory::getHttp();

				    if ($silentPost->method)
				    {
					    if ($enableLogging)
					    {
						    Log::add('Using POST', Log::INFO, 'com_rsform');
					    }

					    // POST
					    $silentPostResponse = $http->post($url, $data, $silentPostHeaders);
				    }
				    else
				    {
					    if ($enableLogging)
					    {
						    Log::add('Using GET', Log::INFO, 'com_rsform');
					    }

					    // GET
					    $silentPostResponse = $http->get($url.(strpos($url, '?') === false ? '?' : '&').$data, $silentPostHeaders);
				    }

				    if ($enableLogging)
				    {
					    Log::add('Executed successfully.', Log::INFO, 'com_rsform');
				    }

                    if ($silentPostResponse)
                    {
	                    Log::add(sprintf('Response code is %d', $silentPostResponse->code), Log::INFO, 'com_rsform');
                    }

				    Factory::getApplication()->triggerEvent('onRsformAfterSilentPost', array(array('SubmissionId' => str_replace($replace, $with, '{global:submissionid}'), 'formId' => str_replace($replace, $with, '{global:formid}'), 'response' => $silentPostResponse)));
			    }
			    else
			    {
				    // Try to follow the URL
				    if ($silentPost->method)
				    {
					    @ob_end_clean();

					    $dataArray = explode('&', $data);
					    // Create a hidden form that we submit through Javascript
					    ?>
					    <form id="formSubmit" method="post" action="<?php echo RSFormProHelper::htmlEscape($url); ?>">
						    <?php
						    if (!empty($dataArray) && is_array($dataArray)) {
							    foreach ($dataArray as $value) {
								    list($key, $value) = explode('=', $value, 2);
								    ?>
								    <input type="hidden" name="<?php echo RSFormProHelper::htmlEscape(urldecode($key)); ?>" value="<?php echo RSFormProHelper::htmlEscape(urldecode($value)); ?>" />
								    <?php
							    }
						    }
						    ?>
					    </form>
					    <script type="text/javascript">
                            function formSubmit() {
                                if (typeof document.getElementById("formSubmit").submit == "function") {
                                    document.getElementById("formSubmit").submit()
                                } else {
                                    document.createElement("form").submit.call(document.getElementById("formSubmit"));
                                }
                            }

                            try {
                                window.addEventListener ? window.addEventListener("load",formSubmit,false) : window.attachEvent("onload",formSubmit);
                            } catch (err) {
                                formSubmit();
                            }
					    </script>
					    <?php
					    if ($enableLogging)
					    {
						    Log::add('Using POST and redirecting user to URL successfully.', Log::INFO, 'com_rsform');
					    }

					    Factory::getApplication()->close();
				    }
				    else
				    {
					    if ($enableLogging)
					    {
						    Log::add('Using GET and redirecting user to URL successfully.', Log::INFO, 'com_rsform');
					    }

					    Factory::getApplication()->redirect($url.(strpos($url, '?') === false ? '?' : '&').$data);
				    }
			    }
		    }
		    catch (Exception $e)
		    {
			    if ($enableLogging)
			    {
				    Log::add(sprintf('Error occurred: %s', $e->getMessage()), Log::ERROR, 'com_rsform');
			    }

			    Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
			    return false;
		    }
	    }

	    return true;
    }

    public static function getSilentPost($formId)
    {
    	$db = Factory::getDbo();

	    $query = $db->getQuery(true)
		    ->select('*')
		    ->from($db->qn('#__rsform_posts'))
		    ->where($db->qn('form_id') . ' = ' . $db->q($formId))
		    ->where($db->qn('enabled') . ' = ' . $db->q(1));
	    $silentPost = $db->setQuery($query)->loadObject();

	    if ($silentPost && !empty($silentPost->fields))
	    {
		    $silentPost->fields = json_decode($silentPost->fields);
		    if (!is_array($silentPost->fields))
		    {
			    $silentPost->fields = array();
		    }
	    }

	    if ($silentPost && !empty($silentPost->headers))
	    {
		    $silentPost->headers = json_decode($silentPost->headers);
		    if (!is_array($silentPost->headers))
		    {
			    $silentPost->headers = array();
		    }
	    }

	    return $silentPost;
    }

    public static function doMappings($mappings, $config = array())
    {
	    static $initLogging;

	    if (empty($mappings))
	    {
	    	return false;
	    }

        $enableLogging = RSFormProHelper::getConfig('log_mappings');

        if ($enableLogging && !$initLogging)
        {
            $initLogging = true;
	        Log::addLogger(array('text_file' => 'rsform_mappings_log.php'), Log::ALL, array('com_rsform'));
        }

	    $db = Factory::getDbo();

	    if (array_key_exists('replace', $config))
	    {
		    $replace = $config['replace'];
	    }
	    if (array_key_exists('with', $config))
	    {
		    $with = $config['with'];
	    }
	    if (array_key_exists('SubmissionId', $config))
	    {
		    list($replace, $with) = RSFormProHelper::getReplacements($config['SubmissionId']);
	    }
	    if (empty($replace) || empty($with))
	    {
            if ($mapping = reset($mappings))
            {
	            if ($enableLogging)
	            {
		            Log::add(sprintf('No submission values found for requested submission, can\'t execute mappings for form ID #%d', $mapping->formId), Log::ERROR, 'com_rsform');
	            }
            }
		    return false;
	    }

	    $config['SubmissionId'] = str_replace($replace, $with, '{global:submissionid}');
	    $config['formId'] = str_replace($replace, $with, '{global:formid}');

	    $lastinsertid = '';
	    array_walk($with, array('RSFormProHelper', 'escapeSql'));

        $count = 0;
        $insertIds = array();
        if (is_array($mappings))
        {
            $insertIds = array_fill(1, count($mappings) - 1, '');
        }

	    foreach ($mappings as $mapping)
	    {
		    try
		    {
                $count++;

			    //get the query
			    $query = RSFormProHelper::getMappingQuery($mapping);

			    //replace the placeholders
			    $query = str_replace($replace, $with, $query);

			    //replace the last insertid placeholder
                $query = str_replace(array('{last_insert_id}', '{empty}', ), array($lastinsertid, ''), $query);

                foreach ($insertIds as $i => $insertId)
                {
                    $query = str_replace('{last_insert_id_' . $i . '}', $insertId, $query);
                }

			    if ($enableLogging)
			    {
				    Log::add(sprintf('Executing query for Submission ID #%d in Form ID #%d: %s', $config['SubmissionId'], $config['formId'], $query), Log::INFO, 'com_rsform');
			    }

			    if ($mapping->connection)
			    {
				    $options = array(
					    'driver' => isset($mapping->driver) ? $mapping->driver : 'mysql',
					    'host' => $mapping->host,
					    'port' => $mapping->port,
					    'user' => $mapping->username,
					    'password' => $mapping->password,
					    'database' => $mapping->database
				    );

				    $database = DatabaseDriver::getInstance($options);
				    $database->setQuery($query)->execute();

				    $lastinsertid = $database->insertid();
			    }
			    else
			    {
				    $db->setQuery($query)->execute();

				    $lastinsertid = $db->insertid();
			    }

			    $insertIds[$count] = $lastinsertid;

			    if ($enableLogging)
			    {
				    Log::add('Query executed without any errors.', Log::INFO, 'com_rsform');
			    }
		    }
		    catch (Exception $e)
		    {
			    if ($enableLogging)
			    {
				    Log::add(sprintf('Error reported for query in Submission ID #%d in Form ID #%d: %s', $config['SubmissionId'], $config['formId'], $e->getMessage()), Log::ERROR, 'com_rsform');
			    }

			    Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		    }
	    }

	    return true;
    }

    public static function getMappings($formId)
    {
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true)
		    ->select('*')
		    ->from($db->qn('#__rsform_mappings'))
		    ->where($db->qn('formId') . ' = ' . $db->q($formId))
		    ->order($db->qn('ordering') . ' ' . $db->escape('asc'));
	    return $db->setQuery($query)->loadObjectList();
    }
}