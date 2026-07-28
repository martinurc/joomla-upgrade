<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Global Colors traits
 */
trait GlobalColorsTrait
{
	public function globalColors()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['POST', 'PUT', 'PATCH', 'DELETE'], $method);

		if ($method === 'GET')
		{
			$this->getGlobalColors();
		}
	}

	private function getDefaultStyleColors()
	{
		$colorPrefix = 'sppb-';

		$keysToExtract = [
			"topbar_bg_color",
			"topbar_text_color",
			"header_bg_color",
			"logo_text_color",
			"menu_text_color",
			"menu_text_hover_color",
			"menu_text_active_color",
			"menu_dropdown_bg_color",
			"menu_dropdown_text_color",
			"menu_dropdown_text_hover_color",
			"menu_dropdown_text_active_color",
			"offcanvas_menu_icon_color",
			"offcanvas_menu_bg_color",
			"offcanvas_menu_items_and_items_color",
			"offcanvas_menu_active_menu_item_color",
			"text_color",
			"bg_color",
			"link_color",
			"link_hover_color",
			"footer_bg_color",
			"footer_text_color",
			"footer_link_color",
			"footer_link_hover_color",
		];

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['params'])
			->from($db->quoteName('#__template_styles'))
			->where($db->quoteName('client_id') . ' = 0')
			->where($db->quoteName('home') . ' = 1');
		$db->setQuery($query);

		try
		{
			$ext = $db->loadObject();

			$styleObj = !empty($ext->params) ? $ext->params : "{}";

			$styleObjDecoded = \json_decode($styleObj);

			$newStyleObj = new \stdClass();

			foreach ($keysToExtract as $key) {
				if (isset($styleObjDecoded->$key)) {
					$newStyleObj->$key = $styleObjDecoded->$key;
				}
			}

			$styleObjDecoded = $newStyleObj;

			if (empty($styleObjDecoded->custom_style) && !empty($styleObjDecoded->preset)) {
				$styleObjDecoded = json_decode($styleObjDecoded->preset);
			}

			$colorValues = [];

			foreach ($styleObjDecoded as $key => $value) {
				if (is_string($value) && !empty($value)) {
					array_push($colorValues, [
						'id' => uniqid(),
						'value' => $value,
						'name' => $colorPrefix . str_replace('_', '-', strtolower($key))
					]);
				}
			}

			return json_encode($colorValues);
		} catch (\Exception $e) {
			return "{}";
		}
	}

	private function getGlobalColors()
	{
		$colorPrefix = 'sppb-';
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['id', 'name', 'colors'])
			->from($db->quoteName('#__sppagebuilder_colors'))
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);

		$colors = [];

		try
		{
			$colors = $db->loadObjectList();
			$ext = $this->getDefaultStyleColors();
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!empty($colors))
		{
			foreach ($colors as &$color)
			{
				$color->colors = \json_decode($color->colors);

				if (isset($color->name) && !empty($color->name))
				{
					$color->name = str_replace(' ', '-', trim($color->name));
				}

				if (isset($color->colors) && !empty($color->colors))
				{
					foreach ($color->colors as &$colorValue)
					{
						if (isset($colorValue->name))
						{
							$colorValue->name = str_replace(' ', '-', trim($colorValue->name));
							$colorValue->name = str_replace('_', '-', $colorValue->name);
							$colorValue->name = strtolower($colorPrefix . $color->name . '-' . $colorValue->name);
						}
					}
				}
			}

			unset($color);

		}
		

		if ($ext !== '[]' && $ext !== '{}') {
			array_push($colors, \json_decode('{ "id": -1, "name": "' . Text::_("COM_SPPAGEBUILDER_EDITOR_SETTINGS_PAGE_DEFAULT_GLOBAL_THEME_COLOR_TITLE") . '", "colors": ' . $ext . ' }'));
		}

		$this->sendResponse($colors);
	}
}
