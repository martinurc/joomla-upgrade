<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Utilities\IpHelper;
use Joomla\CMS\Http\HttpFactory;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/field.php';

class RSFormProFieldRecaptchav2 extends RSFormProField
{
	// backend preview
	public function getPreviewInput()
	{
		$size	= strtolower($this->getProperty('SIZE', 'normal'));
		$image  = $size == 'invisible' ? 'recaptcha-invisible.gif' : 'recaptchav2.gif';

		return HTMLHelper::_('image', 'plg_system_rsfprecaptchav2/' . $image, 'ReCAPTCHA', null, true);
	}
	
	// functions used for rendering in front view
	public function getFormInput() {
		$formId			= $this->formId;
		$componentId	= $this->componentId;

		// If no site key has been setup, just show a warning
		$siteKey = RSFormProHelper::getConfig('recaptchav2.site.key');
		if (!$siteKey)
		{
			return '<div>'.Text::_('RSFP_RECAPTCHAV2_NO_SITE_KEY').'</div>';
		}

		// Need to load scripts one-time.
		$this->loadScripts();

		$theme	= strtolower($this->getProperty('THEME'));
		$type	= strtolower($this->getProperty('TYPE'));
		$size	= strtolower($this->getProperty('SIZE', 'normal'));
		$params = array(
			'sitekey' => $siteKey,
			'theme'	  => $theme,
			'type'	  => $type,
			'size'	  => $size
		);
		if (RSFormProHelper::getConfig('recaptchav2.language') != 'auto')
		{
			$params['hl'] = Factory::getLanguage()->getTag();
		}
		$onsubmit = '';

		// If it's an invisible CAPTCHA we need to add some callbacks
		if ($size == 'invisible')
		{
			$params['badge'] = strtolower($this->getProperty('BADGE', 'inline'));
			$params['callback'] = 'RSFormProInvisibleCallback' . $formId;

			$form = RSFormProHelper::getForm($formId);

			// Need to trigger ReCAPTCHA
			if (!$form->DisableSubmitButton)
			{
				$onsubmit = "RSFormProUtils.addEvent(RSFormPro.getForm({$formId}), 'submit', function(evt){ evt.preventDefault(); 
	RSFormPro.submitForm(RSFormPro.getForm({$formId})); });";
			}

			$onsubmit .= "RSFormPro.addFormEvent({$formId}, function(){ grecaptcha.execute(id); });";
		}

		// JSON-Encode parameters
		$params = json_encode($params);

		$script = '';

		if ($size == 'invisible')
		{
			// Create the script
			$script .= <<<EOS
function RSFormProInvisibleCallback{$formId}()
{
	var form = RSFormPro.getForm({$formId});
	RSFormPro.submitForm(form);
}
EOS;
		}

		// Create the script
		$script .= <<<EOS
