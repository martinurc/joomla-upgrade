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

/**
 * Layout variables
 *
 * @var stdClass                 $item
 * @var Joomla\Registry\Registry $params
 * @var int                      $Itemid
 */

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$clearfix        = $bootstrapHelper->getClassMapping('clearfix');
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$btn             = $bootstrapHelper->getClassMapping('btn');
$iconCalendar    = $bootstrapHelper->getClassMapping('icon-calendar');
$iconMapMaker    = $bootstrapHelper->getClassMapping('icon-map-marker');
$btnPrimary      = $bootstrapHelper->getClassMapping('btn-primary');
$btnBtnPrimary   = $bootstrapHelper->getClassMapping('btn btn-primary');

$config     = EventbookingHelper::getConfig();
$timeFormat = $config->event_time_format ?: 'g:i a';
$dateFormat = $config->date_format;

// Just to be safe in case someone override prepareDisplayData method in wrong way
if (isset($item->cssClasses))
{
	$cssClasses = $item->cssClasses;
}
else
{
	$cssClasses = [];
}

$cssClasses[] = 'eb-event-item-grid-default-layout';

$cssVariables = [];

if ($params->get('use_category_bg_color_from_category') && !empty($item->category->color_code))
{
	$cssVariables[] = '--eb-grid-default-main-category-color: #' . $item->category->color_code;
}

if (count($cssVariables))
{
	$inlineStyles = ' style="' . implode(';', $cssVariables) . '"';
}
else
{
	$inlineStyles = '';
}
?>
<div class="<?php echo implode(' ', $cssClasses); ?>"<?php echo $inlineStyles; ?>>
	<?php
	if (!empty($item->thumb_url))
	{
	?>
		<div class="eb-event-thumb-container <?php echo $clearfix; ?>">
			<a href="<?php echo $item->url; ?>"><img<?php if (!empty($imgLoadingAttr)) echo $imgLoadingAttr; ?> src="<?php echo $item->thumb_url; ?>" class="eb-event-thumb" alt="<?php echo $item->image_alt ?: $item->title; ?>"/></a>
			<?php
			if ($params->get('show_category', 1))
			{
			?>
				<div class="eb-event-main-category"><?php echo $item->category_name; ?></div>
			<?php
			}
			?>
		</div>
	<?php
	}
	?>
	<div class="eb-event-title-container">
		<a class="eb-event-title" href="<?php echo $item->url; ?>"><?php echo $item->title; ?></a>
	</div>
	<div class="eb-event-date-time">
		<i class="<?php echo $iconCalendar; ?>"></i>
		<?php
		if ($item->event_date != EB_TBC_DATE)
		{
			echo HTMLHelper::_('date', $item->event_date, $dateFormat, null);
		}
		else
		{
			echo Text::_('EB_TBC');
		}

		if (strpos($item->event_date, '00:00:00') === false)
		{
		?>
			<span class="eb-time"><?php echo HTMLHelper::_('date', $item->event_date, $timeFormat, null) ?></span>
		<?php
		}

		if ((int) $item->event_end_date)
		{
			echo EventbookingHelperHtml::loadCommonLayout('elements/enddate.php', ['event' => $item]);
		}
		?>
	</div>

	<?php
	if ($item->location && $params->get('show_location', 1))
	{
	?>
		<div class="eb-event-location">
			<i class="<?php echo $iconMapMaker; ?>"></i>
			<?php echo EventbookingHelperHtml::loadCommonLayout('elements/location.php', ['location' => $item->location, 'Itemid' => $Itemid]); ?>
		</div>
	<?php
	}

	if ($item->priceDisplay && $params->get('show_price', 1))
	{
	?>
		<div class="eb-event-price <?php echo $btnBtnPrimary; ?>">
			<span class="eb-individual-price"><?php echo $item->priceDisplay; ?></span>
		</div>
	<?php
	}

	if ($params->get('show_short_description', 1))
	{
	?>
		<div class="eb-event-short-description <?php echo $clearfix; ?>">
			<?php
			if ($params->get('short_description_limit'))
			{
				echo HTMLHelper::_('string.truncate', $item->short_description, (int) $params->get('short_description_limit'));
			}
			else
			{
				echo $item->short_description;
			}
			?>
		</div>
	<?php
	}

	// Event message to tell user that they already registered, need to login to register or don't have permission to register...
	echo EventbookingHelperHtml::loadCommonLayout('common/event_message.php', ['config' => $config, 'event' => $item]);

	if ($params->get('show_register_buttons', 1) || $params->get('show_details_buttons'))
	{
	?>
		<div class="eb-taskbar <?php echo $clearfix; ?>">
			<ul>
				<?php
				if (!$item->is_multiple_date && $params->get('show_register_buttons', 1))
				{
					if ($item->can_register)
					{
						echo EventbookingHelperHtml::loadCommonLayout('common/buttons_register.php', ['item' => $item, 'config' => $config, 'Itemid' => $Itemid]);
					}
					elseif ($item->waiting_list && $item->registration_type != 3 && !EventbookingHelperRegistration::isUserJoinedWaitingList($item->id))
					{
						echo EventbookingHelperHtml::loadCommonLayout('common/buttons_waiting_list.php', ['item' => $item, 'config' => $config, 'Itemid' => $Itemid]);
					}
				}

				if ($params->get('show_details_buttons'))
				{
				?>
					<li>
						<a class="<?php echo $btn; ?>" href="<?php echo $item->url; ?>">
							<?php echo $item->is_multiple_date ? Text::_('EB_CHOOSE_DATE_LOCATION') : Text::_('EB_DETAILS'); ?>
						</a>
					</li>
				<?php
				}
				?>
			</ul>
		</div>
	<?php
	}
	?>
</div>


