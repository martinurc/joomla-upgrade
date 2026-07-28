<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Plugin\PluginHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;

//no direct access
defined('_JEXEC') or die('Restricted access');

JLoader::register('SppagebuilderHelperRoute', JPATH_ROOT . '/components/com_sppagebuilder/helpers/route.php');
/**
 * Page List class
 */
class SppagebuilderModelDynamic extends ItemModel
{

	protected $_context = 'com_sppagebuilder.page';

	private function getPageIdFromCollectionItemId()
	{
		$itemId = CollectionHelper::getCollectionItemIdFromUrl();
		$collectionItem = CollectionItem::where('id', $itemId)->first(['collection_id']);

		if ($collectionItem->isEmpty()) {
			return null;
		}
		$collectionId = $collectionItem->collection_id;

		$page = Page::where('extension', 'com_sppagebuilder')
			->where('extension_view', 'dynamic_content:detail')
			->where('view_id', $collectionId)
			->first(['id']);

		if ($page->isEmpty()) {
			return null;
		}

		return $page->id ?? null;
	}

	protected function populateState()
	{
		$pageId = $this->getPageIdFromCollectionItemId();
		$this->setState('page.id', $pageId);

		$user = Factory::getUser();

		if ((!$user->authorise('core.edit.state', 'com_sppagebuilder')) && (!$user->authorise('core.edit', 'com_sppagebuilder')))
		{
			$this->setState('filter.published', 1);
		}
	}

	public function getItem($pageId = null)
	{
		$user = Factory::getUser();

		$pageId = (!empty($pageId)) ? $pageId : (int)$this->getState('page.id');

		if ($this->_item == null)
		{
			$this->_item = array();
		}

		if (!isset($this->_item[$pageId]))
		{
			try
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true);

				$query->select('a.*')
					->from($db->quoteName('#__sppagebuilder', 'a'))
					->where($db->quoteName('a.id') . ' = ' . (int) $pageId);

				$query->select($db->quoteName('l.title', 'language_title'))
					->join('LEFT', $db->quoteName('#__languages', 'l') . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

				$query->select($db->quoteName('ua.name', 'author_name'))
					->join('LEFT', $db->quoteName('#__users', 'ua') . ' ON ' . $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));

				$query->where($db->quoteName('a.published') . ' = 1');

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					return Text::_('COM_SPPAGEBUILDER_ERROR_UNPUBLISHED_PAGE');
				}

				$data->link = SppagebuilderHelperRoute::getPageRoute($data->id, $data->language);
				$data->formLink = SppagebuilderHelperRoute::getFormRoute($data->id, $data->language);

				if(!empty($data->content))
				{
					$data->text = $data->content;
				}

				if ($this->getState('filter.access'))
				{
					$data->access_view = true;
				}
				else
				{
					$groups = $user->getAuthorisedViewLevels();

					$data->access_view = in_array($data->access, $groups);
				}

				$this->_item[$pageId] = $data;
			}
			catch (Exception $error)
			{
				if ($error->getCode() == 404)
				{
					throw new Exception($error->getMessage(), 'error');
				}
				else
				{
					$this->setError($error);
					$this->_item[$pageId] = false;
				}
			}
		}

		return $this->_item[$pageId];
	}

	/**
	 * Increment the hit counter for the page.
	 *
	 * @param   integer  $pk  Optional primary key of the page to increment.
	 *
	 * @return  boolean  True if successful; false otherwise and internal error set.
	 */
	public function hit($pk = 0)
	{
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('page.id');
		$table = Table::getInstance('Page', 'SppagebuilderTable');
		$table->load($pk);
		$table->hit($pk);

		return true;
	}
}