RSFormProReCAPTCHAv2.loaders.push(function(){
	if (typeof RSFormProReCAPTCHAv2.forms[{$formId}] === 'undefined') {
		var id = grecaptcha.render('g-recaptcha-{$componentId}', {$params});
		RSFormProReCAPTCHAv2.forms[{$formId}] = id;
		{$onsubmit}
	}
});
EOS;
		RSFormProAssets::addScriptDeclaration($script);

		$out = '<div id="g-recaptcha-'.$componentId.'"></div>';

		// Noscript fallback for regular CAPTCHA
		if ($size != 'invisible' && RSFormProHelper::getConfig('recaptchav2.noscript'))
		{
			$out .= '
			<noscript>
			  <div style="width: 302px; height: 352px;">
				<div style="width: 302px; height: 352px; position: relative;">
				  <div style="width: 302px; height: 352px; position: absolute;">
					<iframe src="https://www.' . RSFormProHelper::getConfig('recaptchav2.domain') . '/recaptcha/api/fallback?k='.$this->escape($siteKey).'" frameborder="0" scrolling="no" style="width: 302px; height:352px; border-style: none;"></iframe>
				  </div>
				  <div style="width: 250px; height: 80px; position: absolute; border-style: none; bottom: 21px; left: 25px; margin: 0px; padding: 0px; right: 25px;">
					<textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 80px; border: 1px solid #c1c1c1; margin: 0px; padding: 0px; resize: none;"></textarea>
				  </div>
				</div>
			  </div>
			</noscript>';
		}

		// Clear the token on page refresh
		Factory::getSession()->clear('com_rsform.recaptchav2Token'.$formId);

		return $out;
	}

	public function processValidation($validationType = 'form', $submissionId = 0)
	{
		// Skip directory editing since it makes no sense
		if ($validationType == 'directory')
		{
			return true;
		}

		$formId 	 = $this->formId;
		$form       = RSFormProHelper::getForm($formId);
		$logged		= $form->RemoveCaptchaLogged ? Factory::getUser()->id : false;
		$secretKey 	= RSFormProHelper::getConfig('recaptchav2.secret.key');

		// validation:
		// if there's no session token
		// validate based on challenge & response codes
		// if valid, set the session token

		// session token gets cleared after form processes
		// session token gets cleared on page refresh as well

		if (!$secretKey)
		{
			Factory::getApplication()->enqueueMessage(Text::_('RSFP_RECAPTCHAV2_MISSING_INPUT_SECRET'), 'error');
			return false;
		}

		if (!$logged)
		{
			$input 	  = Factory::getApplication()->input;
			$session  = Factory::getSession();
			$response = $input->post->get('g-recaptcha-response', '', 'raw');
			$ip		  = IpHelper::getIp();
			$task	  = strtolower($input->get('task', ''));
			$option	  = strtolower($input->get('option', ''));
			$isAjax	  = $option == 'com_rsform' && $task == 'ajaxvalidate';
			$isPage   = $input->getInt('page');

			// Already validated, move on
			if ($session->get('com_rsform.recaptchav2Token'.$formId))
			{
				return true;
			}

			// Ajax requests don't validate ReCAPTCHA on page change
			if ($isAjax && $isPage)
			{
				return true;
			}

			try
			{
				$http = HttpFactory::getHttp();
				if ($request = $http->get('https://www.' . RSFormProHelper::getConfig('recaptchav2.domain') . '/recaptcha/api/siteverify?secret='.urlencode($secretKey).'&response='.urlencode($response).'&remoteip='.urlencode($ip)))
				{
					$json = json_decode($request->body);
				}
			}
			catch (Exception $e)
			{
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				return false;
			}

			if (empty($json->success) || !$json->success)
			{
				if (!empty($json) && isset($json->{'error-codes'}) && is_array($json->{'error-codes'}))
				{
					foreach ($json->{'error-codes'} as $code)
					{
						Factory::getApplication()->enqueueMessage(Text::_('RSFP_RECAPTCHAV2_'.str_replace('-', '_', $code)), 'error');
					}
				}

				return false;
			}
			elseif ($isAjax)
			{
				$session->set('com_rsform.recaptchav2Token'.$formId, md5(uniqid($response)));
			}
		}

		return true;
	}

	protected function loadScripts()
	{
		static $loaded;

		if (!$loaded)
		{
			$loaded = true;
			$hl = RSFormProHelper::getConfig('recaptchav2.language') != 'auto' ? '&amp;hl='.Factory::getLanguage()->getTag() : '';
			$domain = RSFormProHelper::getConfig('recaptchav2.domain');

			if (RSFormProHelper::getConfig('recaptchav2.asyncdefer'))
			{
				RSFormProAssets::addCustomTag('<script src="https://www.' . $domain .'/recaptcha/api.js?render=explicit' . $hl. '" async defer></script>');
			}
			else
			{
				RSFormProAssets::addScript('https://www.' . $domain . '/recaptcha/api.js?render=explicit' . $hl);
			}

			RSFormProAssets::addScript(HTMLHelper::_('script', 'plg_system_rsfprecaptchav2/recaptchav2.js', array('pathOnly' => true, 'relative' => true)));
		}
	}
}