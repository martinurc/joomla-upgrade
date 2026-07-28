<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

trait EventbookingControllerCaptcha
{
	/**
	 * Method to validate captcha
	 *
	 * @param   RADInput  $input
	 *
	 * @return bool|mixed
	 */
	protected function validateCaptcha($input)
	{
		$user   = Factory::getUser();
		$config = EventbookingHelper::getConfig();

		if ($config->enable_captcha && !$user->authorise('core.admin', 'com_eventbooking'))
		{
			$captchaPlugin = $this->app->get('captcha') ?: 'recaptcha';

			$plugin = PluginHelper::getPlugin('captcha', $captchaPlugin);

			if ($plugin)
			{
				try
				{
					return Captcha::getInstance($captchaPlugin)->checkAnswer($input->post->get('eb_dynamic_recaptcha_1', null, 'string'));
				}
				catch (Exception $e)
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to add some checks to prevent spams
	 *
	 */
	protected function antiSpam()
	{
		$config = EventbookingHelper::getConfig();

		$honeypotFieldName = $config->get('honeypot_fieldname', 'eb_my_own_website_name');

		if ($this->input->getString($honeypotFieldName))
		{
			throw new Exception(Text::_('EB_HONEYPOT_SPAM_DETECTED'), 403);
		}

		if ((int) $config->minimum_form_time > 0)
		{
			$startTime = $this->input->getInt(EventbookingHelper::getHashedFieldName(), 0);

			if ((time() - $startTime) < (int) $config->minimum_form_time)
			{
				throw new Exception(Text::_('EB_FORM_SUBMIT_TOO_FAST'), 403);
			}
		}

		if ((int) $config->maximum_submits_per_session)
		{
			$session = $this->app->getSession();

			$numberSubmissions = (int) $session->get('eb_number_submissions', 0) + 1;

			if ($numberSubmissions > (int) $config->maximum_submits_per_session)
			{
				throw new Exception(Text::_('EB_EXCEEDED_NUMBER_FORM_SUBMISSIONS'), 403);
			}

			$session->set('eb_number_submissions', $numberSubmissions);
		}
	}
}
