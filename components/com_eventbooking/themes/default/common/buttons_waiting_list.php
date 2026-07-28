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

/**
 * Layout variables
 * -----------------
 * @var   EventbookingTableEvent $item
 * @var   int                    $Itemid
 * @var   string                 $return
 */


$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$btnBtnPrimary   = $bootstrapHelper->getClassMapping('btn btn-primary');

$config = EventbookingHelper::getConfig();

if ($item->waiting_list_capacity == 0)
{
	$numberWaitingListAvailable =  1000; // Fake number
}
else
{
	$numberWaitingListAvailable = max($item->waiting_list_capacity - EventbookingHelperRegistration::countNumberWaitingList($item), 0);
}

if (in_array($item->registration_type, [0, 1]) && $numberWaitingListAvailable)
{
	$cssClasses = [$btnBtnPrimary, 'eb-register-button', 'eb-join-waiting-list-individual-button'];
?>
	<li>
		<a class="<?php echo implode(' ', $cssClasses); ?>" href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $item->id . '&Itemid=' . $Itemid, false); ?>"><?php echo Text::_('EB_REGISTER_INDIVIDUAL_WAITING_LIST'); ?></a>
	</li>
<?php
}

if (in_array($item->registration_type, [0, 2]) && $numberWaitingListAvailable > 1 && !$config->multiple_booking)
{
	$cssClasses = [$btnBtnPrimary, 'eb-register-button', 'eb-join-waiting-list-group-button'];
?>
	<li>
		<a class="<?php echo implode(' ', $cssClasses); ?>" href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.group_registration&event_id=' . $item->id . '&Itemid=' . $Itemid, false); ?>"><?php echo Text::_('EB_REGISTER_GROUP_WAITING_LIST'); ?></a>
	</li>
<?php
}
