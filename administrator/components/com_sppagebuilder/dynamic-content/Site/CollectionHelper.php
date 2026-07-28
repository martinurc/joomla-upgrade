<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use AddonParser;
use ApplicationHelper;
use DateTime;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Menu;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Date;

class CollectionHelper
{
    /**
     * List of fields that are not prefixed with the collection title.
     *
     * @var array
     *
     * @since 5.5.0
     */
    public const NON_PREFIXED_FIELDS = [
        'created', 'modified', 'language', 'created_by', 'modified_by', 'published'
    ];

    /**
     * Get the selector from the CSS.
     *
     * @param string $css The CSS content.
     * @return string|null The selector or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getSelector($css)
    {
        $pattern = "/^(.*?)\{/";
		preg_match($pattern, $css, $matches);
		return $matches[1] ?? null;
    }

    /**
     * Generate the CSS for the collection addon.
     *
     * @param object $addon The addon object.
     * @param object $layouts The layouts object.
     * @return array The generated CSS.
     *
     * @since 5.5.0
     */
    public static function generateDynamicContentCSS($addon, $layouts)
	{
		if (empty($addon->name))
		{
			return '';
		}

		$addonPath = AddonParser::getAddonPath($addon->name);
		$output = '';

		if (file_exists($addonPath . '/site.php'))
		{
			require_once $addonPath . '/site.php';

			$addonClassName = ApplicationHelper::generateSiteClassName($addon->name);
			$addonInstance = new $addonClassName($addon);

			$addonCss = $layouts->addon_css->render(array('addon' => $addon));

			if (method_exists($addonClassName, 'css'))
			{
				$css = $addonInstance->css();
				$addonSelector = static::getSelector($addonCss);
				$instanceSelector = static::getSelector($css);

                if (empty($addonSelector) || empty($instanceSelector)) {
                    return [];
                }

				return [
					$addonSelector => $addonCss,
					$instanceSelector => $css
				];
			}
		}

		return $output;
	}

    /**
     * Get the detail page ID for the dynamic content collection.
     *
     * @param int $collectionId The collection ID.
     * @return int|null The detail page ID or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getDetailPageId($collectionId)
    {
        if (empty($collectionId)) {
            return null;
        }

        $page = Page::where('extension', 'com_sppagebuilder')
            ->where('extension_view', 'dynamic_content:detail')
            ->where('view_id', $collectionId)
            ->first(['id']);

        return $page->id ?? null;
    }

    /**
     * Create the route url for the dynamic content detail page.
     *
     * @param object $item The item to create the route url for.
     * @return string|null The route url or null if not found.
     *
     * @since 5.5.0
     */
    public static function createRouteUrl($item)
    {
        $collectionId = $item['collection_id'];
        $itemId = $item['id'];
        $pageId = static::getDetailPageId($collectionId);

        if (empty($collectionId) || empty($itemId) || empty($pageId)) {
            return null;
        }

        $menuItemId = static::getCurrentMenuItemId($collectionId);
        $routeUrl = 'index.php?option=com_sppagebuilder&view=dynamic&collection_id=' . $collectionId . '&collection_item_id=' . $itemId;

        if (!empty($menuItemId)) {
            $routeUrl .= '&Itemid=' . $menuItemId;
        }

        return Route::_($routeUrl, false);
    }

    /**
     * Get the dynamic content data from the item.
     *
     * @param object $attribute The attribute to get the data from.
     * @param array $item The item to get the data from.
     * @return array|null The dynamic content data or null if not found.
     *
     * @since 5.5.0
     */
    public static function getDynamicContentData($attribute, $item)
    {
        if (empty($attribute) || empty($item)) {
            return null;
        }

        $path = $attribute->path ?? '';
        $segments = explode('.', $path);

        if (empty($segments) || !is_array($segments)) {
            $segments = [];
        }

        $value = $item;

        foreach ($segments as $segment) {
            $key = in_array($segment, static::NON_PREFIXED_FIELDS)
                ? $segment
                : CollectionItemsService::createFieldKey((int) $segment);

            if (is_array($value)) {
                if (!array_key_exists($key, $value)) {
                    $value = static::getReferenceValueByPath($value['id'], $segment);
                } else {
                    $value = $value[$key] ?? null;
                }
            }

            $value = is_object($value) ? (array) $value : $value;
        }

        if (is_array($value)) {
            if (array_key_exists('value', $value)) {
                $value = $value['value'];
            }
        }

        if (is_array($value) || is_object($value)) {
            return $value;
        }

        // Pick the value from the option store for the option type field.
        $optionStore = $item['option_store'] ?? [];

        if (isset($optionStore[$value])) {
            return $optionStore[$value];
        }

        return $value;
    }

