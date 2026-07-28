<?php
/**
 * @package        Joomla
 * @subpackage     Event Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 - 2024 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$btnPrimary      = $bootstrapHelper->getClassMapping('btn btn-primary');
$locationIds     = $this->params->get('location_ids', []);
$locationIds     = array_filter(ArrayHelper::toInteger($locationIds));
?>
<form name="eb-event-search" method="post" id="eb-event-search">
	<div class="filters btn-toolbar eb-search-bar-container clearfix">
		<div class="filter-search pull-left">
			<input type="text" name="search" class="input-large form-control" value="<?php echo htmlspecialchars($this->state->search, ENT_COMPAT, 'UTF-8'); ?>"
				   placeholder="<?php echo Text::_('EB_KEY_WORDS'); ?>"/>
		</div>
		<div class="btn-group pull-left">
			<?php

			if ($this->config->show_category_filter && empty($this->state->id))
			{
				// Show categories filter if configured
				$filters = [];
				$filters[] = '`access` IN (' . implode(',', Factory::getUser()->getAuthorisedViewLevels()) . ')';

				echo EventbookingHelperHtml::getCategoryListDropdown('category_id', $this->state->category_id, 'class="input-large form-select" onchange="submit();"', EventbookingHelper::getFieldSuffix(), $filters);
			}

			$locations = EventbookingHelperDatabase::getAllLocations();

			if (count($locations) > 1)
			{
				$options   = [];
				$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_ALL_LOCATIONS'), 'id', 'name');

				foreach ($locations as $location)
				{
					if (!count($locationIds) || in_array($location->id, $locationIds))
					{
						$options[] = HTMLHelper::_('select.option', $location->id, $location->name, 'id', 'name');
					}
				}

				echo HTMLHelper::_('select.genericlist', $options, 'location_id', ' class="input-large form-select" onchange="submit();" ', 'id', 'name', $this->state->location_id);
			}

			// Do not display duration filter when the menu item is configured to display past events
			if ($this->params->get('display_events_type', 0) != 3)
			{
				$options   = [];
				$options[] = HTMLHelper::_('select.option', '', Text::_('EB_ALL_DATES'));
				$options[] = HTMLHelper::_('select.option', 'today', Text::_('EB_TODAY'));
				$options[] = HTMLHelper::_('select.option', 'tomorrow', Text::_('EB_TOMORROW'));
				$options[] = HTMLHelper::_('select.option', 'this_week', Text::_('EB_THIS_WEEK'));
				$options[] = HTMLHelper::_('select.option', 'next_week', Text::_('EB_NEXT_WEEK'));
				$options[] = HTMLHelper::_('select.option', 'this_month', Text::_('EB_THIS_MONTH'));
				$options[] = HTMLHelper::_('select.option', 'next_month', Text::_('EB_NEXT_MONTH'));

				echo HTMLHelper::_('select.genericlist', $options, 'filter_duration', ' class="input-large form-select" onchange="submit();" ', 'value', 'text', $this->state->filter_duration);
			}
			?>
		</div>
		<div class="btn-group pull-left">
			<input type="submit" class="<?php echo $btnPrimary; ?> eb-btn-search" value="<?php echo Text::_('EB_SEARCH'); ?>"/>
		</div>
	</div>
</form>