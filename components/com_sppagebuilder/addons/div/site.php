<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

class SppagebuilderAddonDiv extends SppagebuilderAddons
{

	/**
	 * The render method of the DIV addon.
	 * The HTML of the DIV addon is managed from
	 * the `addon-parser.php` file. Nothing is needed to return from here.
	 *
	 * @return 	string
	 * @since 	4.0.0
	 */
	public function render()
	{
		return '';
	}

	/**
	 * The DIV addon's CSS stylings.
	 *
	 * @return 	string 	The CSS string.
	 * @since 	4.0.0
	 */
	public function css()
	{
		$settings = $this->addon->settings;
		$addon_id = '#sppb-addon-' . $this->addon->id;
		$cssHelper = new CSSHelper($addon_id);

		$css = '';

		$props = [
			'display' => 'display',
			'width' => 'width',
			'height' => 'height',
			'max_width' => 'max-width',
			'max_height' => 'max-height',
			'overflow' => 'overflow'
		];
		$units = [
			'display' => false,
			'overflow' => false,
		];

		if (isset($settings->reverse_direction) && $settings->reverse_direction) {
			if (is_object($settings->reverse_direction)) {
				if (is_object($settings->flex_direction)) {
					foreach ($settings->reverse_direction as $key => $value) {
						if (!empty($value) && !empty($settings->flex_direction->$key)) {
							$settings->flex_direction->$key = $settings->flex_direction->$key . '-reverse';
						}
					}
				} else {
					foreach ($settings->reverse_direction as $key => $value) {
						if (!empty($value) && !empty($settings->flex_direction)) {
							$settings->flex_direction->$key = $settings->flex_direction . '-reverse';
						}
					}
				}
			} else if (!empty($settings->flex_direction)) {
				$settings->flex_direction = $settings->flex_direction . '-reverse';
			}
		}

		if (isset($settings->display) && \in_array($settings->display, ['flex', 'inline-flex'])) {
			$currentFlexDirection = $cssHelper->getResponsiveValue($settings->flex_direction);
			$isColumnDirection = isset($currentFlexDirection) && $currentFlexDirection === 'column';

			$justifyKey = $isColumnDirection ? 'justify_content_vertical' : 'justify_content';
			$alignKey = $isColumnDirection ? 'align_items_vertical' : 'align_items';

			$props = array_merge($props, [
				'flex_direction' => 'flex-direction',
				$justifyKey => 'justify-content',
				$alignKey => 'align-items',
				'flex_gap' => 'gap',
				'flex_wrap' => 'flex-wrap'
			]);

			$units = array_merge($units, [
				'flex_direction' => false,
				$justifyKey => false,
				$alignKey => false,
				'flex_gap' => false,
				'flex_wrap' => false,
			]);
		}

		if (isset($settings->display) && \in_array($settings->display, ['grid'], true)) {
			$props = array_merge($props, [
				'grid_auto_flow' => 'grid-auto-flow',
				'grid_justify' => 'justify-content',
				'grid_align' => 'align-items',
			]);

			$units = array_merge($units, [
				'grid_auto_flow' => false,
				'grid_justify' => false,
				'grid_align' => false,
			]);

			$css .= $cssHelper->parseGridTemplate(':self', $settings, 'grid_template_columns', 'grid-template-columns');
			$css .= $cssHelper->parseGridTemplate(':self', $settings, 'grid_template_rows', 'grid-template-rows');
			$css .= $cssHelper->parseGridGap(':self', $settings, 'grid_gap');
		}

		$divStyle = $cssHelper->generateStyle(':self', $settings, $props, $units);
		$transformCss = $cssHelper->generateTransformStyle(':self', $settings, 'transform');
		
		$css .= $divStyle;
		$css .= $transformCss;

		return $css;
	}

	public static function getTemplate() {
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$output  = '<style type="text/css">';

		$output .= $lodash->generateTransformCss('', 'data.transform');

		$output .= '</style>';

		return $output;
	}
}
