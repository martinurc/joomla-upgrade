<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Factory;
use Joomla\String\StringHelper;
use Joomla\Utilities\IpHelper;

abstract class RSFormProValidations
{
	public static function none($value, $extra = null, $data = null)
	{
		return true;
	}

	public static function alpha($param, $extra = null, $data = null)
	{
		if (strpos($param,"\n") !== false)
		{
			$param = str_replace(array("\r","\n"),'', $param);
		}

		for ($i = 0; $i < strlen($param); $i++)
		{
			if (strpos($extra, $param[$i]) === false && preg_match('#([^a-zA-Z ])#', $param[$i]))
			{
				return false;
			}
		}

		return true;
	}

	public static function numeric($param, $extra = null, $data = null)
	{
		if (strpos($param,"\n") !== false)
		{
			$param = str_replace(array("\r","\n"),'', $param);
		}

		for ($i = 0; $i < strlen($param); $i++)
		{
			if (strpos($extra, $param[$i]) === false && !is_numeric($param[$i]))
			{
				return false;
			}
		}

		return true;
	}

	public static function alphanumeric($param, $extra = null, $data = null)
	{
		if (strpos($param,"\n") !== false)
		{
			$param = str_replace(array("\r","\n"),'', $param);
		}

		for ($i = 0; $i < strlen($param); $i++)
		{
			if (strpos($extra, $param[$i]) === false && preg_match('#([^a-zA-Z0-9 ])#', $param[$i]))
			{
				return false;
			}
		}

		return true;
	}

	public static function alphaaccented($value, $extra = null, $data = null)
	{
		if (strpos($value,"\n") !== false)
		{
			$value = str_replace(array("\r","\n"),'', $value);
		}

		if ($extra !== null && $extra !== '')
		{
			for ($i = 0; $i < strlen($value); $i++)
			{
				if (strpos($extra, $value[$i]) === false && preg_match('#[^[:alpha:] ]#u', $value[$i]))
				{
					return false;
				}
			}
		}
		else
		{
			if (preg_match('#[^[:alpha:] ]#u', $value))
			{
				return false;
			}
		}

		return true;
	}

	public static function alphanumericaccented($value, $extra = null, $data = null)
	{
		if (strpos($value,"\n") !== false)
		{
			$value = str_replace(array("\r","\n"),'', $value);
		}

		if ($extra !== null && $extra !== '')
		{
			for ($i = 0; $i < strlen($value); $i++)
			{
				if (strpos($extra, $value[$i]) === false && preg_match('#[^[:alpha:]0-9 ]#u', $value[$i]))
				{
					return false;
				}
			}
		}
		else
		{
			if (preg_match('#[^[:alpha:]0-9 ]#u', $value))
			{
				return false;
			}
		}

		return true;
	}

	public static function email($email, $extra = null, $data = null)
	{
		try
		{
			$result = MailHelper::isEmailAddress($email);
		}
		catch (Exception $e)
		{
			$result = false;
		}

		if ($result)
		{
			if ($list = array_filter(RSFormProConfig::getInstance()->get('disposable_domains', array(), true)))
			{
				list($user, $domain) = explode('@', $email, 2);

				// Do we have an all-match?
				if (strpos(implode('', $list), '*') !== false)
				{
					foreach ($list as $item)
					{
						if (strpos($item, '*') !== false)
						{
							if (preg_match('/^' . str_replace('\*', '.*', preg_quote($item)) . '$/is', $domain))
							{
								return false;
							}
						}
					}
				}

				if (in_array(strtolower($domain), $list))
				{
					return false;
				}
			}
		}

		return $result;
	}

	public static function emaildns($email, $extra = null, $data = null)
	{
		// Check if it's an email address format
		if (!self::email($email,$extra,$data))
		{
			return false;
		}

		// Fallback if we don't have these
		if (!function_exists('checkdnsrr') || !is_callable('checkdnsrr'))
		{
			return true;
		}

		// IDN convert
		$email = PunycodeHelper::emailToPunycode($email);
		list($user, $domain) = explode('@', $email, 2);

		// Does this domain have a mail exchange record?
		return checkdnsrr($domain . '.', 'MX');
	}

