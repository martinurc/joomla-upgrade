<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\HTML\HTMLHelper;

/**
 * Layout variables
 *
 * @var EventbookingTableEvent $event
 */

$config = EventbookingHelper::getConfig();

$timeFormat = $config->event_time_format ?: 'g:i a';
$dateFormat = $config->date_format;

if (strpos($event->event_end_date, '00:00:00') === false)
{
	$showTime = true;
}
else
{
	$showTime = false;
}

$startDate =  HTMLHelper::_('date', $event->event_date, 'Y-m-d', null);
$endDate   = HTMLHelper::_('date', $event->event_end_date, 'Y-m-d', null);

if ($startDate == $endDate)
{
	if ($showTime)
	{
	?>
		-<span class="eb-event-time eb-time"><?php echo HTMLHelper::_('date', $event->event_end_date, $timeFormat, null) ?></span>
	<?php
	}
}
else
{
	echo ' - ' .HTMLHelper::_('date', $event->event_end_date, $dateFormat, null);

	if ($showTime)
	{
	?>
		<span class="eb-event-time eb-time"><?php echo HTMLHelper::_('date', $event->event_end_date, $timeFormat, null) ?></span>
	<?php
	}
}

