<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2023 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\IpHelper;
use Joomla\CMS\Language\Text;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/field.php';

class RSFormProFieldHashcash extends RSFormProField
{
	// backend preview
	public function getPreviewInput()
	{
		return '<span class="rsficon rsficon-shield"></span> ' . $this->getProperty('LABEL', '');
	}
	
	public function getFormInput()
	{
		$this->addScript(HTMLHelper::_('script', 'com_rsform/hashcash/sha256.js', array('pathOnly' => true, 'relative' => true)));
		$this->addScript(HTMLHelper::_('script', 'com_rsform/hashcash/hashcash.js', array('pathOnly' => true, 'relative' => true)));

		$session    = Factory::getSession();
		$prefix     = 'com_rsform.hashcash' . $this->formId;
		$time       = $session->get($prefix . '.request_time', Factory::getDate()->toUnix());
		$ip         = $session->get($prefix . '.remote_addr', IpHelper::getIp());
		$name		= $this->getName();
		$id			= $this->getId();
		$attr		= $this->getAttributes();
		$label		= $this->getProperty('LABEL', '');
		$type 		= 'button';
		$additional = '';

		// Reset data
		$session->set($prefix . '.request_time', $time);
		$session->set($prefix . '.remote_addr', $ip);

		// Start building the HTML input
		$html = '<button';
		// Parse Additional Attributes
		if ($attr) {
			foreach ($attr as $key => $values) {
				// @new feature - Some HTML attributes (type) can be overwritten
				// directly from the Additional Attributes area
				if ($key == 'type' && strlen($values)) {
					${$key} = $values;
					continue;
				}
				$additional .= $this->attributeToHtml($key, $values);
			}
		}
		// Set the type
		$html .= ' type="'.$this->escape($type).'"';
		// ID
		$html .= ' id="'.$this->escape($id).'"';
		// Additional HTML
		$html .= $additional;
		$html .= ' data-rsfp-hashcash';
		$html .= ' data-hashcash-level="' . (int) $this->getProperty('DIFFICULTY', 4) . '"';
		$html .= ' data-hashcash-text="' . $this->escape(md5($ip  . $time)) . '"';
		$html .= ' data-hashcash-name="' . $this->escape($name) . '"';

		// Close the tag
		$html .= '>';

		$html .= '<svg class="hashcash__pending hashcash" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="hashcash__circle" cx="26" cy="26" r="25" fill="none"/><path class="hashcash__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg><span class="hashcash__text">' . $this->escape($label) . '</span></button>';

		return $html;
	}

	public function processValidation($validationType = 'form', $submissionId = 0)
	{
		// Skip directory editing since it makes no sense
		if ($validationType == 'directory')
		{
			return true;
		}

		// Logged in users don't need to pass Captcha if this option is enabled on the form.
		$form = RSFormProHelper::getForm($this->formId);
		if (Factory::getUser()->id && $form->RemoveCaptchaLogged)
		{
			return true;
		}

		$app      = Factory::getApplication();
		$task	  = strtolower($app->input->get('task', ''));
		$option	  = strtolower($app->input->get('option', ''));
		$isAjax	  = $option === 'com_rsform' && $task === 'ajaxvalidate';

		if ($isAjax)
		{
			return true;
		}

		$session = Factory::getSession();
		$prefix  = 'com_rsform.hashcash' . $this->formId;
		$value   = $this->getValue();
		$time    = $session->get($prefix . '.request_time');
		$ip      = $session->get($prefix . '.remote_addr');

		$session->set($prefix . '.request_time', null);
		$session->set($prefix . '.remote_addr', null);

		try
		{
			if (!$value)
			{
				throw new Exception(Text::_('COM_RSFORM_HASHCASH_MISSING_VALUE'));
			}

			if (!$time || !$ip)
			{
				throw new Exception(Text::_('COM_RSFORM_HASHCASH_MISSING_SESSION_DATA'));
			}

			$sha256 = hash('sha256', md5($ip . $time) . $value, false);
			$valid = preg_match('/^0{' . (int) $this->getProperty('DIFFICULTY', 4) . '}/', $sha256);

			if (!$valid)
			{
				throw new Exception(Text::_('COM_RSFORM_HASHCASH_VALIDATION_FAILED'));
			}

			return $valid;
		}
		catch (Exception $e)
		{
			$properties =& RSFormProHelper::getComponentProperties($this->componentId);
			$properties['VALIDATIONMESSAGE'] = $e->getMessage();
			return false;
		}
	}

	public function processBeforeStore($submissionId, &$post, &$files)
	{
		unset($post[$this->name]);
	}
}