	public static function uniquefield($value, $extra = null, $data = null)
	{
		$db 	= Factory::getDbo();
		$app	= Factory::getApplication();
		$form   = $app->input->get('form', array(), 'array');
		$formId = isset($form['formId']) ? $form['formId'] : 0;
		$option = $app->input->getCmd('option');
		$ctrl 	= $app->input->getCmd('controller');
		$task 	= $app->input->getCmd('task');
		$id		= $app->input->getInt('id');
		$collection = Factory::getApplication()->input->get('hidden_phone', array(), 'array');

		if (isset($collection[$data['NAME']]))
		{
			$value = $collection[$data['NAME']];
		}

		$query = $db->getQuery(true)
			->select($db->qn('SubmissionValueId'))
			->from($db->qn('#__rsform_submission_values'))
			->where($db->qn('FormId').'='.$db->q($formId))
			->where($db->qn('FieldName').'='.$db->q($data['NAME']))
			->where($db->qn('FieldValue').'='.$db->q($value));

		// Is this a directory edit?
		if ($id && $option == 'com_rsform' && $ctrl == 'directory' && ($task == 'save' || $task == 'apply'))
		{
			$query->where($db->qn('SubmissionId').' != '.$db->q($id));
		}

		return $db->setQuery($query)->loadResult() ? false : true;
	}

	public static function uniquefielduser($value, $extra = null, $data = null)
	{
		$db 		= Factory::getDbo();
		$app		= Factory::getApplication();
		$form   	= $app->input->get('form', array(), 'array');
		$formId 	= isset($form['formId']) ? $form['formId'] : 0;
		$user		= Factory::getUser();
		$userField 	= $user->guest ? 's.UserIp' : 's.UserId';
		$userValue 	= $user->guest ? IpHelper::getIp() : $user->id;
		$option 	= $app->input->getCmd('option');
		$ctrl 		= $app->input->getCmd('controller');
		$task 		= $app->input->getCmd('task');
		$id			= $app->input->getInt('id');
		$collection = Factory::getApplication()->input->get('hidden_phone', array(), 'array');

		if (isset($collection[$data['NAME']]))
		{
			$value = $collection[$data['NAME']];
		}

		$query = $db->getQuery(true)
			->select($db->qn('sv.SubmissionValueId'))
			->from($db->qn('#__rsform_submission_values', 'sv'))
			->join('left', $db->qn('#__rsform_submissions', 's').' ON ('.$db->qn('sv.SubmissionId').' = '.$db->qn('s.SubmissionId').')')
			->where($db->qn('sv.FormId').'='.$db->q($formId))
			->where($db->qn('sv.FieldName').'='.$db->q($data['NAME']))
			->where($db->qn('sv.FieldValue').'='.$db->q($value));

		// Is this a directory edit?
		if ($id && $option == 'com_rsform' && $ctrl == 'directory' && ($task == 'save' || $task == 'apply'))
		{
			$query->where($db->qn('s.SubmissionId').' != '.$db->q($id));

			// Override the $userValue based on the submission original values
			$newquery = $db->getQuery(true)
				->select($db->qn('UserId'))
				->select($db->qn('UserIp'))
				->from($db->qn('#__rsform_submissions'))
				->where($db->qn('SubmissionId').'='.$db->q($id));

			$submission = $db->setQuery($newquery)->loadObject();
			if ($submission->UserId)
			{
				$userField = 's.UserId';
				$userValue = $submission->UserId;
			}
			else
			{
				$userField = 's.UserIp';
				$userValue = $submission->UserIp;
			}
		}

		$query->where($db->qn($userField).'='.$db->q($userValue));

		return $db->setQuery($query)->loadResult() ? false : true;
	}

	public static function uszipcode($value, $extra = null, $data = null)
	{
		return preg_match("/^([0-9]{5})(-[0-9]{4})?$/i", $value);
	}

	public static function phonenumber($value, $extra = null, $data = null)
	{
		return preg_match("/\(?\b[0-9]{3}\)?[-. ]?[0-9]{3}[-. ]?[0-9]{4}\b/i", $value);
	}

	public static function creditcard($value, $extra = null, $data = null)
	{
		$value = preg_replace('/[^0-9]+/', '', $value);

		if (!$value)
		{
			return false;
		}

		// Amex
		if (preg_match("/^([34|37]{2})([0-9]{13})$/", $value) && self::luhn($value))
		{
			return true;
		}

		// Diners
		if (preg_match("/^([30|36|38]{2})([0-9]{12})$/", $value) && self::luhn($value))
		{
			return true;
		}

		// Discover
		if (preg_match("/^(?:6(?:011|5[0-9][0-9])[0-9]{12})$/", $value) && self::luhn($value))
		{
			return true;
		}

		// Master
		if (preg_match("/^([51|52|53|54|55]{2})([0-9]{14})$/", $value) && self::luhn($value))
		{
			return true;
		}

		// Visa
		if (preg_match("/^([4]{1})([0-9]{12,15})$/", $value) && self::luhn($value))
		{
			return true;
		}

		return false;
	}

