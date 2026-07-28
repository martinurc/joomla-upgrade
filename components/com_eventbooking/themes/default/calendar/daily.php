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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

Factory::getApplication()->getDocument()->addScriptOptions('dailyCalendarUrl', Route::_('index.php?option=com_eventbooking&view=calendar&layout=daily&Itemid=' . $this->Itemid . '&day=', false));

EventbookingHelperHtml::addOverridableScript('media/com_eventbooking/js/site-calendar-daily.min.js');

Text::script('EB_PLEASE_CHOOSE_DATE', true);

$timeFormat = $this->config->event_time_format ?: 'g:i a' ;

$daysInWeek = [
	0 => Text::_('SUNDAY'),
	1 => Text::_('MONDAY'),
	2 => Text::_('TUESDAY'),
	3 => Text::_('WEDNESDAY'),
	4 => Text::_('THURSDAY'),
	5 => Text::_('FRIDAY'),
	6 => Text::_('SATURDAY'),
];

$monthsInYear = [
	1  => Text::_('JANUARY'),
	2  => Text::_('FEBRUARY'),
	3  => Text::_('MARCH'),
	4  => Text::_('APRIL'),
	5  => Text::_('MAY'),
	6  => Text::_('JUNE'),
	7  => Text::_('JULY'),
	8  => Text::_('AUGUST'),
	9  => Text::_('SEPTEMBER'),
	10 => Text::_('OCTOBER'),
	11 => Text::_('NOVEMBER'),
	12 => Text::_('DECEMBER'),
];

$bootstrapHelper  = EventbookingHelperBootstrap::getInstance();
$angleDoubleLeft  = $bootstrapHelper->getClassMapping('icon-angle-double-left');
$angleDoubleRight = $bootstrapHelper->getClassMapping('icon-angle-double-right');
$mapMarkerClass   = $bootstrapHelper->getClassMapping('icon-map-marker');

$datePicker       = HTMLHelper::_('calendar', Factory::getApplication()->input->getString('day', ''), 'date', 'date', '%Y-%m-%d', ['class' => 'input-medium']);
$iconCalendar     = $bootstrapHelper->getClassMapping('icon-calendar');
$datePicker       = str_replace('icon-calendar', $iconCalendar, $datePicker);
?>
<div id="extcalendar" class="eb-container eb-calendar-daily">
	<?php
	if ($this->params->get('show_page_heading', 1))
	{
		if ($this->input->getInt('hmvc_call'))
		{
			$hTag = 'h2';
		}
		else
		{
			$hTag = 'h1';
		}
	?>
		<<?php echo $hTag; ?> class="eb-page-heading"><?php echo $this->params->get('page_heading') ?: $this->escape(Text::_('EB_CALENDAR')); ?></<?php echo $hTag; ?>>
	<?php
	}
	?>
    <div style="width: 100%;" class="topmenu_calendar <?php echo $bootstrapHelper->getClassMapping('row-fluid'); ?>">
        <div class="left_calendar <?php echo $bootstrapHelper->getClassMapping('span7'); ?>">
            <strong><?php echo Text::_('EB_CHOOSE_DATE'); ?>:</strong>
            <?php echo $datePicker; ?>
            <input type="button" class="btn" value="<?php echo Text::_('EB_GO'); ?>" onclick="gotoDate();" />
        </div>
        <?php
			if ($this->showCalendarMenu)
			{
			?>
                <div class="<?php echo $bootstrapHelper->getClassMapping('span5');?>">
                    <?php echo $this->loadCommonLayout('calendar/navigation.php', ['layout' => 'daily']); ?>
                </div>
            <?php
			}
		?>
    </div>
    <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr class="tablec">
            <td class="previousday">
                <a href="<?php echo Route::_('index.php?option=com_eventbooking&view=calendar&layout=daily&day=' . date('Y-m-d', strtotime('-1 day', strtotime($this->day))) . "&Itemid=$this->Itemid");?>" rel="nofollow">
                    <i class="<?php echo $angleDoubleLeft; ?> eb-calendar-navigation" title="<?php echo Text::_('EB_PREVIOUS_DAY')?>"></i>
                </a>
            </td>
            <td class="currentday currentdaytoday">
                <?php
					$time = strtotime($this->day) ;
					echo $daysInWeek[date('w', $time)] . ', ' . $monthsInYear[date('n', $time)] . ' ' . date('d', $time) . ', ' . date('Y', $time);
				?>
            </td>
            <td class="nextday">
                <a href="<?php echo Route::_('index.php?option=com_eventbooking&view=calendar&layout=daily&day=' . date('Y-m-d', strtotime('+1 day', strtotime($this->day))) . "&Itemid=$this->Itemid");?>" rel="nofollow">
                    <i class="<?php echo $angleDoubleRight; ?> eb-calendar-navigation" title="<?php echo Text::_('EB_NEXT_DAY')?>"></i>
                </a>
            </td>
        </tr>
    </table>
    <?php
	if (count($this->events))
	{
	?>
        <table cellpadding="0" cellspacing="0" width="100%" border="0" class="eb-daily-events-container">
            <?php
			foreach ($this->events as $key => $event)
			{
				if ($event->event_detail_url) {
					$url = $event->event_detail_url;
				} else {
					$url = Route::_(EventbookingHelperRoute::getEventRoute($event->id, 0, $this->Itemid));
				}
			?>
            <tr>
                <td class="tablea">
                    <a href="<?php echo $url; ?>"><?php echo HTMLHelper::_('date', $event->event_date, $timeFormat, null);?></a>
                </td>
                <td class="tableb">
                    <div class="eventdesc">
                        <h4><a href="<?php echo $url; ?>"><?php echo $event->title?></a></h4>
                        <?php
							if ($event->location_id > 0)
							{
							?>
                                <p class="location-name">
                                    <i class="<?php echo $mapMarkerClass; ?>"></i>
                                    <a href="<?php echo Route::_('index.php?option=com_eventbooking&view=map&location_id=' . $event->location_id . '&tmpl=component&format=html'); ?>" title="<?php echo $event->location_name ; ?>" class="eb-colorbox-map" rel="nofollow"><?php echo $event->location_name; ?></a>
                                </p>
                            <?php
							}

							echo $event->short_description;
						?>
                    </div>
                </td>
            </tr>
            <?php
			}
			?>
        </table>
    <?php
	}
	else
	{
	?>
        <div class="tableb center" >
            <strong><?php echo Text::_('EB_NO_EVENTS'); ?></strong>
        </div>
    <?php
	}
	?>
</div>