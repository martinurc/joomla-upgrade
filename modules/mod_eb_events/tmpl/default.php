<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var array                     $rows
 * @var RADConfig                 $config
 * @var \Joomla\Registry\Registry $params
 * @var bool                      $linkToRegistrationForm
 * @var bool                      $titleLinkable
 * @var bool                      $showThumb
 * @var bool                      $showCategory
 * @var bool                      $showLocation
 * @var bool                      $showPrice
 * @var int                       $itemId
 *
 */

if (count($rows))
{
	$baseUri            = Uri::root(true);
	$bootstrapHelper    = EventbookingHelperBootstrap::getInstance();
	$iconFolderClass    = $bootstrapHelper->getClassMapping('icon-folder-open');
	$iconMapMarkerClass = $bootstrapHelper->getClassMapping('icon-map-marker');
	$iconCalendarClass  = $bootstrapHelper->getClassMapping('icon-calendar');
	?>
	<div class="ebm-upcoming-events ebm-upcoming-events-default">
	<?php
		if (EventbookingHelper::isValidMessage($params->get('pre_text')))
		{
			echo $params->get('pre_text');
		}

		foreach ($rows as $row)
		{
			if ($linkToRegistrationForm && EventbookingHelperRegistration::acceptRegistration($row))
			{
				if ($row->registration_handle_url)
				{
					$url = $row->registration_handle_url;
				}
				else
				{
					$url = Route::_(
						'index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $row->id . '&Itemid=' . $itemId
					);
				}
			}
			else
			{
				if ($row->event_detail_url)
				{
					$url = $row->event_detail_url;
				}
				else
				{
					$url = Route::_(EventbookingHelperRoute::getEventRoute($row->id, $row->main_category_id, $itemId));
				}
			}

			$cssClasses = ['up-event-item'];

			if ($row->featured)
			{
				$cssClasses[] = 'eb-event-featured';
			}
		?>
            <div class="<?php echo implode(' ', $cssClasses); ?>">
                <?php
                    if ($titleLinkable)
                    {
                    ?>
                        <a href="<?php echo $url; ?>" class="ebm-event-link">
                            <?php
                                if ($showThumb && $row->thumb && file_exists(JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $row->thumb))
                                {
                                ?>
                                    <img src="<?php echo $baseUri . '/media/com_eventbooking/images/thumbs/' . $row->thumb; ?>" alt="<?php echo $row->image_alt ?: $row->title; ?>" class="ebm-event-thumb"/>
                                <?php
                                }

                                echo $row->title;
                            ?>
                        </a>
                    <?php
                    }
                    else
                    {
                        if ($showThumb && $row->thumb && file_exists(JPATH_ROOT . '/media/com_eventbooking/images/thumbs/' . $row->thumb))
                        {
                        ?>
                            <img src="<?php echo $baseUri . '/media/com_eventbooking/images/thumbs/' . $row->thumb; ?>"
                                 class="ebm-event-thumb"/>
                        <?php
                        }

                        echo $row->title;
                    }
                ?>
                <br/>
                <span class="ebm-event-date">
                    <i class="<?php echo $iconCalendarClass; ?>"></i>
                    <?php
                    if ($row->event_date == '2099-12-31 00:00:00')
                    {
	                    echo Text::_('EB_TBC');
                    }
                    else
                    {
	                    echo EventbookingHelperFormatter::getFormattedDatetime($row->event_date);
                    }
                    ?>
                </span>
                <?php
                if ($showCategory)
                {
                ?>
                    <br/>
                    <i class="<?php echo $iconFolderClass; ?>"></i>
                    <span class="ebm-event-categories"><?php echo $row->categories; ?></span>
                <?php
                }

                if ($showLocation && $row->location)
                {
                ?>
                    <br/>
                    <i class="<?php echo $iconMapMarkerClass; ?>"></i>
                <?php
	                echo EventbookingHelperHtml::loadCommonLayout('elements/location.php', ['location' => $row->location, 'Itemid' => $itemId]);
				}

                if ($showPrice)
                {
                    $price = $row->price_text ?: EventbookingHelper::formatCurrency($row->individual_price, $config);
                ?>
                    <br/>
                    <?php echo '<strong>'.Text::_('EB_PRICE').'</strong>: '. $price; ?>
                <?php
                }
                ?>
            </div>
		<?php
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
