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
 * @var   int                    $Itemid
 */

$language        = Factory::getLanguage();
$config          = EventbookingHelper::getConfig();
$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$btnBtnPrimary   = $bootstrapHelper->getClassMapping('btn btn-primary');

$registrationUrl = trim($item->registration_handle_url ?? '');

if ($registrationUrl)
{
	$languageItem = EventbookingHelperHtml::getCustomizedLanguageItem($item, 'EB_REGISTER');
?>
	<li>
		<a class="<?php echo $btnBtnPrimary . ' eb-register-button eb-external-registration-link'; ?>" href="<?php echo $registrationUrl; ?>" target="_blank"><?php echo Text::_($languageItem); ?></a>
	</li>
<?php
}
elseif (!empty($showCheckbox))
{
	// This is used in table layout to show checkbox to allow adding multiple events to cart
?>
	<input type="checkbox" class="checkbox eb-event-checkbox" name="event_ids[]" value="<?php echo $item->id ?>" />
<?php
}
else
{
	if (in_array($item->registration_type, [0, 1]))
	{
		$cssClasses = [$btnBtnPrimary, 'eb-register-button'];

		if ($config->multiple_booking && !$item->has_multiple_ticket_types)
		{
			if ($config->get('add_time_to_add_to_cart_link', 1))
			{
				$url = 'index.php?option=com_eventbooking&task=cart.add_cart&id=' . (int) $item->id . '&Itemid=' . (int) $Itemid . '&pt=' . time();
			}
			else
			{
				$url = 'index.php?option=com_eventbooking&task=cart.add_cart&id=' . (int) $item->id . '&Itemid=' . (int) $Itemid;
			}

			if (!$item->event_password)
			{
				$cssClasses[] = 'eb-colorbox-addcart';
			}

			$languageItem = 'EB_REGISTER';
		}
		else
		{
			$cssClasses[] = 'eb-individual-registration-button';

			$url = Route::_('index.php?option=com_eventbooking&task=register.individual_registration&event_id=' . $item->id . '&Itemid=' . $Itemid, false);

			if ($item->has_multiple_ticket_types)
			{
				$languageItem = 'EB_REGISTER';
			}
			else
			{
				$languageItem = 'EB_REGISTER_INDIVIDUAL';
			}
		}

		$languageItem = EventbookingHelperHtml::getCustomizedLanguageItem($item, $languageItem);
		?>
			<li>
				<a class="<?php echo implode(' ', $cssClasses); ?>" href="<?php echo $url; ?>"><?php echo Text::_($languageItem); ?></a>
			</li>
		<?php
	}

	if ($item->min_group_number > 0)
	{
		$minGroupNumber = $item->min_group_number;
	}
	else
	{
		$minGroupNumber = (int) $config->get('default_min_number_registrants') ?: 2;
	}

	if ($item->event_capacity > 0 && (($item->event_capacity - $item->total_registrants) < $minGroupNumber))
	{
		$groupRegistrationAvailable = false;
	}
	else
	{
		$groupRegistrationAvailable = true;
	}

	if ($groupRegistrationAvailable && in_array($item->registration_type, [0, 2]) && !$config->multiple_booking && !$item->has_multiple_ticket_types)
	{
		$cssClasses   = [$btnBtnPrimary, 'eb-register-button', 'eb-group-registration-button'];
		$languageItem = EventbookingHelperHtml::getCustomizedLanguageItem($item, 'EB_REGISTER_GROUP');
		?>
			<li>
				<a class="<?php echo implode(' ', $cssClasses); ?>"
				   href="<?php echo Route::_('index.php?option=com_eventbooking&task=register.group_registration&event_id=' . $item->id . '&Itemid=' . $Itemid, false); ?>"><?php echo Text::_('EB_REGISTER_GROUP'); ?></a>
			</li>
		<?php
	}
}
