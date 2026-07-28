<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

$app      = Factory::getApplication();
$document = $app->getDocument();

$document->addScript(Uri::root(true) . '/media/com_eventbooking/assets/js/responsive-auto-height.min.js', [], ['defer' => true]);

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper = $this->bootstrapHelper;

$return        = base64_encode(Uri::getInstance()->toString());
$timeFormat    = $this->config->event_time_format ?: 'g:i a';
$dateFormat    = $this->config->date_format;
$numberColumns = $this->params->get('number_columns') ?: $this->config->get('number_events_per_row', 2);

if ($this->params->get('image_lazy_loading', 'lazy'))
{
	$imgLoadingAttr = ' loading="lazy"';
}
else
{
	$imgLoadingAttr = '';
}

$lazyLoadingStartIndex = $this->params->get('image_lazy_loading_start_index', 0);

$rowFluid      = $bootstrapHelper->getClassMapping('row-fluid');
$btn           = $bootstrapHelper->getClassMapping('btn');
$iconCalendar  = $bootstrapHelper->getClassMapping('icon-calendar');
$iconMapMaker  = $bootstrapHelper->getClassMapping('icon-map-marker');
$clearfix      = $bootstrapHelper->getClassMapping('clearfix');
$btnPrimary    = $bootstrapHelper->getClassMapping('btn-primary');
$btnBtnPrimary = $bootstrapHelper->getClassMapping('btn btn-primary');

$span             = 'span' . intval(12 / $numberColumns);
$span             = $bootstrapHelper->getClassMapping($span);
$numberEvents     = count($this->items);
?>
<div id="eb-events" class="<?php echo $rowFluid . ' ' . $clearfix; ?> eb-columns-layout-container">
	<?php
		$rowCount = 0;

		for ($i = 0 ;  $i < $numberEvents ; $i++)
		{
			$event = $this->items[$i];

			if ($i % $numberColumns == 0)
			{
				$rowCount++;
				$newRowClass = ' eb-first-child-of-new-row';
			}
			else
			{
				$newRowClass = '';
			}

			$cssClasses = $event->cssClasses ?? [];

			$cssClasses[] = 'eb-event-wrapper';
			$cssClasses[] = 'eb-event-box';
			$cssClasses[] = $clearfix;
		?>
		<div class="<?php echo $span . $newRowClass; ?> eb-row-<?php echo $rowCount; ?>">
			<div class="<?php echo implode(' ', $cssClasses); ?>">
				<?php
				if (!empty($event->thumb_url))
				{
				?>
					<a href="<?php echo $event->url; ?>"><img<?php if ($imgLoadingAttr && $i >= $lazyLoadingStartIndex) echo $imgLoadingAttr; ?> src="<?php echo $event->thumb_url; ?>" class="eb-thumb-left" alt="<?php echo $event->image_alt ?: $event->title; ?>"/></a>
				<?php
				}
				?>
				<h2 class="eb-event-title-container">
					<?php
					if ($this->config->hide_detail_button !== '1')
					{
					?>
						<a class="eb-event-title" href="<?php echo $event->url; ?>"><?php echo $event->title; ?></a>
					<?php
					}
					else
					{
						echo $event->title;
					}
					?>
				</h2>
				<div class="eb-event-date-time <?php echo $clearfix; ?>">
					<i class="<?php echo $iconCalendar; ?>"></i>
					<?php
					if ($event->event_date != EB_TBC_DATE)
					{
						echo HTMLHelper::_('date', $event->event_date, $dateFormat, null);
					}
					else
					{
						echo Text::_('EB_TBC');
					}

					if (strpos($event->event_date, '00:00:00') === false)
					{
					?>
						<span class="eb-time"><?php echo HTMLHelper::_('date', $event->event_date, $timeFormat, null) ?></span>
					<?php
					}

					if ((int) $event->event_end_date > 0)
					{
						echo EventbookingHelperHtml::loadCommonLayout('elements/enddate.php', ['event' => $event]);
					}
					?>
				</div>
				<div class="eb-event-location-price <?php echo $rowFluid . ' ' . $clearfix; ?>">
					<?php
					if ($event->location)
					{
					?>
						<div class="eb-event-location <?php echo $bootstrapHelper->getClassMapping('span9'); ?>">
							<i class="<?php echo $iconMapMaker; ?>"></i>
							<?php echo EventbookingHelperHtml::loadCommonLayout('elements/location.php', ['location' => $event->location, 'Itemid' => $this->Itemid]); ?>
						</div>
					<?php
					}

					if ($event->priceDisplay)
					{
					?>
						<div class="eb-event-price <?php echo $btnPrimary . ' ' . $bootstrapHelper->getClassMapping('span3 pull-right'); ?>">
							<span class="eb-individual-price"><?php echo $event->priceDisplay; ?></span>
						</div>
					<?php
					}
					?>
				</div>
				<div class="eb-event-short-description <?php echo $clearfix; ?>">
					<?php echo $event->short_description; ?>
				</div>
				<?php
					// Event message to tell user that they already registered, need to login to register or don't have permission to register...
					echo EventbookingHelperHtml::loadCommonLayout('common/event_message.php', ['config' => $this->config, 'event' => $event]);
				?>
				<div class="eb-taskbar <?php echo $clearfix; ?>">
					<ul>
						<?php
						if ($this->config->get('show_register_buttons', 1) && !$event->is_multiple_date)
						{
							if ($event->can_register)
							{
								echo EventbookingHelperHtml::loadCommonLayout('common/buttons_register.php', ['item' => $event, 'Itemid' => $this->Itemid]);
							}
							elseif ($event->waiting_list && $event->registration_type != 3 && !EventbookingHelperRegistration::isUserJoinedWaitingList($event->id))
							{
								echo EventbookingHelperHtml::loadCommonLayout('common/buttons_waiting_list.php', ['item' => $event, 'Itemid' => $this->Itemid]);
							}
						}

						if ($this->config->hide_detail_button !== '1' || $event->is_multiple_date)
						{
						?>
							<li>
								<a class="<?php echo $btn ?>" href="<?php echo $event->url; ?>">
									<?php echo $event->is_multiple_date ? Text::_('EB_CHOOSE_DATE_LOCATION') : Text::_('EB_DETAILS');?>
								</a>
							</li>
						<?php
						}
						?>
					</ul>
				</div>
			</div>
		</div>
		<?php
		}
	?>
</div>
<?php

// Add Google Structured Data
PluginHelper::importPlugin('eventbooking');
$app->triggerEvent('onDisplayEvents', [$this->items]);

$equalHeightScript[] = 'window.addEventListener("load", function() {';

for ($i = 1; $i <= $rowCount; $i++)
{
	$equalHeightScript[] = 'new ResponsiveAutoHeight(".eb-row-' . $i . ' .eb-event-wrapper");';
}

$equalHeightScript[] = '});';

$document->addScriptDeclaration(implode("\r\n", $equalHeightScript));
