<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

/**
 * Layout variables
 * -----------------
 * @var   int    $previousMonth
 * @var   int    $nextMonth
 * @var   string $previousMonthLink
 * @var   string $nextMonthLink
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

if ($this->config->display_event_in_tooltip)
{
	HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', ['html' => true, 'sanitize' => false]);
	Factory::getApplication()->getDocument()->addStyleDeclaration('.hasTip{display:block !important}');
}

$timeFormat = $this->config->event_time_format ?: 'g:i a';
$rootUri    = Uri::root(true);

$bootstrapHelper  = EventbookingHelperBootstrap::getInstance();
$angleDoubleLeft  = $bootstrapHelper->getClassMapping('icon-angle-double-left');
$angleDoubleRight = $bootstrapHelper->getClassMapping('icon-angle-double-right');
$hiddenPhoneClass = $bootstrapHelper->getClassMapping('hidden-phone');
$clearFixClass    = $bootstrapHelper->getClassMapping('clearfix');
$rowFluid         = $bootstrapHelper->getClassMapping('row-fluid');

if ($bootstrapHelper->getBootstrapVersion() === 'uikit3')
{
	$hiddenPhoneClass = '';
}

$params = $this->params;
?>
<div class="eb-calendar">
	<ul class="eb-month-browser regpro-calendarMonthHeader <?php echo $clearFixClass; ?>">
		<li class="eb-calendar-nav">
			<a href="<?php echo $previousMonthLink; ?>" rel="nofollow"><i class="fa fa-angle-double-left eb-calendar-navigation"></i></a>
		</li>
		<li id="eb-current-month">
			<?php echo $this->searchMonth; ?>
			<?php echo $this->searchYear; ?>
		</li>
		<li class="eb-calendar-nav">
			<a href="<?php echo $nextMonthLink ; ?>" rel="nofollow"><i class="fa fa-angle-double-right  eb-calendar-navigation"></i></a>
		</li>
	</ul>
	<ul class="eb-weekdays">
		<?php
		foreach ($this->data['daynames'] as $dayName)
		{
		?>
			<li class="eb-day-of-week regpro-calendarWeekDayHeader">
				<?php echo $dayName; ?>
			</li>
		<?php
		}
		?>
	</ul>
	<ul class="eb-days <?php echo $clearFixClass; ?>">
	<?php
		$eventIds = [];
		$dataCount = count($this->data['dates']);
		$dn=0;

		for ($w=0; $w<6 && $dn < $dataCount; $w++)
		{
			$rowClass = 'eb-calendar-row-' . $w;

			for ($d=0; $d<7 && $dn < $dataCount; $d++)
			{
				$currentDay = $this->data['dates'][$dn];

				if (!empty($currentDay['today']))
				{
					$isToday = true;
				}
				else
				{
					$isToday  = false;
				}

				switch ($currentDay['monthType'])
				{
					case 'prior':
					case 'following':
					?>
						<li class="eb-calendarDay calendar-day regpro-calendarDay <?php echo $rowClass; if (empty($currentDay['events'])) echo ' ' . $hiddenPhoneClass; ?>"></li>
					<?php
					break;
					case 'current':
					?>
					<li class="eb-calendarDay calendar-day regpro-calendarDay <?php echo $rowClass; if (empty($currentDay['events'])) echo ' ' . $hiddenPhoneClass;?>">
						<div class="date day_cell<?php if ($isToday) echo ' eb-calendar-today-date'; ?>"><span class="day"><?php echo $this->data['daynames'][$d] ?>,</span> <span class="month"><?php echo $this->listMonth[$this->month - 1]; ?></span> <?php echo $currentDay['d']; ?></div>
						<?php
						foreach ($currentDay['events'] as $key=> $event)
						{
							$eventIds[] = $event->id;
							$eventId = $event->id;

							if ($this->config->show_children_events_under_parent_event && $event->parent_id > 0)
							{
								$eventId = $event->parent_id;
							}

							[$thumbSource, $eventClasses, $eventLinkTitle, $eventInlineStyle] = $this->getCalendarEventAttributes($event, $params);
							?>
							<div class="date day_cell">
								<a class="<?php echo implode(' ', $eventClasses); ?>" href="<?php echo $event->url; ?>" title="<?php echo $eventLinkTitle; ?>"<?php if ($eventInlineStyle) echo $eventInlineStyle;  ?>>
									<?php
										if ($thumbSource)
										{
										?>
											<img border="0" align="top" alt="" class="eb-calendar-thumb" title="<?php echo $event->title; ?>" src="<?php echo $thumbSource; ?>" />
										<?php
										}

										if ($this->config->show_event_time && strpos($event->event_date, '00:00:00') === false)
										{
											echo $event->title . ' (<span class="eb-calendar-event-time">' . HTMLHelper::_('date', $event->event_date, $timeFormat, null) . '</span>)';
										}
										else
										{
											echo $event->title ;
										}
									?>
								</a>
							</div>
						<?php
						}
					echo "</li>\n";
					break;
				}
				$dn++;
			}
		}
	?>
	</ul>
</div>
<?php
if ($this->config->show_calendar_legend && empty($categoryId))
{
	echo $this->loadTemplate('calendar_legend', ['eventIds' => $eventIds]);
}

echo $this->loadTemplate('calendar_script', ['w' => $w]);