    /**
     * When creating links for nested dynamic content fields, we need to get the parent object
     * that contains the actual link target, rather than the final field value.
     * 
     * For example, if we have a blog post with an author reference field:
     * {
     *     "id": 1,
     *     "field_1": "My Blog Post",              // title field
     *     "field_2": {                            // author reference field
     *         "id": 123,
     *         "field_3": "John Smith",            // author name field
     *         "field_4": "john@example.com"       // author email field
     *     }
     * }
     * 
     * And we want to link to the author's email (field_2.field_4), we need to return
     * the entire author object (field_2) as we need the author id to create the link to navigate there.
     * 
     * This method extracts the parent object by traversing the attribute path
     * and stopping at the second-to-last segment.
     *
     * @param array $item The item to prepare.
     * @param object $attribute The attribute to prepare the item for.
     * @return array|null The prepared item or null if the item is empty.
     *
     * @since 5.5.0
     */
    public static function prepareItemForLink($item, $attribute)
    {
        if (empty($item) || empty($attribute)) {
            return null;
        }

        $path = $attribute->path ?? '';
        $segments = explode('.', $path);

        if (empty($segments) || !is_array($segments)) {
            $segments = [];
        }

        if (count($segments) === 1) {
            return $item;
        }

        $value = $item;
        $length = count($segments);
        $segmentsUntilLast = array_slice($segments, 0, $length - 1);

        foreach ($segmentsUntilLast as $segment) {
            $key = in_array($segment, static::NON_PREFIXED_FIELDS)
                ? $segment
                : CollectionItemsService::createFieldKey((int) $segment);

            if (is_array($value)) {
                if (!array_key_exists($key, $value)) {
                    $value = static::getReferenceValueByPath($value['id'], $segment);

                    if (!empty($value['reference_item_id'])) {
                        $value = (new CollectionItemsService)->getCollectionItem($value['reference_item_id']);
                    }
                } else {
                    $value = $value[$key] ?? null;
                }
            }

            $value = is_object($value) ? (array) $value : $value;   
        }

        return $value;
    }

    /**
     * Get the collection item ID from the URL.
     *
     * @return int|null The collection item ID or null if not found.
     *
     * @since 5.5.0
     */
    public static function getCollectionItemIdFromUrl()
    {
        $input = Factory::getApplication()->input;
        $itemIds = $input->get('collection_item_id', [], 'ARRAY');

        if (empty($itemIds)) {
            return null;
        }

        return (int) $itemIds[count($itemIds) - 1];
    }

    /**
     * Get the dynamic content item data from the database.
     *
     * @return array|null The dynamic content item data or null if not found.
     *
     * @since 5.5.0
     */
    public static function getDetailPageData()
    {
        $itemId = static::getCollectionItemIdFromUrl();

        if (empty($itemId)) {
            return null;
        }

        $service = new CollectionDataService();
        $item = $service->fetchCollectionItemById($itemId);

        return $item ?? null;
    }

    /**
     * Create a dynamic content link.
     *
     * @param object $link The link object.
     * @param array $item The item to create the link for.
     * @return string|null The link or null if not found.
     *
     * @since 5.5.0
     */
    public static function createDynamicContentLink($link, $item)
    {
        if (empty($link) || empty($item)) {
            return null;
        }

        $linkType = $link->type ?? null;

        if (empty($linkType)) {
            return null;
        }

        switch ($linkType) {
            case 'page':
                $pageId = $link->page ?? null;
                $page = !empty($pageId)
                    ? Page::where('id', $pageId)->first(['extension_view', 'view_id', 'id'])
                    : null;

                if (empty($page) || $page->isEmpty()) {
                    return null;
                }

                $itemId = static::getCurrentMenuItemId($page->view_id);

                if ($page->extension_view === 'dynamic_content:detail') {
                    $routeUrl = 'index.php?option=com_sppagebuilder&view=dynamic';

                    if (!empty($itemId)) {
                        $routeUrl .= '&Itemid=' . $itemId;
                    }

                    return Route::_(static::buildRouteWithCollectionItemId($routeUrl, $item['id']), false);
                }

                $routeUrl = 'index.php?option=com_sppagebuilder&view=page&id=' . $page->id;
                return Route::_($routeUrl, false);
            case 'url':
                return $link->url ?? null;
            case 'menu':
                return Route::_($link->menu, false);
            case 'popup': // Implement popup link later
            default:
                return null;
        }
    }

