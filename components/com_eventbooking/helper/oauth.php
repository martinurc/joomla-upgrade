<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

abstract class EventbookingHelperOauth
{
	/**
	 * Get the valid access token
	 *
	 * @param   string  $vendor
	 *
	 * @return stdClass
	 */
	public static function getAccessToken($vendor)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from('#__eb_accesstokens')
			->where('vendor = ' . $db->quote($vendor))
			->order('id DESC');
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Store the token into database
	 *
	 * @param   string  $vendor
	 * @param   string  $token
	 * @param   int     $expireAt
	 *
	 * @return void
	 */
	public static function storeToken($vendor, $token, $expireAt)
	{
		$rowToken = self::getAccessToken($vendor);

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// Token record already exist
		if ($rowToken)
		{
			// We just need to update the record
			$query->update('#__eb_accesstokens')
				->set('token = ' . $db->quote($token))
				->set('expire_at = ' . $expireAt)
				->where('id = ' . $rowToken->id);
			$db->setQuery($query)
				->execute();
		}
		else
		{
			// Insert the record
			$query->insert('#__eb_accesstokens')
				->columns(['vendor', 'token', 'expire_at'])
				->values(implode(',', $db->quote([$vendor, $token, $expireAt])));
			$db->setQuery($query)
				->execute();
		}
	}
}