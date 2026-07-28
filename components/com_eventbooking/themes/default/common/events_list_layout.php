<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

$return                  = base64_encode(Uri::getInstance()->toString());
$eventPropertiesPosition = (int) $this->params->get('event_properties_position', 2);

if (!$this->config->get('show_register_buttons', 1))
{
	$hideRegisterButtons = true;
}
else
{
	$hideRegisterButtons = false;
}

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper         = $this->bootstrapHelper;
$activeCategoryId        = $this->categoryId;

if ($eventPropertiesPosition === 0)
{
	$eventDescriptionClass = $bootstrapHelper->getClassMapping('span7');
	$eventPropertiesClass  = $bootstrapHelper->getClassMapping('span5');
}
else
{
	$eventDescriptionClass = $bootstrapHelper->getClassMapping('clearfix');
	$eventPropertiesClass  = $bootstrapHelper->getClassMapping('clearfix');
}

$rowFluid   = $bootstrapHelper->getClassMapping('row-fluid');
$btn        = $bootstrapHelper->getClassMapping('btn');
$btnPrimary = $bootstrapHelper->getClassMapping('btn-primary');
$clearfix   = $bootstrapHelper->getClassMapping('clearfix');

if ($this->input->getInt('hmvc_call'))
{
	$hTag = 'h3';
}
else
{
	$hTag = 'h2';
}

if ($this->params->get('image_lazy_loading', 'lazy'))
{
	$imgLoadingAttr = ' loading="lazy"';
}
else
{
	$imgLoadingAttr = '';
}

$lazyLoadingStartIndex = $this->params->get('image_lazy_loading_start_index', 0);
?>
<div class="eb-events-list-items">
	<?php
		foreach ($this->items as $i => $event)
		{
			$layoutData = [
				'item'                => $event,
				'isMultipleDate'      => $event->is_multiple_date,
				'showInviteFriend'    => false,
				'Itemid'              => $this->Itemid,
				'return'              => $return,
				'hideRegisterButtons' => $hideRegisterButtons,
			];

			$registerButtons = EventbookingHelperHtml::loadCommonLayout('common/buttons.php', $layoutData);

			$layoutData = [
				'item'           => $event,
				'config'         => $this->config,
				'location'       => $event->location,
				'showLocation'   => $this->config->show_location_in_category_view,
				'isMultipleDate' => $event->is_multiple_date,
				'nullDate'       => $this->nullDate,
				'Itemid'         => $this->Itemid,
			];

			$eventProperties = EventbookingHelperHtml::loadCommonLayout('common/event_properties.php', $layoutData);

			$cssClasses = $event->cssClasses ?? [];

			$cssClasses[] = 'eb-event-item-list-layout';
			$cssClasses[] = $clearfix;
		?>
			<div class="<?php echo implode(' ', $cssClasses); ?>">
				<<?php echo $hTag; ?> class="eb-event-title-container">
					<?php
					if ($this->config->hide_detail_button !== '1')
					{
					?>
						<a href="<?php echo $event->url; ?>" title="<?php echo $event->title; ?>" class="eb-event-title">
							<?php echo $event->title; ?>
						</a>
					<?php
					}
					else
					{
					?>
						<?php echo $event->title; ?>
					<?php
					}
					?>
				</<?php echo $hTag; ?>>
				<div class="eb-event-information-container <?php echo $clearfix; ?>">
				<?php
				if (in_array($this->config->get('register_buttons_position', 0), [1, 2]))
				{
				?>
					<div class="eb-taskbar eb-register-buttons-top <?php echo $clearfix; ?>">
						<ul>
							<?php
							echo $registerButtons;

							if ($this->config->hide_detail_button !== '1' || $event->is_multiple_date)
							{
							?>
								<li>
									<a class="<?php echo $btn; ?>" href="<?php echo $event->url; ?>">
										<?php echo $event->is_multiple_date ? Text::_('EB_CHOOSE_DATE_LOCATION') : Text::_('EB_DETAILS');?>
									</a>
								</li>
							<?php
							}
							?>
						</ul>
					</div>
					<?php
				}

				if ($eventPropertiesPosition === 0)
				{
				?>
					<div class="<?php echo $rowFluid; ?>">
				<?php
				}

				if ($eventPropertiesPosition == 1)
				{
				?>
					<div class="eb-event-properties-table <?php echo $eventPropertiesClass; ?>">
						<?php echo $eventProperties; ?>
					</div>
				<?php
				}
				?>
				<div class="eb-short-description <?php echo $eventDescriptionClass; ?>">
					<?php
					if (!empty($event->thumb_url))
					{
					?>
						<a href="<?php echo $event->url; ?>"><img<?php if ($imgLoadingAttr && $i >= $lazyLoadingStartIndex) echo $imgLoadingAttr; ?> src="<?php echo $event->thumb_url; ?>" class="eb-thumb-left" alt="<?php echo $event->image_alt ?: $event->title; ?>"/></a>
					<?php
					}

					echo $event->short_description;
					?>
				</div>
				<?php
				if (in_array($eventPropertiesPosition, [0, 2]))
				{
				?>
					<div class="eb-event-properties-table <?php echo $eventPropertiesClass; ?>">
						<?php echo $eventProperties; ?>
					</div>
				<?php
				}

				if ($eventPropertiesPosition == 0)
				{
				?>
				 </div>
				<?php
				}

				if ($this->config->display_ticket_types && !empty($event->ticketTypes))
				{
					echo EventbookingHelperHtml::loadCommonLayout('common/tickettypes.php', ['ticketTypes' => $event->ticketTypes, 'config' => $this->config, 'event' => $event]);
				?>
					<div class="<?php echo $clearfix; ?>"></div>
				<?php
				}

				// Event message to tell user that they already registered or need to log in to register or don't have permission to register...
				echo EventbookingHelperHtml::loadCommonLayout('common/event_message.php', ['config' => $this->config, 'event' => $event]);

				if (in_array($this->config->get('register_buttons_position', 0), [0, 2]))
				{
				?>
					<div class="eb-taskbar <?php echo $clearfix; ?>">
						<ul>
							<?php
							echo $registerButtons;

							if ($this->config->hide_detail_button !== '1' || $event->is_multiple_date)
							{
							?>
								<li>
									<a class="<?php echo $btn; ?>" href="<?php echo $event->url; ?>">
										<?php echo $event->is_multiple_date ? Text::_('EB_CHOOSE_DATE_LOCATION') : Text::_('EB_DETAILS');?>
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
			</div>
		<?php
		}
	?>
</div>
<?php

// Add Google Structured Data
PluginHelper::importPlugin('eventbooking');
Factory::getApplication()->triggerEvent('onDisplayEvents', [$this->items]);