    /**
     * Generate the link attributes.
     *
     * @param array $linkOptions The link options.
     * @return array The link attributes.
     *
     * @since 5.5.0
     */
    public static function generateLinkAttributes($linkOptions)
    {
        $url = $linkOptions['url'] ?? null;
        $target = $linkOptions['target'] ?? null;
        $nofollow = $linkOptions['nofollow'] ?? null;
        $noreferrer = $linkOptions['noreferrer'] ?? null;
        $noopener = $linkOptions['noopener'] ?? null;

        $attributes = [
            'href' => '',
            'target' => '',
            'rel' => '',
            'has_link' => false,
        ];

        if (!empty($url)) {
            $attributes['href'] = $url;
            $attributes['has_link'] = true;
        }

        if (!empty($target)) {
            $attributes['target'] = $target;
        }

        $rel = [];

        if (!empty($nofollow)) {
            $rel[] = 'nofollow';
        }

        if (!empty($noreferrer)) {
            $rel[] = 'noreferrer'; 
        }

        if (!empty($noopener)) {
            $rel[] = 'noopener';
        }

        if (!empty($rel)) {
            $attributes['rel'] = implode(' ', $rel);
        }

        return $attributes;
    }

    /**
     * Format the date.
     *
     * @param string $date The date to format.
     * @param object $attribute The attribute to format the date for.
     * @return string|null The formatted date or null if not found.
     *
     * @since 5.5.0
     */
    public static function formatDate($date, $attribute)
    {
        $format = $attribute->date_format ?? 'd M, Y H:i A';
        if (empty($date) || empty($format)) {
            return $date;
        }

        if ($format === 'n-time-ago') {
            $date = Date::create($date);
            $now = Date::create('now');

            $interval = $now->diff($date);
            $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;

            if ($minutes === 0) {
                return Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_JUST_NOW');
            } elseif ($minutes < 60) {
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_MINUTES_AGO', $minutes);
            } elseif ($minutes < 1440) {
                $hours = floor($minutes / 60);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_HOURS_AGO', $hours);
            } elseif ($minutes < 10080) {
                $days = floor($minutes / 1440);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_DAYS_AGO', $days);
            } elseif ($minutes < 43200) {
                $weeks = floor($minutes / 10080);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_WEEKS_AGO', $weeks);
            } elseif ($minutes < 525600) {
                $months = floor($minutes / 43200);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_MONTHS_AGO', $months);
            } else {
                $years = floor($minutes / 525600);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_YEARS_AGO', $years);
            }
        }

        $format = $format === 'custom' ? $attribute->date_format_custom : $format;
        

        $date = new DateTime($date);
        return $date->format($format);
    }

    /**
     * Check if the field has a circular reference.
     * Circular reference means that the field is referencing itself.
     *
     * @param int $fieldId The field ID.
     * @return bool True if the field has a circular reference, false otherwise.
     *
     * @since 5.5.0
     */
    public static function hasCircularReference($fieldId)
    {
        $field = CollectionField::where('id', $fieldId)->first(['collection_id', 'reference_collection_id']);

        if ($field->isEmpty()) {
            return false;
        }

        return $field->collection_id === $field->reference_collection_id;
    }

    /**
     * Get the first collection item ID.
     *
     * @param integer $collectionId The collection ID.
     * @return int|null The first collection item ID or null if not found.
     *
     * @since 5.5.0
     */
    public static function getFirstCollectionItemId(int $collectionId)
    {
        $item = CollectionItem::where('collection_id', $collectionId)
            ->orderBy('id', 'ASC')
            ->first(['id']);

        if ($item->isEmpty()) {
            return null;
        }

        return $item->id;
    }

