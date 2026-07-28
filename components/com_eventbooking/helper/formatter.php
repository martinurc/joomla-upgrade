<?php

use Joomla\CMS\HTML\HTMLHelper;

/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */
class EventbookingHelperFormatter
{
	public static function getFormattedDatetime($date)
	{
		if ((int) $date)
		{
			$config = EventbookingHelper::getConfig();

			if (strpos($date, '00:00:00') !== false)
			{
				$format = $config->date_format;
			}
			else
			{
				$format = $config->event_date_format;
			}

			return HTMLHelper::_('date', $date, $format, null);
		}

		return '';
	}
}