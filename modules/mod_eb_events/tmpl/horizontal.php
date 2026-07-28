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
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var array                     $rows
 * @var RADConfig                 $config
 * @var \Joomla\Registry\Registry $params
 * @var int                       $numberEventPerRow
 * @var bool                      $linkToRegistrationForm
 * @var bool                      $titleLinkable
 * @var bool                      $showThumb
 * @var bool                      $showCategory
 * @var bool                      $showLocation
 * @var bool                      $showPrice
 * @var bool                      $showShortDescription
 * @var int                       $itemId
 *
 */

$db                = Factory::getDbo();
$dateFormat        = $config->date_format;
$timeFormat        = $config->event_time_format ?: 'g:i a';
$bootstrapHelper   = EventbookingHelperBootstrap::getInstance();
$rowFluidClass     = $bootstrapHelper->getClassMapping('row-fluid');
$span2Class        = $bootstrapHelper->getClassMapping('span2');
$span10Class       = $bootstrapHelper->getClassMapping('span10');
$iconMapMakerClass = $bootstrapHelper->getClassMapping('icon-map-marker');
$iconFolderClass   = $bootstrapHelper->getClassMapping('icon-folder-open');
$span              = $bootstrapHelper->getClassMapping('span' . intval(12 / $numberEventPerRow));
$iconCalendarClass = $bootstrapHelper->getClassMapping('icon-calendar');
$numberEvents      = count($rows);

if ($numberEvents > 0)
{
	if (EventbookingHelper::isValidMessage($params->get('pre_text')))
	{
		echo $params->get('pre_text');
	}
?>
    <div class="<?php echo $rowFluidClass; ?> clearfix">
        <?php
        $baseUri = Uri::base(true);
        $count = 0;

        for ($i = 0, $n = count($rows) ; $i < $n; $i++)
        {
            $event = $rows[$i];
	        $count++;
            $date = HTMLHelper::_('date', $event->event_date, 'd', null);
            $month = HTMLHelper::_('date', $event->event_date, 'n', null);
            $eventDate =  HTMLHelper::_('date', $event->event_date, 'h:i A') .' to '. HTMLHelper::_('date', $event->event_end_date, 'h:i A');

	        if ($linkToRegistrationForm && EventbookingHelperRegistration::acceptRegistration($event))
	        {
		        if ($event->registration_handle_url)
		        {
			        $detailUrl = $event->registration_handle_url;
		        }
		        else
		        {
			        $detailUrl = Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $event->id . '&Itemid=' . $itemId);
		        }
	        }
	        else
	        {
		        if ($event->event_detail_url)
		        {
			        $detailUrl = $event->event_detail_url;
		        }
		        else
		        {
			        $detailUrl = Route::_(EventbookingHelperRoute::getEventRoute($event->id, $event->main_category_id, $itemId));;
		        }
	        }

	        $cssClasses = ['up-event-item', $span];

	        if ($event->featured)
	        {
		        $cssClasses[] = 'eb-event-featured';
	        }
			?>
            <div class="<?php echo implode(' ', $cssClasses); ?>">
            	<h2 class="eb-event-title-container">
					<?php
					if ($titleLinkable)
					{
					?>
						<a class="eb-event-title" href="<?php echo $detailUrl; ?>"><?php echo $event->title; ?></a>
					<?php
					}
					else
					{
						echo $event->title;
					}
					?>
				</h2>
				<?php
				if ($showThumb && $event->thumb && file_exists(JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $event->thumb))
				{
				?>
					<div class="clearfix">
						<a href="<?php echo $detailUrl; ?>"><img src="<?php echo $baseUri . '/media/com_eventbooking/images/thumbs/' . $event->thumb; ?>" alt="<?php echo $event->image_alt ?: $event->title; ?>" class="eb-event-thumb" /></a>
					</div>
				<?php
				}

				if ($showCategory)
				{
				?>
					<div class="eb-event-category clearfix">
                        <i class="<?php echo $iconFolderClass; ?>"></i>
						<span><?php echo $event->categories ; ?></span>
					</div>
				<?php
				}
				?>
				<div class="eb-event-date-time clearfix">
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
				<div class="eb-event-location-price <?php echo $rowFluidClass; ?> clearfix">
					<?php
					if ($event->location && $showLocation)
					{
					?>
						<div class="eb-event-location <?php echo $bootstrapHelper->getClassMapping('span9'); ?>">
							<i class="<?php echo $iconMapMakerClass; ?>"></i>
							<?php echo EventbookingHelperHtml::loadCommonLayout('elements/location.php', ['location' => $event->location, 'Itemid' => $itemId]); ?>
						</div>
					<?php
					}

					if ($event->price_text)
					{
						$priceDisplay = $event->price_text;
					}
					elseif ($event->individual_price > 0)
					{
						$symbol        = $event->currency_symbol ?: $config->currency_symbol;
						$priceDisplay  = EventbookingHelper::formatCurrency($event->individual_price, $config, $symbol);
					}
					elseif ($config->show_price_for_free_event)
					{
						$priceDisplay = Text::_('EB_FREE');
					}
					else
					{
						$priceDisplay = '';
					}

					if ($priceDisplay && $showPrice)
					{
					?>
						<div class="eb-event-price btn-primary <?php echo $bootstrapHelper->getClassMapping('span3'); ?> pull-right">
							<span class="eb-individual-price"><?php echo $priceDisplay; ?></span>
						</div>
					<?php
					}
					?>
				</div>
	            <?php
	                if ($showShortDescription)
	                {
	                ?>
		                <div class="eb-event-short-description clearfix">
			                <?php echo $event->short_description; ?>
		                </div>
		            <?php
	                }
	            ?>
            </div>
        <?php
	        if ($count % $numberEventPerRow == 0 && $count < $numberEvents)
	        {
		    ?>
		        </div>
		        <div class="clearfix <?php echo $rowFluidClass; ?>">
		    <?php
	        }
        }

		if (EventbookingHelper::isValidMessage($params->get('post_text')))
		{
			echo $params->get('post_text');
		}
        ?>
    </div>
<?php
}
else
{
?>
    <div class="eb_empty"><?php echo Text::_('EB_NO_UPCOMING_EVENTS') ?></div>
<?php
}