<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class EventbookingViewCategoryIcs extends RADView
{
	public function display()
	{
		$app    = Factory::getApplication();
		$config = EventbookingHelper::getConfig();

		/* @var EventbookingModelCategory $model */
		$model = $this->getModel();
		$model->setState('limitstart', 0)
			->setState('limit', (int) $config->get('ics_export_limit', 100) ?: 500);
		$rows = $model->getData();

		if ($config->from_name)
		{
			$fromName = $config->from_name;
		}
		else
		{
			$fromName = $app->get('from_name');
		}

		if ($config->from_email)
		{
			$fromEmail = $config->from_email;
		}
		else
		{
			$fromEmail = $app->get('fromname');
		}

		$calendar = EventbookingHelper::generateIcs($rows, $fromEmail, $fromName);

		if ($id = $model->getState('id'))
		{
			$filename = 'events' . $id . '.ics';
		}
		else
		{
			$filename = 'events.ics';
		}

		$app->setHeader('Content-Type', 'text/calendar; charset=utf-8', true)
			->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true)
			->setHeader('Cache-Control', 'must-revalidate', true)
			->sendHeaders();

		echo $calendar->get();

		$app->close();
	}
}
