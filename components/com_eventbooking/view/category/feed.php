<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Document\Feed\FeedItem;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class EventbookingViewCategoryFeed extends RADView
{
	public function display()
	{
		/* @var JDocumentFeed $document */
		$document = Factory::getApplication()->getDocument();

		if (!method_exists($document, 'addItem'))
		{
			return;
		}

		$app = Factory::getApplication();

		/* @var EventbookingModelCategory $model */
		$model = $this->getModel();
		$model->setState('limitstart', 0)
			->setState('limit', $app->get('feed_limit'));
		$rows     = $model->getData();
		$timezone = $app->get('offset');

		$rootUri = Uri::root();
		$Itemid  = $app->input->getInt('Itemid', EventbookingHelper::getItemid());

		foreach ($rows as $row)
		{
			$title = html_entity_decode($row->title, ENT_COMPAT, 'UTF-8');
			$link  = Route::_(EventbookingHelperRoute::getEventRoute($row->id, $row->main_category_id, $Itemid));

			$date = Factory::getDate($row->event_date, $timezone);

			if ($row->image && file_exists(JPATH_ROOT . '/' . $row->image))
			{
				$description = '<p><img src="' . $rootUri . $row->image . '" /></p>';
				$description .= $row->short_description;
			}
			else
			{
				$description = $row->short_description;
			}

			// load individual item creator class
			$item              = new FeedItem();
			$item->title       = $title;
			$item->link        = $link;
			$item->description = $description;
			$item->category    = $row->category_name;
			$item->date        = $date->format('r');

			$document->addItem($item);
		}
	}
}
