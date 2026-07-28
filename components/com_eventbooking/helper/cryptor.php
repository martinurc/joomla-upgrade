<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use ioncube\phpOpensslCryptor\Cryptor;
use Joomla\CMS\Crypt\Cipher\SimpleCipher;
use Joomla\CMS\Crypt\Crypt;
use Joomla\CMS\Crypt\Key;
use Joomla\CMS\Factory;

class EventbookingHelperCryptor
{
	/**
	 * Method to encrypt a string
	 *
	 * @param   string  $string
	 *
	 * @return string
	 */
	public static function encrypt($string)
	{
		$privateKey = md5(Factory::getApplication()->get('secret'));

		if (static::isOpenSSLAvailable())
		{
			if (!class_exists(Cryptor::class))
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/cryptor/Cryptor.php';
			}

			try
			{
				return Cryptor::Encrypt($string, $privateKey);
			}
			catch (Exception $e)
			{
				// Return original string if encryption
				return $string;
			}
		}

		if (class_exists('JCryptCipherSimple'))
		{
			$key   = new Key('simple', $privateKey, $privateKey);
			$crypt = new Crypt(new SimpleCipher(), $key);

			return $crypt->encrypt($string);
		}

		return $string;
	}

	/**
	 * Method to decrypt a string
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function decrypt($string)
	{
		$privateKey = md5(Factory::getApplication()->get('secret'));

		if (static::isOpenSSLAvailable())
		{
			if (!class_exists(Cryptor::class))
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/cryptor/Cryptor.php';
			}

			try
			{
				return Cryptor::Decrypt($string, $privateKey);
			}
			catch (Exception $e)
			{
				// Return original string in case there is error with decryption
				return $string;
			}
		}

		if (class_exists('JCryptCipherSimple'))
		{
			$key   = new Key('simple', $privateKey, $privateKey);
			$crypt = new Crypt(new SimpleCipher(), $key);

			return $crypt->decrypt($string);
		}

		return $string;
	}

	/**
	 * Method to check if openssl library is available to use
	 *
	 * @return bool
	 */
	protected static function isOpenSSLAvailable()
	{
		return function_exists('openssl_encrypt');
	}
}
