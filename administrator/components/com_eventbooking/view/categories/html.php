<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingViewCategoriesHtml extends RADViewList
{
	protected function prepareView()
	{
		parent::prepareView();

		$ordering = [];

		foreach ($this->items as $item)
		{
			$ordering[$item->parent][] = $item->id;
		}

		foreach ($this->items as $row)
		{
			if ($row->level > 1)
			{
				$_currentParentId = $row->parent;
				$parentsStr       = ' ' . $_currentParentId;

				for ($i2 = 0; $i2 < $row->level; $i2++)
				{
					foreach ($ordering as $k => $v)
					{
						$v = implode('-', $v);
						$v = '-' . $v . '-';

						if (strpos($v, '-' . $_currentParentId . '-') !== false)
						{
							$parentsStr       .= ' ' . $k;
							$_currentParentId = $k;
							break;
						}
					}
				}
			}
			else
			{
				$parentsStr = '';
			}

			$row->parentsStr = $parentsStr;
		}
	}
}
