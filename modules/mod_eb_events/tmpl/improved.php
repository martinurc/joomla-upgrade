<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var array                     $rows
 * @var \Joomla\Registry\Registry $params
 * @var RADConfig                 $config
 * @var bool                      $linkToRegistrationForm
 * @var bool                      $titleLinkable
 * @var bool                      $showThumb
 * @var bool                      $showCategory
 * @var bool                      $showLocation
 * @var bool                      $showPrice
 *
 */

if (count($rows))
{
	$bootstrapHelper = EventbookingHelperBootstrap::getInstance();

	$rowFluidClass      = $bootstrapHelper->getClassMapping('row-fluid');
	$span3Class         = $bootstrapHelper->getClassMapping('span3');
	$span9Class         = $bootstrapHelper->getClassMapping('span9');
	$iconFolderClass    = $bootstrapHelper->getClassMapping('icon-folder-open');
	$iconMapMarkerClass = $bootstrapHelper->getClassMapping('icon-map-marker');

    $monthNames = [
        1 => Text::_('JANUARY_SHORT'),
        2 => Text::_('FEBRUARY_SHORT'),
        3 => Text::_('MARCH_SHORT'),
        4 => Text::_('APRIL_SHORT'),
        5 => Text::_('MAY_SHORT'),
        6 => Text::_('JUNE_SHORT'),
        7 => Text::_('JULY_SHORT'),
        8 => Text::_('AUGUST_SHORT'),
        9 => Text::_('SEPTEMBER_SHORT'),
        10 => Text::_('OCTOBER_SHORT'),
        11 => Text::_('NOVEMBER_SHORT'),
        12 => Text::_('DECEMBER_SHORT')
    ];
?>
    <div class="ebm-upcoming-events ebm-upcoming-events-improved">
        <?php
        if (EventbookingHelper::isValidMessage($params->get('pre_text')))
        {
	        echo $params->get('pre_text');
        }

        $k = 0 ;
        $baseUri = Uri::base(true);

        foreach ($rows as  $row)
        {
            $k = 1 - $k ;
            $date = HTMLHelper::_('date', $row->event_date, 'd', null);
            $month = HTMLHelper::_('date', $row->event_date, 'n', null);

	        if ($linkToRegistrationForm && EventbookingHelperRegistration::acceptRegistration($row))
	        {
		        if ($row->registration_handle_url)
		        {
			        $url = $row->registration_handle_url;
		        }
		        else
		        {
			        $url = Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $row->id . '&Itemid=' . $itemId);
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

	        $cssClasses = [$rowFluidClass, 'up-event-item'];

	        if ($row->featured)
	        {
		        $cssClasses[] = 'eb-event-featured';
	        }
        ?>
            <div class="<?php echo implode(' ', $cssClasses); ?>">
                <div class="<?php echo $span3Class; ?>">
                    <div class="ebm-event-date">
                        <?php
                            if ($row->event_date == '2099-12-31 00:00:00')
                            {
                                echo Text::_('EB_TBC');
                            }
                            else
                            {
                            ?>
                                <div class="ebm-event-month"><?php echo $monthNames[$month];?></div>
                                <div class="ebm-event-day"><?php echo $date; ?></div>
                            <?php
                            }
                        ?>
                    </div>
                </div>
                <div class="<?php echo $span9Class; ?>">
                    <?php
                        if ($titleLinkable)
                        {
                        ?>
                            <a class="url ebm-event-link" href="<?php echo $url; ?>">
		                        <?php
		                        if ($showThumb && $row->thumb && file_exists(JPATH_ROOT.'/media/com_eventbooking/images/thumbs/'.$row->thumb))
		                        {
			                    ?>
                                    <img src="<?php echo $baseUri . '/media/com_eventbooking/images/thumbs/' . $row->thumb; ?>" alt="<?php echo $row->image_alt ?: $row->title; ?>" class="ebm-event-thumb" />
			                    <?php
		                        }

		                        echo $row->title;
		                        ?>
                            </a>
                        <?php
                        }
                        else
                        {
                            if ($showThumb && $row->thumb && file_exists(JPATH_ROOT.'/media/com_eventbooking/images/thumbs/'.$row->thumb))
                            {
                            ?>
                                <img src="<?php echo $baseUri . '/media/com_eventbooking/images/thumbs/' . $row->thumb; ?>" class="ebm-event-thumb" />
                            <?php
                            }

                            echo $row->title;
                        }

                        if ($showCategory)
                        {
                        ?>
                            <br />
                            <i class="<?php echo $iconFolderClass; ?>"></i>
                            <span class="ebm-event-categories"><?php echo $row->categories ; ?></span>
                        <?php
                        }

                        if ($showLocation && $row->location_name)
                        {
                        ?>
                            <br />
                            <i class="<?php echo $iconMapMarkerClass; ?>"></i>
                            <?php
                            if ($row->location_address)
                            {
                            ?>
                                <a href="<?php echo Route::_('index.php?option=com_eventbooking&view=map&location_id='.$row->location_id.'&tmpl=component&format=html&Itemid='.$itemId); ?>" class="eb-colorbox-map">
                                    <?php echo $row->location_name ; ?>
                                </a>
                            <?php
                            }
                            else
                            {
                            ?>
                                <span class="ebm-location-name"><?php echo $row->location_name; ?></span>
                            <?php
                            }
                        }

	                    if ($row->price_text)
	                    {
		                    $priceDisplay = $row->price_text;
	                    }
	                    elseif ($row->individual_price > 0)
	                    {
		                    $symbol        = $row->currency_symbol ?: $config->currency_symbol;
		                    $priceDisplay  = EventbookingHelper::formatCurrency($row->individual_price, $config, $symbol);
	                    }
	                    elseif ($config->show_price_for_free_event)
	                    {
		                    $priceDisplay = Text::_('EB_FREE');
	                    }
	                    else
	                    {
		                    $priceDisplay = '';
	                    }

                        if ($showPrice && $priceDisplay)
                        {
                        ?>
                            <br/>
                            <?php echo '<strong>'.Text::_('EB_PRICE').'</strong>: <span class="ebm-event-price">'. $priceDisplay.'</span>'; ?>
                        <?php
                        }
                        ?>
                </div>
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