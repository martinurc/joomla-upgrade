<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();

$month = $this->currentDateData['month'];
$year  = $this->currentDateData['year'];
$day   = $this->currentDateData['current_date'];
$date  = $this->currentDateData['start_week_date'];

$monthNavLink = Route::_("index.php?option=com_eventbooking&view=calendar&layout=default&month=$month&year=$year&Itemid=$this->Itemid");
$weekNavLink  = Route::_("index.php?option=com_eventbooking&view=calendar&layout=weekly&date=$date&Itemid=$this->Itemid");
$dayNavLink   = Route::_("index.php?option=com_eventbooking&view=calendar&layout=daily&day=$day&Itemid=$this->Itemid");
?>
<div class="eb-topmenu-calendar <?php echo $bootstrapHelper->getClassMapping('clearfix'); ?>">
    <ul class="eb-menu-calendar <?php echo $bootstrapHelper->getClassMapping('nav nav-pills pull-right'); ?>">
        <li>
            <a class="eb-calendar-view-link<?php if ($layout == 'default') echo ' active'; ?>" href="<?php echo $monthNavLink; ?>" rel="nofollow">
                <?php echo Text::_('EB_MONTHLY_VIEW') ?>
            </a>
        </li>
        <?php
		if ($this->config->activate_weekly_calendar_view)
		{
			$date = $this->currentDateData['start_week_date'];
		?>
            <li>
                <a class="eb-calendar-view-link<?php if ($layout == 'weekly') echo ' active'; ?>" href="<?php echo $weekNavLink; ?>" rel="nofollow">
                    <?php echo Text::_('EB_WEEKLY_VIEW')?>
                </a>
            </li>
        <?php
		}

		if ($this->config->activate_daily_calendar_view)
		{
		?>
            <li>
                <a class="eb-calendar-view-link<?php if ($layout == 'daily') echo ' active'; ?>" href="<?php echo $dayNavLink; ?>" rel="nofollow">
                    <?php echo Text::_('EB_DAILY_VIEW')?>
                </a>
            </li>
        <?php
		}
		?>
    </ul>
</div>