	public static function custom($param, $extra = null, $data = null)
	{
		if (strpos($param,"\n") !== false)
		{
			$param = str_replace(array("\r","\n"),'', $param);
		}

		for ($i = 0; $i < strlen($param); $i++)
		{
			if (strpos($extra, $param[$i]) === false)
			{
				return false;
			}
		}

		return true;
	}

	public static function password($param, $extra = null, $data = null)
	{
		if (RSFormProHelper::isCode($data['DEFAULTVALUE']) == $param)
		{
			return true;
		}

		return false;
	}

	public static function ipaddress($param, $extra = null, $data = null)
	{
		return filter_var($param, FILTER_VALIDATE_IP);
	}

	public static function validurl($url, $extra = null, $data = null)
	{
		try
		{
			// Let's encode utf-8 characters
			$url = PunycodeHelper::urlToPunycode($url);

			// Now FILTER_VALIDATE_URL should suffice
			return filter_var($url, FILTER_VALIDATE_URL);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	public static function regex($value, $pattern = null, $data = null)
	{
		return preg_match($pattern, $value);
	}

	public static function sameas($value, $secondField, $data)
	{
		$valid 	= false;
		$form 	= Factory::getApplication()->input->get('form', array(), 'array');
		$collection = Factory::getApplication()->input->get('hidden_phone', array(), 'array');
		$secondValue = null;
		if (isset($form[$secondField]))
		{
			$secondValue = $form[$secondField];
		}
		if (isset($collection[$secondField]))
		{
			$secondValue = $collection[$secondField];
		}
		if (isset($collection[$data['NAME']]))
		{
			$value = $collection[$data['NAME']];
		}
		if ($secondValue !== null)
		{
			if (is_array($secondValue))
			{
				$secondValue = implode('', $secondValue);
			}
			if ($value == $secondValue)
			{
				$valid = true;
			}
		}

		return $valid;
	}

	public static function multiplerules($value, $extra = null, $data = null)
	{
		$validations 	= explode(',', $data['VALIDATIONMULTIPLE']);
		$extra 			= json_decode($extra);

		if (!empty($validations))
		{
			foreach ($validations as $function)
			{
				$newData = $data;
				unset($newData['VALIDATIONMULTIPLE']);

				$newData['VALIDATIONRULE']  = $function;
				$newData['VALIDATIONEXTRA'] = !empty($extra->{$function}) ? $extra->{$function} : null;

				if (!call_user_func_array(array(static::class, $function), array($value, $newData['VALIDATIONEXTRA'], $newData)))
				{
					return false;
				}
			}
		}

		return true;
	}

	public static function iban($value, $extra = null, $data = null)
	{
		require_once __DIR__ . '/iban.php';
		$iban = new RSFormIBAN($value);
		return $iban->validate();
	}

	protected static function luhn($value)
	{
		$sum = 0;
		$odd = strlen($value) % 2;

		// Calculate sum of digits.
		for($i = 0; $i < strlen($value); $i++)
		{
			$sum += $odd ? $value[$i] : (($value[$i] * 2 > 9) ? $value[$i] * 2 - 9 : $value[$i] * 2);
			$odd = !$odd;
		}

		// Check validity.
		return ($sum % 10 == 0) ? true : false;
	}

	public static function minlength($value, $min = null, $data = null)
	{
		$min = (int) $min;
		$value = (string) $value;

		return StringHelper::strlen($value) < $min ? false : true;
	}

	public static function maxlength($value, $max = null, $data = null)
	{
		$max = (int) $max;
		$value = (string) $value;

		return StringHelper::strlen($value) > $max ? false : true;
	}

	public static function restrictedwords($value, $extra = null, $data = null)
	{
		if (is_string($extra) && strlen($extra) > 0)
		{
			$words = explode(',', $extra);
			foreach ($words as $word)
			{
				if (preg_match('/\b' . preg_quote(trim($word)) . '\b/iu', $value))
				{
					return false;
				}
			}
		}

		return true;
	}

	public static function restrictedtext($value, $extra = null, $data = null)
	{
		if (is_string($extra) && strlen($extra) > 0)
		{
			$words = explode(',', $extra);
			foreach ($words as $word)
			{
				if (stripos($value, $word) !== false)
				{
					return false;
				}
			}
		}

		return true;
	}
}