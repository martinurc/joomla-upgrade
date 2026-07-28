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

$item = $this->item ;

// This is needed because someone might override EventbookingViewEventHtml class, it is here for backward compatible purpose
EventbookingHelper::callOverridableHelperMethod('Data', 'prepareDisplayData', [[$item], $item->main_category_id, $this->config, $this->Itemid]);

$socialUrl = Uri::getInstance()->toString(['scheme', 'user', 'pass', 'host']) . $item->url;

/* @var EventbookingHelperBootstrap $bootstrapHelper*/
$bootstrapHelper   = $this->bootstrapHelper;
$iconPencilClass   = $bootstrapHelper->getClassMapping('icon-pencil');
$iconOkClass       = $bootstrapHelper->getClassMapping('icon-ok');
$iconRemoveClass   = $bootstrapHelper->getClassMapping('icon-remove');
$iconDownloadClass = $bootstrapHelper->getClassMapping('icon-download');
$btnClass          = $bootstrapHelper->getClassMapping('btn');
$iconPrint         = $bootstrapHelper->getClassMapping('icon-print');
$clearfixClass     = $bootstrapHelper->getClassMapping('clearfix');
$return = base64_encode(Uri::getInstance()->toString());

$isMultipleDate = false;

if ($this->config->show_children_events_under_parent_event && $item->event_type == 1)
{
	$isMultipleDate = true;
}

$offset = Factory::getApplication()->get('offset');

if ($this->showTaskBar)
{
	$layoutData = [
		'item'              => $this->item,
		'isMultipleDate'    => $isMultipleDate,
		'showInviteFriend'  => true,
		'Itemid'            => $this->Itemid,
		'return'            => $return,
	];

	$registerButtons = EventbookingHelperHtml::loadCommonLayout('common/buttons.php', $layoutData);
}

if (!$this->config->get('show_group_rates', 1))
{
	$this->rowGroupRates = [];
}

$cssClasses   = $item->cssClasses ?? [];
$cssClasses[] = 'eb-container';
$cssClasses[] = 'eb-event';

if ($this->print)
{
	$cssClasses[] = 'eb-print-page';
}

