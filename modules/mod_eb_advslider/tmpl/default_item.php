<?php
/**
 * @package        Joomla
 * @subpackage     Events Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 - 2024 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Layout variables
 *
 * @var stdClass                    $event
 * @var Joomla\Registry\Registry    $params
 * @var EventbookingHelperBootstrap $bootstrapHelper
 * @var string                      $rowFluidClass
 * @var string                      $clearfixClass
 * @var string                      $iconCalendarClass
 * @var string                      $iconMapMakerClass
 * @var string                      $btnPrimaryClass
 * @var string                      $dateFormat
 * @var string                      $timeFormat
 * @var int                         $itemId
 *
 */

EventbookingHelperModal::iframeModal('a.eb-colorbox-map', 'eb-map-modal');

$cssClasses = ['eb-event-wrapper', $clearfixClass];

if ($event->featured)
{
	$cssClasses[] = 'eb-event-featured';
}
?>
<li class="splide__slide">
	<div class="<?php echo implode(' ', $cssClasses); ?>">
		<?php
		if (!empty($event->thumb_url))
		{
			if ($params->get('thumb_width', 'full') == 'full')
			{
			?>
				<a href="<?php echo $event->url; ?>" class="eb-event-slider-thumb-container eb-thumb-width-full"><img src="<?php echo $event->thumb_url; ?>" class="eb-event-slider-thumb" alt="<?php echo $event->image_alt ?: $event->title; ?>"/></a>
			<?php
			}
			else
			{
			?>
				<a href="<?php echo $event->url; ?>" class="eb-event-slider-thumb-container eb-thumb-width-auto"><img src="<?php echo $event->thumb_url; ?>" class="eb-event-slider-thumb" alt="<?php echo $event->image_alt ?: $event->title; ?>"/></a>
			<?php
			}
		}
		?>
		<h2 class="eb-event-title-container">
            <a class="eb-event-title" href="<?php echo $event->url; ?>"><?php echo $event->title; ?></a>
		</h2>
		<div class="eb-event-date-time <?php echo $clearfixClass; ?>">
			<i class="<?php echo $iconCalendarClass; ?>"></i>
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

			if ((int) $event->event_end_date)
			{
				echo EventbookingHelperHtml::loadCommonLayout('elements/enddate.php', ['event' => $event]);
			}
			?>
		</div>
		<div class="eb-event-location-price <?php echo $rowFluidClass . ' ' . $clearfixClass; ?>">
			<?php
			if ($event->location)
			{
			?>
				<div class="eb-event-location <?php echo $bootstrapHelper->getClassMapping('span9'); ?>">
					<i class="<?php echo $iconMapMakerClass; ?>"></i>
					<?php echo EventbookingHelperHtml::loadCommonLayout('elements/location.php', ['location' => $event->location, 'Itemid' => $itemId]); ?>
				</div>
			<?php
			}

			if ($event->priceDisplay)
			{
			?>
				<div class="eb-event-price <?php echo $btnPrimaryClass . ' ' . $bootstrapHelper->getClassMapping('span3'); ?> pull-right">
					<span class="eb-individual-price"><?php echo $event->priceDisplay; ?></span>
				</div>
			<?php
			}
			?>
		</div>
		<?php
		if ($params->get('show_short_description'))
		{
			if ($params->get('short_description_limit'))
			{
                $event->short_description = HTMLHelper::_('string.truncate', $event->short_description, (int)$params->get('short_description_limit'));
			}
		?>
			<div class="eb-event-short-description <?php echo $clearfixClass; ?>">
				<?php echo $event->short_description; ?>
			</div>
		<?php
		}
		?>
	</div>
</li>