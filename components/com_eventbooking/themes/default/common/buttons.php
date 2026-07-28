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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Layout variables
 * -----------------
 * @var   EventbookingTableEvent $item
 * @var   boolean                $isMultipleDate
 * @var   boolean                $showInviteFriend
 * @var   int                    $Itemid
 * @var   string                 $return
 */

$bootstrapHelper   = EventbookingHelperBootstrap::getInstance();
$config            = EventbookingHelper::getConfig();
$btnClass          = $bootstrapHelper->getClassMapping('btn');
$btnBtnPrimary     = $bootstrapHelper->getClassMapping('btn btn-primary');
$iconPencilClass   = $bootstrapHelper->getClassMapping('icon-pencil');
$iconOkClass       = $bootstrapHelper->getClassMapping('icon-ok');
$iconRemoveClass   = $bootstrapHelper->getClassMapping('icon-remove');
$iconDownloadClass = $bootstrapHelper->getClassMapping('icon-download');
$clearfixClass     = $bootstrapHelper->getClassMapping('clearfix');

if (empty($hideRegisterButtons) && !$item->is_multiple_date)
{
	if ($item->can_register)
	{
		echo EventbookingHelperHtml::loadCommonLayout('common/buttons_register.php', ['item' => $item, 'Itemid' => $Itemid]);
	}
	elseif ($item->waiting_list && $item->registration_type != 3 && !EventbookingHelperRegistration::isUserJoinedWaitingList($item->id))
	{
		echo EventbookingHelperHtml::loadCommonLayout('common/buttons_waiting_list.php', ['item' => $item, 'Itemid' => $Itemid]);
	}
}

if ($config->show_save_to_personal_calendar && $item->event_date != EB_TBC_DATE)
{
?>
	<li class="eb-save-to-calendar-buttons">
		<?php echo EventbookingHelperHtml::loadCommonLayout('common/save_calendar.php', ['item' => $item, 'Itemid' => $Itemid]); ?>
	</li>
<?php
}

if ($showInviteFriend && $config->show_invite_friend && EventbookingHelperRegistration::canInviteFriend($item))
{
?>
	<li>
		<a class="<?php echo $btnClass; ?> eb-colorbox-invite" href="<?php echo Route::_('index.php?option=com_eventbooking&view=invite&tmpl=component&id=' . $item->id . '&Itemid=' . $Itemid, false); ?>"><?php echo Text::_('EB_INVITE_FRIEND'); ?></a>
	</li>
<?php
}

$user = Factory::getUser();

if ($user->guest)
{
	return;
}

if ($item->enable_cancel_registration)
{
	$registrantId = EventbookingHelperRegistration::getRegistrantId($item->id);

	if ($registrantId !== false && EventbookingHelperRegistration::canCancelRegistrationNow($item))
	{
		if (EventbookingHelperRegistration::isUserJoinedWaitingList($item->id))
		{
			$buttonLabel = 'EB_CANCEL_WAITING_LIST';
		}
		else
		{
			$buttonLabel = 'EB_CANCEL_REGISTRATION';
		}
	?>
		<li>
			<a class="<?php echo $btnClass; ?>"
			   href="javascript:cancelRegistration(<?php echo $registrantId; ?>)"><?php echo Text::_($buttonLabel); ?></a>
		</li>
	<?php
	}
}

if (!$config->get('show_actions_button', '1'))
{
	return;
}

if (EventbookingHelperAcl::canEditEvent($item))
{
?>
	<li>
		<a class="<?php echo $btnClass; ?>"
		   href="<?php echo Route::_('index.php?option=com_eventbooking&view=event&layout=form&id=' . $item->id . '&Itemid=' . $Itemid . '&return=' . $return, false); ?>">
			<i class="<?php echo $iconPencilClass; ?>"></i>
			<?php echo Text::_('EB_EDIT'); ?>
		</a>
	</li>
<?php
}

if (EventbookingHelperAcl::canPublishUnpublishEvent($item))
{
	if ($item->published == 1)
	{
		$link  = Route::_('index.php?option=com_eventbooking&task=event.unpublish&id=' . $item->id . '&Itemid=' . $Itemid . '&return=' . $return, false);
		$text  = Text::_('EB_UNPUBLISH');
		$class = $iconRemoveClass;
	}
	else
	{
		$link  = Route::_('index.php?option=com_eventbooking&task=event.publish&id=' . $item->id . '&Itemid=' . $Itemid . '&return=' . $return, false);
		$text  = Text::_('EB_PUBLISH');
		$class = $iconOkClass;
	}
	?>
	<li>
		<a class="<?php echo $btnClass; ?>" href="<?php echo $link; ?>">
			<i class="<?php echo $class; ?>"></i>
			<?php echo $text; ?>
		</a>
	</li>
	<?php
}

if ($item->total_registrants && EventbookingHelperAcl::canExportEventRegistrant($item))
{
?>
	<li>
		<a class="<?php echo $btnClass; ?>" href="<?php echo Route::_('index.php?option=com_eventbooking&task=registrant.export&event_id=' . $item->id . '&Itemid=' . $Itemid); ?>">
			<i class="<?php echo $iconDownloadClass; ?>"></i>
			<?php echo Text::_('EB_EXPORT_REGISTRANTS'); ?>
		</a>
	</li>
<?php
}