if ($this->input->getInt('hmvc_call'))
{
	$hTag = 'h2';
}
else
{
	$hTag = 'h1';
}
?>
<div id="eb-event-page" class="<?php echo implode(' ', $cssClasses); ?><?php if ($this->config->activate_transparent) echo ' eb-container-transparent'; ?>">
	<div class="eb-box-heading <?php echo $clearfixClass; ?>">
		<<?php echo $hTag; ?> class="eb-page-heading">
			<?php
			echo $item->title;

			if ($this->config->get('show_print_button', '1') === '1' && !$this->print)
			{
				$uri = clone Uri::getInstance($this->url);
				$uri->setVar('tmpl', 'component');
				$uri->setVar('print', '1');
			?>
				<div id="pop-print" class="btn hidden-print">
					<a href="<?php echo $uri->toString();?> " rel="nofollow" target="_blank">
						<span class="<?php echo $iconPrint; ?>"></span>
					</a>
				</div>
			<?php
			}
			?>
		</<?php echo $hTag; ?>>
	</div>
	<div id="eb-event-details" class="eb-description">
		<?php
			// Facebook, twitter, Gplus share buttons
			if ($this->config->show_fb_like_button || $this->config->show_twitter_button)
			{
				echo $this->loadTemplate('share', ['socialUrl' => $socialUrl]);
			}

			if ($this->showTaskBar && in_array($this->config->get('register_buttons_position', 0), [1, 2]))
			{
			?>
				<div class="eb-taskbar eb-register-buttons-top <?php echo $clearfixClass; ?>">
					<ul>
						<?php echo $registerButtons; ?>
					</ul>
				</div>
			<?php
			}
		?>

		<div class="eb-description-details <?php echo $clearfixClass; ?>">
			<?php
				if ($this->config->get('show_image_in_event_detail', 1) && $this->config->display_large_image && !empty($item->image_url))
				{
				?>
					<img src="<?php echo $item->image_url; ?>" alt="<?php echo $item->image_alt ?: $item->title; ?>" class="eb-event-large-image img-polaroid"/>
				<?php
				}
				elseif ($this->config->get('show_image_in_event_detail', 1) && !empty($item->thumb_url))
				{
					EventbookingHelperJquery::colorbox('a.eb-modal');
				?>
					<a href="<?php echo $item->image_url; ?>" class="eb-modal"><img src="<?php echo $item->thumb_url; ?>" class="eb-thumb-left" alt="<?php echo $item->image_alt ?: $item->title; ?>"/></a>
				<?php
				}

				echo $item->description;
			?>
		</div>

		<div id="eb-event-info" class="<?php echo $bootstrapHelper->getClassMapping('row-fluid clearfix'); ?>">
			<?php
			if (!empty($this->items))
			{
				if (EventbookingHelperHtml::isLayoutOverridden('common/events_children.php'))
				{
					echo EventbookingHelperHtml::loadCommonLayout('common/events_children.php', ['items' => $this->items, 'config' => $this->config, 'Itemid' => $this->Itemid, 'nullDate' => $this->nullDate, 'ssl' => 0, 'viewLevels' => $this->viewLevels, 'categoryId' => $this->item->category_id, 'bootstrapHelper' => $this->bootstrapHelper]);
				}
				else
				{
					// Prepare additional data needed by events_table_layout
					$this->categoryId = $this->item->category_id;
					$this->category = EventbookingHelperDatabase::getCategory($this->item->category_id);

					echo $this->loadCommonLayout('common/events_table_layout.php', ['displayChildrenEvents' => true]);
				}
			}
			else
			{
				$leftCssClass = 'span8';

				if (empty($this->rowGroupRates))
				{
					$leftCssClass = 'span12';
				}
			?>
				<div id="eb-event-info-left" class="<?php echo $bootstrapHelper->getClassMapping($leftCssClass); ?>">
					<h3 id="eb-event-properties-heading">
						<?php echo Text::_('EB_EVENT_PROPERTIES'); ?>
					</h3>
					<?php
					$layoutData = [
						'item'           => $this->item,
						'config'         => $this->config,
						'location'       => $item->location,
						'showLocation'   => true,
						'isMultipleDate' => false,
						'nullDate'       => $this->nullDate,
						'Itemid'         => $this->Itemid,
					];

					echo EventbookingHelperHtml::loadCommonLayout('common/event_properties.php', $layoutData);
					?>
				</div>

				<?php
				if (count($this->rowGroupRates))
				{
					echo $this->loadTemplate('group_rates');
				}
			}
			?>
		</div>
		<div class="<?php echo $clearfixClass; ?>"></div>
	<?php

	if ($this->config->show_location_info_in_event_details && $item->location && ($item->location->image || EventbookingHelper::isValidMessage($item->location->description)))
	{
		echo $this->loadTemplate('location', ['location' => $item->location]);
	}

	foreach ($this->horizontalPlugins as $plugin)
	{
	?>
		<h3 class="eb-horizontal-plugin-header<?php if (!empty($plugin['name'])) echo ' eb-plugin-' . $plugin['name']; ?>"><?php echo $plugin['title']; ?></h3>
	<?php
		echo $plugin['form'];
	}

	if ($this->config->display_ticket_types && !empty($item->ticketTypes))
	{
		echo EventbookingHelperHtml::loadCommonLayout('common/tickettypes.php', ['ticketTypes' => $item->ticketTypes, 'config' => $this->config, 'event' => $item]);
	?>
		<div class="<?php echo $clearfixClass; ?>"></div>
	<?php
	}

	echo EventbookingHelperHtml::loadCommonLayout('common/event_message.php', ['config' => $this->config, 'event' => $item]);

	if ($this->showTaskBar && in_array($this->config->get('register_buttons_position', 0), [0, 2])
	) {
		?>
		<div class="eb-taskbar eb-register-buttons-bottom <?php echo $clearfixClass; ?>">
			<ul>
				<?php
				echo $registerButtons;

				if ($this->config->get('show_back_button', 1)) {
				?>
	                <li>
		                <a class="eb-button-button-link <?php echo $bootstrapHelper->getClassMapping('btn'); ?>" href="javascript: window.history.go(-1);"><?php echo Text::_('EB_BACK'); ?></a>
	                </li>
				<?php
				}
				?>
			</ul>
		</div>
	<?php
	}

	if (count($this->plugins))
	{
		echo $this->loadTemplate('plugins');
	}

	if ($this->config->show_social_bookmark && !$this->print)
	{
		echo $this->loadTemplate('social_buttons', ['socialUrl' => $socialUrl]);
	}
?>
	</div>
</div>

<?php
Factory::getApplication()->getDocument()->addScriptDeclaration('
		function cancelRegistration(registrantId)
		{
			var form = document.adminForm ;
	
			if (confirm("' . Text::_('EB_CANCEL_REGISTRATION_CONFIRM') . '"))
			{
				form.task.value = "registrant.cancel" ;
				form.id.value = registrantId ;
				form.submit() ;
			}
		}
	');
?>
<form name="adminForm" id="adminForm" action="<?php echo Route::_('index.php?option=com_eventbooking&Itemid=' . $this->Itemid); ?>" method="post">
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<script type="text/javascript">
	<?php
	if ($this->print)
	{
	?>
		window.print();
	<?php
	}
?>
</script>
<?php
Factory::getApplication()->triggerEvent('onDisplayEvents', [[$item]]);
