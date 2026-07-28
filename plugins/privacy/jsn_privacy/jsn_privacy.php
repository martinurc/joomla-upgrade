<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');

class PlgPrivacyJsn_privacy extends PrivacyPlugin
{
	public function onPrivacyExportRequest(PrivacyTableRequest $request, JUser $user = null)
	{
		if (!$user)
		{
			return array();
		}

		/** @var JTableUser $userTable */
		$userTable = JUser::getTable();
		$userTable->load($user->id);

		$domains = array();
		$domains[] = $this->createEpDomain($userTable);

		return $domains;
	}

	private function createEpDomain(JTableUser $user)
	{
		$domain = $this->createDomain('user_ep', 'joomla_user_ep_data');

		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__jsn_users'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($user->id));

		$item = $this->db->setQuery($query)->loadAssoc();
		unset($item['privacy']);
		foreach($item as $key => $value){
			if(empty($value)) unset($item[$key]);
		}
		$domain->addItem($this->createItemFromArray($item, $item['id']));

		return $domain;
	}
}