    /**
     * Prepare the image url for displaying.
     *
     * @param string $src The image source.
     * @return string|null The image URL or null if not found.
     *
     * @since 5.5.0
     */
    public static function getImageUrl($src)
    {
        if (empty($src)) {
            return null;
        }

        if (strpos($src, 'http') === 0) {
            return $src;
        }

        return Uri::root(true) . '/' . $src;
    }

    /**
     * Get the value by the path.
     *
     * @param int $itemId The item ID.
     * @param int $fieldId The field ID.
     * @return array|null The value or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getReferenceValueByPath($itemId, $fieldId)
    {
        $item = CollectionItemValue::where('item_id', $itemId)
            ->where('field_id', $fieldId)
            ->first(['value', 'reference_item_id']);

        if ($item->isEmpty()) {
            return null;
        }

        $item->id = $item->reference_item_id ?? null;
        return $item->toArray();
    }

    /**
     * Build the route with the collection item ID.
     *
     * @param string $url The URL to build the route for.
     * @param int $itemId The item ID to build the route for.
     * @return string|null The built route or null if not found.
     *
     * @since 5.5.0
     */
    protected static function buildRouteWithCollectionItemId($url, $itemId)
    {
        $currentRoute = Uri::getInstance($url);
        $app = Factory::getApplication();
        $input = $app->input;
        $itemIds = $input->get('collection_item_id', [], 'ARRAY');
        
        if (empty($itemIds)) {
            $itemIds = [];
            $itemIds[] = $itemId;
            $currentRoute->setVar('collection_item_id', $itemIds);
            return $currentRoute->toString();
        }

        $lastItemId = $itemIds[count($itemIds) - 1];
        $itemIdToPush = $itemId;

        $collectionIdOfLastItem = static::getCollectionIdOfCollectionItem($lastItemId);
        $collectionIdOfItemToPush = static::getCollectionIdOfCollectionItem($itemIdToPush);

        if ($collectionIdOfLastItem !== $collectionIdOfItemToPush) {
            $itemIds[] = $itemIdToPush;
        } else {
            $itemIds[count($itemIds) - 1] = $itemIdToPush;
        }

        $currentRoute->setVar('collection_item_id', $itemIds);
        return $currentRoute->toString();
    }

    /**
     * Get the collection ID of the collection item.
     *
     * @param int $itemId The item ID.
     * @return int|null The collection ID or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getCollectionIdOfCollectionItem($itemId)
    {
        $item = CollectionItem::where('id', $itemId)->first(['collection_id']);

        if ($item->isEmpty()) {
            return null;
        }

        return $item->collection_id;
    }

    /**
     * Get the current item ID from the URL.
     *
     * @param int $collectionId The collection ID.
     * @return int|null The current item ID or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getCurrentMenuItemId($collectionId)
    {
        $menuItems = Menu::whereLike('link', '%option=com_sppagebuilder%')
            ->where('client_id', 0)
            ->where('published', 1)
            ->get(['link', 'id']);

        if (empty($menuItems)) {
            return null;
        }

        $menuItems = Arr::make($menuItems);
        $menuItem = $menuItems->find(function ($item) use ($collectionId) {
            $query = Uri::getInstance($item->link);
            if ($query->getVar('view') !== 'page') {
                return false;
            }
            $pageId = intval($query->getVar('id') ?? 0);
            $pageCollectionId = static::getCollectionIdFromPageId($pageId);

            if (empty($pageCollectionId)) {
                return false;
            }

            return $pageCollectionId === (int) $collectionId;
        });

        if (empty($menuItem)) {
            /** @var CMSApplication */
            $app = Factory::getApplication();
            $input = $app->input;
            $itemId = $input->getInt('Itemid', 0);

            return $itemId ?: null;
        }

        return $menuItem->id;
    }

    /**
     * Get the collection ID from the page ID.
     *
     * @param int $pageId The page ID.
     * @return int|null The collection ID or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getCollectionIdFromPageId($pageId)
    {
        $page = Page::where('id', $pageId)->first(['extension_view', 'view_id', 'id']);

        if ($page->isEmpty()) {
            return null;
        }

        return $page->view_id ?? null;
    }
}
