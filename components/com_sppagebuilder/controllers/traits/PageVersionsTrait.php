<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

trait PageVersionsTrait
{
	/**
	 * Profile image cache
	 * @var array
	 * @since 6.2.5
	 */
	private $profileImageCache = [];
	/**
	 * Get list of versions for a page
	 *
	 * @return void
	 * @since 6.2.4
	 */
	public function getVersions()
	{
		$this->profileImageCache = [];
		$input = Factory::getApplication()->input;
		$pageId = $input->getInt('id', 0);
		$search = $input->getString('search', '');

		$response = [
			'status' => false,
			'message' => 'Invalid page id',
			'data' => []
		];

		if (empty($pageId))
		{
			echo json_encode($response);
			die();
		}

		try
		{
			$db = Factory::getDbo();
			$baseUrl = rtrim(Uri::root(), '/');
			$profileImageEnabled = PluginHelper::isEnabled('user', 'profileimage');
			$profileImageSelect = $profileImageEnabled
				? 'CASE WHEN p.profile_value IS NOT NULL AND p.profile_value != ' . $db->quote('') . ' THEN CONCAT(' . $db->quote($baseUrl) . ', JSON_UNQUOTE(p.profile_value)) ELSE NULL END as profile_image'
				: 'NULL as profile_image';
			$query = $db->getQuery(true);
			$query->select([
				$db->quoteName('v.id'),
				$db->quoteName('v.page_id'),
				$db->quoteName('v.name'),
				$db->quoteName('v.note'),
				$db->quoteName('v.active'),
				$db->quoteName('v.created_on'),
				$db->quoteName('v.created_by'),
				$db->quoteName('u.name', 'created_by_name'),
				$db->quoteName('u.email', 'created_by_email'),
				$profileImageSelect
			])
			->from($db->quoteName('#__sppagebuilder_versions', 'v'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('v.created_by'));

			if ($profileImageEnabled)
			{
				$query->join('LEFT', $db->quoteName('#__user_profiles', 'p') . ' ON ' . $db->quoteName('p.user_id') . ' = ' . $db->quoteName('v.created_by') . ' AND ' . $db->quoteName('p.profile_key') . ' = ' . $db->quote('profileimage.profile_image'));
			}

			$query->group($db->quoteName('v.id'))
				->where($db->quoteName('v.page_id') . ' = ' . (int) $pageId);

			// Add search filter if provided
			if (!empty($search))
			{
				$searchTerm = $db->escape($search, true);
				$query->where('(' .
					$db->quoteName('v.name') . ' LIKE ' . $db->quote('%' . $searchTerm . '%') . ' OR ' .
					$db->quoteName('v.note') . ' LIKE ' . $db->quote('%' . $searchTerm . '%') . ' OR ' .
					$db->quoteName('u.name') . ' LIKE ' . $db->quote('%' . $searchTerm . '%') .
					')'
				);
			}

			$query->order($db->quoteName('v.created_on') . ' DESC');

			$db->setQuery($query);
			$versions = $db->loadObjectList();

			foreach ($versions as &$version) {
				if ($profileImageEnabled && empty($version->profile_image)) {
					$enableGravatar = ComponentHelper::getParams('com_sppagebuilder')->get('enable_gravatar', 1);
					if ($enableGravatar && !empty($version->created_by_email)) {
						$version->profile_image = $this->getCachedProfileImage($version->created_by, $version->created_by_email);
					}
				}
				unset($version->created_by_email);
			}

			$response = [
				'status' => true,
				'message' => 'Versions retrieved successfully',
				'data' => $versions
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => false,
				'message' => $e->getMessage(),
				'data' => []
			];
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Delete a version
	 *
	 * @return void
	 * @since 6.2.4
	 */
	public function deleteVersion()
	{
		$input = Factory::getApplication()->input;
		// Try to get version_id from URL parameter first, then from JSON body
		$versionId = $input->getInt('version_id', 0);
		if (empty($versionId))
		{
			$versionId = $input->json->getInt('version_id', 0);
		}

		$response = [
			'status' => false,
			'message' => 'Invalid version id'
		];

		if (empty($versionId))
		{
			echo json_encode($response);
			die();
		}

		try
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/tables');
			$versionTable = Table::getInstance('Version', 'SppagebuilderTable');
			
			if ($versionTable->load($versionId))
			{
				$versionTable->delete();
				$response = [
					'status' => true,
					'message' => 'Version deleted successfully'
				];
			}
			else
			{
				$response['message'] = 'Version not found';
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => false,
				'message' => $e->getMessage()
			];
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Delete multiple versions for a page (bulk).
	 * Only versions that belong to the given page id are deleted; others are skipped.
	 *
	 * @return void
	 * @since 6.2.6
	 */
	public function deleteVersions()
	{
		$input = Factory::getApplication()->input;
		$pageId = $input->getInt('id', 0);

		if (empty($pageId))
		{
			$pageId = $input->json->getInt('id', 0);
		}

		$versionIds = $input->get('version_ids', [], 'array');

		if (empty($versionIds) && isset($input->json))
		{
			$jsonIds = $input->json->get('version_ids', []);

			if (\is_array($jsonIds))
			{
				$versionIds = $jsonIds;
			}
		}

		if (empty($versionIds))
		{
			$contentType = $input->server->getString('CONTENT_TYPE', '');
			if (stripos($contentType, 'application/json') !== false)
			{
				$raw = \file_get_contents('php://input');
				$body = \is_string($raw) && $raw !== '' ? \json_decode($raw, true) : null;

				if (\is_array($body) && isset($body['version_ids']) && \is_array($body['version_ids']))
				{
					$versionIds = $body['version_ids'];
				}
			}
		}

		$versionIds = array_values(array_unique(array_filter(array_map('intval', $versionIds))));

		$response = [
			'status' => false,
			'message' => 'Invalid page id or version ids',
			'data' => [
				'deleted' => 0,
				'failed' => 0,
			],
		];

		if (empty($pageId) || empty($versionIds))
		{
			echo json_encode($response);
			die();
		}

		try
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/tables');
			$deleted = 0;
			$failed = 0;

			foreach ($versionIds as $versionId)
			{
				$versionTable = Table::getInstance('Version', 'SppagebuilderTable');

				if (!$versionTable->load($versionId))
				{
					$failed++;
					continue;
				}

				if ((int) $versionTable->page_id !== (int) $pageId)
				{
					$failed++;
					continue;
				}

				if ($versionTable->delete())
				{
					$deleted++;
				}
				else
				{
					$failed++;
				}
			}

			$response = [
				'status' => $deleted > 0,
				'message' => $deleted > 0
					? 'Versions deleted successfully'
					: 'No versions were deleted',
				'data' => [
					'deleted' => $deleted,
					'failed' => $failed,
				],
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => false,
				'message' => $e->getMessage(),
				'data' => [
					'deleted' => 0,
					'failed' => 0,
				],
			];
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Restore a version
	 *
	 * @return void
	 * @since 6.2.4
	 */
	public function restoreVersion()
	{
		$input = Factory::getApplication()->input;
		// Try to get version_id from JSON body first, then from GET/POST
		$versionId = $input->json->getInt('version_id', 0);
		if (empty($versionId))
		{
			$versionId = $input->getInt('version_id', 0);
		}
		$pageId = $input->getInt('id', 0);

		$response = [
			'status' => false,
			'message' => 'Invalid version id or page id'
		];

		if (empty($versionId) || empty($pageId))
		{
			echo json_encode($response);
			die();
		}

		try
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/tables');
			$versionTable = Table::getInstance('Version', 'SppagebuilderTable');
			
			if (!$versionTable->load($versionId))
			{
				$response['message'] = 'Version not found';
				echo json_encode($response);
				die();
			}

			// Verify the version belongs to the page
			if ($versionTable->page_id != $pageId)
			{
				$response['message'] = 'Version does not belong to this page';
				echo json_encode($response);
				die();
			}

			// Get the page table
			$pageTable = Table::getInstance('Page', 'SppagebuilderTable');
			
			if (!$pageTable->load($pageId))
			{
				$response['message'] = 'Page not found';
				echo json_encode($response);
				die();
			}

			// Restore version data
			$pageData = [
				'id' => $pageId,
				'content' => $versionTable->content,
				'css' => $versionTable->css,
				'attribs' => $versionTable->attribs,
				'og_title' => $versionTable->og_title,
				'og_image' => $versionTable->og_image,
				'og_description' => $versionTable->og_description,
			];

			$pageTable->bind($pageData);
			
			if ($pageTable->store())
			{
				// Set all other versions of this page to inactive
				$db = Factory::getDbo();
				$updateQuery = $db->getQuery(true);
				$updateQuery->update($db->quoteName('#__sppagebuilder_versions'))
					->set($db->quoteName('active') . ' = 0')
					->where($db->quoteName('page_id') . ' = ' . (int) $pageId);
				$db->setQuery($updateQuery);
				$db->execute();

				// Set the restored version as active
				$versionTable->active = 1;
				$versionTable->store();

				$response = [
					'status' => true,
					'message' => 'Version restored successfully'
				];
			}
			else
			{
				$response['message'] = 'Error restoring version';
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => false,
				'message' => $e->getMessage()
			];
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Update version note
	 *
	 * @return void
	 * @since 6.2.5
	 */
	public function updateVersionNote()
	{
		$input = Factory::getApplication()->input;
		$versionId = $input->getInt('version_id', 0);
		$note = $input->json->getString('note', '');

		$response = [
			'status' => false,
			'message' => 'Invalid version id'
		];

		if (empty($versionId))
		{
			echo json_encode($response);
			die();
		}

		try
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/tables');
			$versionTable = Table::getInstance('Version', 'SppagebuilderTable');
			
			if (!$versionTable->load($versionId))
			{
				$response['message'] = 'Version not found';
				echo json_encode($response);
				die();
			}

			$versionTable->note = $note;
			
			if ($versionTable->store())
			{
				$response = [
					'status' => true,
					'message' => 'Version note updated successfully'
				];
			}
			else
			{
				$response['message'] = 'Error updating version note';
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => false,
				'message' => $e->getMessage()
			];
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Get version content for preview
	 *
	 * @return void
	 * @since 6.2.5
	 */
	public function getVersionContent()
	{
		$input = Factory::getApplication()->input;
		$versionId = $input->getInt('version_id', 0);

		$response = [
			'status' => false,
			'message' => 'Invalid version id',
			'data' => null
		];

		if (empty($versionId))
		{
			echo json_encode($response);
			die();
		}

		try
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/tables');
			$versionTable = Table::getInstance('Version', 'SppagebuilderTable');
			
			if (!$versionTable->load($versionId))
			{
				$response['message'] = 'Version not found';
				echo json_encode($response);
				die();
			}

			$response = [
				'status' => true,
				'message' => 'Version content retrieved successfully',
				'data' => [
					'content' => $versionTable->content ?? '',
					'css' => $versionTable->css ?? '',
					'attribs' => $versionTable->attribs ?? '[]',
					'og_title' => $versionTable->og_title ?? '',
					'og_image' => $versionTable->og_image ?? '',
					'og_description' => $versionTable->og_description ?? '',
				]
			];
		}
		catch (Exception $e)
		{
			$response = [
				'status' => false,
				'message' => $e->getMessage(),
				'data' => null
			];
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Update version name
	 *
	 * @return void
	 * @since 6.2.5
	 */
	public function updateVersionName()
	{
		$input = Factory::getApplication()->input;
		$versionId = $input->getInt('version_id', 0);
		$name = $input->json->getString('name', '');

		$response = [
			'status' => false,
			'message' => 'Invalid version id'
		];

		if (empty($versionId))
		{
			echo json_encode($response);
			die();
		}

		if (empty(trim($name)))
		{
			$response['message'] = 'Version name cannot be empty';
			echo json_encode($response);
			die();
		}

		try
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/tables');
			$versionTable = Table::getInstance('Version', 'SppagebuilderTable');
			
			if (!$versionTable->load($versionId))
			{
				$response['message'] = 'Version not found';
				echo json_encode($response);
				die();
			}

			$versionTable->name = trim($name);
			
			if ($versionTable->store())
			{
				$response = [
					'status' => true,
					'message' => 'Version name updated successfully'
				];
			}
			else
			{
				$response['message'] = 'Error updating version name';
			}
		}
		catch (Exception $e)
		{
			$response = [
				'status' => false,
				'message' => $e->getMessage()
			];
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Get cached profile image or generate Gravatar
	 *
	 * @param int $userId User ID
	 * @param string $email User email
	 * @return string|false Profile image URL or Gravatar URL or false
	 * @since 6.2.5
	 */
	private function getCachedProfileImage($userId, $email)
	{
		if (!isset($this->profileImageCache[$userId]))
		{
			$this->profileImageCache[$userId] = $this->getGravatarUrl($email);
		}

		return $this->profileImageCache[$userId];
	}

	/**
	 * Get Gravatar URL for email
	 *
	 * @param string $email User email
	 * @param int $size Avatar size
	 * @param string $default Default image
	 * @return string|false Gravatar URL or false
	 * @since 6.2.5
	 */
	private function getGravatarUrl($email, $size = 45, $default = '404')
	{
		if (empty($email))
		{
			return false;
		}
		
		$hash = md5(strtolower(trim($email)));
		$url = "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
		
		return $url;
	}
}
