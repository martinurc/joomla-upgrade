<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Table\Table;
use Joomla\String\StringHelper;

class EventbookingModelCategory extends RADModelAdmin
{
	/**
	 * Support events trigger for category
	 *
	 * @var boolean
	 */

	protected $triggerEvents = true;

	/**
	 * @param   EventbookingTableCategory  $row
	 * @param   RADInput                   $input
	 * @param   bool                       $isNew
	 */
	protected function beforeStore($row, $input, $isNew)
	{
		parent::beforeStore($row, $input, $isNew);

		$paymentMethods = $input->get('payment_methods', [], 'array');

		if (isset($paymentMethods[0]) && $paymentMethods[0] === '')
		{
			$paymentMethods = '';
		}
		else
		{
			$paymentMethods = implode(',', array_filter($paymentMethods));
		}

		$input->set('payment_methods', $paymentMethods);
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   EventbookingTableCategory  $row  A reference to a JTable object.
	 *
	 * @return void
	 */
	protected function prepareTable($row, $task, $sourceId = 0)
	{
		// Prevent choosing itself as parent category
		if ($row->parent == $row->id)
		{
			$row->parent = 0;
		}

		$row->level = 1;

		if ($row->parent > 0)
		{
			$db    = $this->getDbo();
			$query = $db->getQuery(true)
				->select('`level`')
				->from('#__eb_categories')
				->where('id = ' . (int) $row->parent);
			$db->setQuery($query);
			$row->level = (int) $db->loadResult() + 1;
		}

		parent::prepareTable($row, $task, $sourceId);
	}

	/**
	 * Create category thumbnail if category is selected
	 *
	 * @param   EventbookingTableCategory  $row
	 * @param   RADInput                   $input
	 * @param   bool                       $isNew
	 */
	protected function afterStore($row, $input, $isNew)
	{
		parent::afterStore($row, $input, $isNew);

		$image = EventbookingHelperHtml::getCleanImagePath($row->image);

		if ($image && file_exists(JPATH_ROOT . '/' . $image))
		{
			$config = EventbookingHelper::getConfig();

			$thumbPath   = JPATH_ROOT . '/images/com_eventbooking/categories/thumb/' . basename($image);
			$thumbWidth  = $config->get('category_thumb_width') ?: 200;
			$thumbHeight = $config->get('category_thumb_height') ?: 200;

			$fileExt = StringHelper::strtoupper(File::getExt($image));

			if ($fileExt == 'PNG')
			{
				$imageType = IMAGETYPE_PNG;
			}
			elseif ($fileExt == 'GIF')
			{
				$imageType = IMAGETYPE_GIF;
			}
			elseif (in_array($fileExt, ['JPG', 'JPEG']))
			{
				$imageType = IMAGETYPE_JPEG;
			}
			else
			{
				$imageType = '';
			}

			$image = new Image(JPATH_ROOT . '/' . $image);

			if ($config->get('resize_image_method') == 'crop_resize')
			{
				$image->cropResize($thumbWidth, $thumbHeight, false)
					->toFile($thumbPath, $imageType);
			}
			else
			{
				$image->resize($thumbWidth, $thumbHeight, false)
					->toFile($thumbPath, $imageType);
			}
		}
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   Table  $row  A JTable object.
	 *
	 * @return array An array of conditions to add to ordering queries.
	 */
	protected function getReorderConditions($row)
	{
		return ['`parent` = ' . (int) $row->parent];
	}

	/**
	 * Initialize data for new category
	 */
	public function initData()
	{
		parent::initData();

		$this->data->submit_event_access = 1;
	}

	/**
	 * Override beforeDelete method to delete the urls related to categoroes before categories are delete
	 *
	 * @param   array  $cid
	 *
	 * @return void
	 */
	protected function beforeDelete($cid)
	{
		// Delete the URLs related to category
		$cids  = implode(',', $cid);
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->delete('#__eb_urls')
			->where($db->quoteName('view') . '=' . $db->quote('category'))
			->where('record_id IN (' . $cids . ')');
		$db->setQuery($query);
		$db->execute();
	}
}
