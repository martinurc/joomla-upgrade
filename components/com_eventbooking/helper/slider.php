<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\Registry\Registry;

class EventbookingHelperSlider
{
	public static function getSliderSettings(Registry $params, int $numberItems): array
	{
		$sliderSettings = [
			'type'       => 'loop',
			'perPage'    => min($params->get('number_items', 3), $numberItems),
			'speed'      => (int) $params->get('speed', 300),
			'autoplay'   => (bool) $params->get('autoplay', 1),
			'arrows'     => (bool) $params->get('arrows', 1),
			'pagination' => (bool) $params->get('pagination', 1),
			'gap'        => $params->get('gap', '1em'),
		];

		$numberItemsXs = $params->get('number_items_xs', 0);
		$numberItemsSm = $params->get('number_items_sm', 0);
		$numberItemsMd = $params->get('number_items_md', 0);
		$numberItemsLg = $params->get('number_items_lg', 0);

		if ($numberItemsXs && $numberItems)
		{
			$sliderSettings['breakpoints'][576]['perPage'] = min($numberItemsXs, $numberItems);
		}

		if ($numberItemsSm && $numberItems)
		{
			$sliderSettings['breakpoints'][768]['perPage'] = min($numberItemsSm, $numberItems);
		}

		if ($numberItemsMd && $numberItems)
		{
			$sliderSettings['breakpoints'][992]['perPage'] = min($numberItemsMd, $numberItems);
		}

		if ($numberItemsLg && $numberItems)
		{
			$sliderSettings['breakpoints'][1200]['perPage'] = min($numberItemsLg, $numberItems);
		}

		return $sliderSettings;
	}
}