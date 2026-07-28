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
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

ToolbarHelper::title(Text::_('EB_DASHBOARD'), 'generic.png');

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
?>
<div class="<?php echo $bootstrapHelper->getClassMapping('row-fluid'); ?>">
	<div id="cpanel" class="<?php echo $bootstrapHelper->getClassMapping('span6'); ?>">
		<?php
		$this->quickiconButton('index.php?option=com_eventbooking&view=configuration', 'icon-48-eventbooking-config.png', Text::_('EB_CONFIGURATION'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=categories', 'icon-48-eventbooking-categories.png', Text::_('EB_CATEGORIES'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=events', 'icon-48-eventbooking-events.png', Text::_('EB_EVENTS'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=registrants', 'icon-48-eventbooking-registrants.png', Text::_('EB_REGISTRANTS'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=fields', 'icon-48-eventbooking-fields.png', Text::_('EB_CUSTOM_FIELDS'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=locations', 'icon-48-eventbooking-locations.png', Text::_('EB_LOCATIONS'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=coupons', 'icon-48-eventbooking-coupons.png', Text::_('EB_COUPONS'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=plugins', 'icon-48-eventbooking-payments.png', Text::_('EB_PAYMENT_PLUGINS'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=language', 'icon-48-eventbooking-language.png', Text::_('EB_TRANSLATION'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=mitems', 'icon-48-mail.png', Text::_('EB_EMAIL_MESSAGES'));
		$this->quickiconButton('index.php?option=com_eventbooking&task=registrant.export', 'icon-48-eventbooking-export.png', Text::_('EB_EXPORT_REGISTRANTS'));

		//Permission settings
		$return = urlencode(base64_encode(Uri::getInstance()->toString()));

		$this->quickiconButton('index.php?option=com_config&amp;view=component&amp;component=com_eventbooking&amp;return=' . $return, 'icon-48-acl.png', Text::_('EB_PERMISSIONS'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=massmail', 'icon-48-eventbooking-massmail.png', Text::_('EB_MASS_MAIL'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=countries', 'icon-48-countries.png', Text::_('EB_COUNTRIES'));
		$this->quickiconButton('index.php?option=com_eventbooking&view=states', 'icon-48-states.png', Text::_('EB_STATES'));

		$link = 'index.php?option=com_eventbooking';

		switch ($this->updateResult['status'])
		{
			case 0:
				$icon = 'icon-48-deny.png';
				$text = Text::_('EB_UPDATE_CHECKING_ERROR');
				break;
			case 1:
				$icon = 'icon-48-jupdate-uptodate.png';
				$text = $this->updateResult['message'];
				break;
			case 2:
				$icon = 'icon-48-jupdate-updatefound.png';
				$text = $this->updateResult['message'];
				$link = 'index.php?option=com_installer&view=update';
				break;
			default:
				$icon = 'icon-48-download.png';
				$text = Text::_('EB_UPDATE_CHECKING');
				break;
		}

		$this->quickiconButton($link, $icon, $text, 'update-check');
		?>
	</div>
	<div class="<?php echo $bootstrapHelper->getClassMapping('span6'); ?>">
		<?php
			echo HTMLHelper::_('bootstrap.startAccordion', 'statistics_pane', ['active' => 'statistic']);
			echo HTMLHelper::_('bootstrap.addSlide', 'statistics_pane', Text::_('EB_STATISTICS'), 'statistic');
			echo $this->loadTemplate('statistics');
			echo HTMLHelper::_('bootstrap.endSlide');
			echo HTMLHelper::_('bootstrap.addSlide', 'statistics_pane', Text::_('EB_UPCOMING_EVENTS'), 'upcoming_events');
			echo $this->loadTemplate('upcoming_events');
			echo HTMLHelper::_('bootstrap.endSlide');
			echo HTMLHelper::_('bootstrap.addSlide', 'statistics_pane', Text::_('EB_LATEST_REGISTRANTS'), 'registrants');
			echo $this->loadTemplate('registrants');
			echo HTMLHelper::_('bootstrap.endSlide');
			echo HTMLHelper::_('bootstrap.addSlide', 'statistics_pane', Text::_('EB_USEFUL_LINKS'), 'links_panel');
			echo $this->loadTemplate('useful_links');
			echo HTMLHelper::_('bootstrap.endSlide');
			echo HTMLHelper::_('bootstrap.endAccordion');
		?>
	</div>
</div>
<style>
	#statistics_pane
	{
		margin:0 !important
	}
</style>
