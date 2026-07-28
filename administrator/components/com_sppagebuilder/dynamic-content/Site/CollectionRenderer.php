<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use AddonParser;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;

class CollectionRenderer
{
    /**
     * Store the collection data.
     *
     * @var CollectionData
     * @since 5.5.0
     */
    protected $data;

    /**
     * Store the layouts.
     *
     * @var object
     * @since 5.5.0
     */
    protected $layouts = [];

    /**
     * Store the page name.
     *
     * @var string
     * @since 5.5.0
     */
    protected $pageName = 'none';

    /**
     * Store the addon object.
     *
     * @var object
     * @since 5.5.0
     */
    protected $addon;

    /**
     * Store the filters.
     *
     * @var array
     * @since 5.5.0
     */
    protected $filters = [];

    /**
     * Store the CSS content.
     *
     * @var array
     * @since 5.5.0
     */
    protected static $cssContent = [];

    /**
     * Initialize the CollectionRenderer.
     *
     * @param object $addon The addon object.
     * @since 5.5.0
     */
    public function __construct($addon)
    {
        $this->addon = $addon;
        $layoutPath = JPATH_ROOT . '/components/com_sppagebuilder/layouts';
        $this->layouts = (object) [
            'row_start' => new FileLayout('row.start', $layoutPath),
            'row_end'   => new FileLayout('row.end', $layoutPath),
            'row_css'   => new FileLayout('row.css', $layoutPath),
            'column_start' => new FileLayout('column.start', $layoutPath),
            'column_end'   => new FileLayout('column.end', $layoutPath),
            'column_css'   => new FileLayout('column.css', $layoutPath),
            'addon_start' => new FileLayout('addon.start', $layoutPath),
            'addon_end'   => new FileLayout('addon.end', $layoutPath),
            'addon_css'   => new FileLayout('addon.css', $layoutPath),
        ];

        $this->pageName  = 'none';
    }

    /**
     * Set the data object
     *
     * @param CollectionData $data The data object
     * 
     * @since 5.5.0
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the data object
     *
     * @return CollectionData
     * 
     * @since 5.5.0
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the data array
     *
     * @return array The data array
     * 
     * @since 5.5.0
     */
    public function getDataArray()
    {
        return $this->data->getData();
    }

    /**
     * Render the collection addon
     *
     * @param array $data The data to be rendered
     * @param object $addon The addon object containing settings and filters
     * @param object $layouts The layouts object containing the layout files
     * @param string $pageName The name of the page
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function renderCollectionAddon($data, $addon)
    {
        $childNodes = isset($addon->child_nodes) ? $addon->child_nodes : [];
        $id = 'sppb-dynamic-content-' . $addon->id;
        $noRecordsMessage = $addon->settings->no_records_message ?? Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS');
        $noRecordsDescription = $addon->settings->no_records_description ?? null;
        $class = $addon->settings->class ?? '';

        if (empty($data)) {
            $output = '<div class="sppb-dynamic-content-collection '. $class . '" id="' . $id . '">';
            $output .= '<div class="sppb-dynamic-content-no-records">';
            $output .= '<h4>' . $noRecordsMessage . '</h4>';

            if ($noRecordsDescription) {
                $output .= '<p>' . $noRecordsDescription . '</p>';
            }

            $output .= '</div>';
            $output .= '</div>';
            return $output;
        }


        $output = '<div class="sppb-dynamic-content-collection '. $class .'" id="' . $id . '">';

        foreach ($data as $index => $item) {
            $output .= $this->renderCollectionItem($childNodes, $item, $index);
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Render the individual collection item
     *
     * @param array $childNodes The child nodes of the collection item
     * @param array $item The item to be rendered
     * @param int $index The index of the item
     * @return string The rendered collection item
     * 
     * @since 5.5.0
     */
    public function renderCollectionItem($childNodes, $item, $index)
    {
        $output = '<div class="sppb-dynamic-content-collection__item">';

        foreach ($childNodes as $childNode) {
            if (empty((int) $childNode->visibility)) {
                continue;
            }

            if (!AddonParser::checkAddonACL($childNode)) {
                continue;
            }

            if ($childNode->name === 'dynamic_content_collection') {
                $newData = $this->getChildCollectionData($childNode, $item);
                $output .= $this->renderChildCollectionAddon($newData, $childNode, $this->layouts);
            } elseif ($childNode->name === 'div') {
                // Convey the dynamic item to the child node
                $childNode->settings->dynamic_item = $item;
                $output .= AddonParser::getDivHTMLViewForDynamicContent(
                    $childNode,
                    $this->layouts,
                    $this->pageName,
                    function($collectionAddon) use ($item) {
                        $newData = $this->getChildCollectionData($collectionAddon, $item);
                        return $this->renderChildCollectionAddon($newData, $collectionAddon, $this->layouts);
                    },
                    $index
                );
            } else {
                // Convey the dynamic item to the child node
                $childNode->settings->dynamic_item = $item;
                $output .= $this->renderChildNodeAddon($childNode, $this->layouts, $this->pageName, $index);
            }
        }

        $link = $this->addon->settings->link ?? null;
        $linkUrl = CollectionHelper::createDynamicContentLink($link, $item);
        $hasLink = !empty($linkUrl);

        if ($hasLink) {
            $output .= '<a href="' . $linkUrl . '" class="sppb-dynamic-content-collection__item-link" data-instant data-preload-collection data-preload-url="' . $linkUrl . '"></a>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Get the data for the child collection (a collection addon inside a collection addon)
     * 
     * This method handles two scenarios:
     * 1. With reference filters: Filters data based on the parent item using either "match all" 
     *    or "match any" conditions. The filtered data is then further processed with regular filters.
     *    If a negative limit is provided, it slices the reference filtered data.
     * 
     * 2. Without reference filters: Loads data directly from the source with the specified limit,
     *    then applies regular filters only.
     *
     * @param object $addon The addon object containing settings and filters
     * @param array $item The parent collection item used for reference filtering
     * @return array The filtered collection data
     * 
     * @since 5.5.0
     */
    public function getChildCollectionData($addon, $item)
    {
        $limit = $addon->settings->limit ?? 20;
        $direction = $addon->settings->direction ?? 'ASC';
        [$referenceFilters, $regularFilters, $hasReferenceFilters] = CollectionData::partitionByReferenceFilters($addon->settings->filters);

        if ($hasReferenceFilters) {
            $items = (new CollectionDataService)->getCollectionReferenceItemsOnDemand($item, $referenceFilters, $direction);

            // Apply the regular filters to the reference filtered data
            $newData = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setDirection($direction)
                ->applyFilters($regularFilters)
                ->getData();
        } else {
            $newData = (new CollectionData())
                ->setLimit($limit)
                ->setDirection($direction)
                ->setCurrentItemId($item['id'])
                ->loadDataBySource($addon->settings->source)
                ->applyFilters($addon->settings->filters)
                ->getData();
        }

        return $newData;
    }

    /**
     * Render the content of the collection addon that placed inside a collection addon
     *
     * @param array     $data       The data to be rendered
     * @param object    $addon      The addon object containing settings and filters
     * @param object    $layouts    The layouts object containing the layout files
     * @param string    $pageName   The name of the page
     *
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function renderChildCollectionAddon($data, $addon, $layouts)
    {
        $output = $layouts->addon_start->render(array('addon' => $addon));
        $output .= $this->renderCollectionAddon($data, $addon);
        $output .= $layouts->addon_end->render(array('addon' => $addon));
        $css = CollectionHelper::generateDynamicContentCSS($addon, $layouts);

        foreach ($css as $key => $value) {
            static::$cssContent[$key] = $value;
        }

        return $output;
    }

    /**
     * Render the regular child addons. This addons will skip the child collection addons and div addons.
     *
     * @param object    $addon      The addon object containing settings and filters
     * @param object    $layouts    The layouts object containing the layout files
     * @param string    $pageName   The name of the page
     * @param int       $index      The index of the item
     *
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function renderChildNodeAddon($addon, $layouts, $pageName, $index)
    {
        return AddonParser::getAddonHTMLView($addon, $layouts, $pageName, false, [], $index, false);
    }

    /**
     * Render the collection addon.
     *
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function render()
    {
        $settings = $this->addon->settings;
        $collectionId = $settings->source ?? null;
        $filters = $settings->filters ?? null;
        $limit = $settings->limit ?? 20;
        $direction = $settings->direction ?? 'ASC';
        $class = $settings->class ?? '';

        [$referenceFilters, $regularFilters, $hasReferenceFilters] = CollectionData::partitionByReferenceFilters($settings->filters);
        // If the addon has reference filter that means it is a detail page
        // So we need to get the data for the detail page
        if ($hasReferenceFilters) {
            $parentItem = CollectionHelper::getDetailPageData();
            $items = (new CollectionDataService)->getCollectionReferenceItemsOnDemand($parentItem, $referenceFilters, $direction);

            $data = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setDirection($direction)
                ->applyFilters($regularFilters);
        } else {
            $data = (new CollectionData())
                ->setLimit($limit)
                ->setDirection($direction)
                ->loadDataBySource($collectionId)
                ->applyFilters($filters);
        }

        if (empty($data)) {
            $id = 'sppb-dynamic-content-' . $this->addon->id;
            $noRecordsMessage = $settings->no_records_message ?? Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS');
            $noRecordsDescription = $settings->no_records_description ?? null;
            $output = '<div class="sppb-dynamic-content-collection ' . $class . '" id="' . $id . '">';
            $output .= '<div class="sppb-dynamic-content-no-records">';
            $output .= '<h4>' . $noRecordsMessage . '</h4>';

            if ($noRecordsDescription) {
                $output .= '<p>' . $noRecordsDescription . '</p>';
            }

            $output .= '</div>';
            $output .= '</div>';
            return $output;
        }

        $this->setData($data);

        return $this->renderCollectionAddon($this->getDataArray(), $this->addon);
    }

    /**
     * Render the pagination.
     *
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function renderPagination()
    {
        $settings = $this->addon->settings;
        $isPaginationEnabled = $this->addon->settings->pagination ?? false;
        $page = 1;
        $numberOfPages = $this->data->getTotalPages();
        $output = '';

        if ($isPaginationEnabled) {

            $loadMoreButtonText = $settings->pagination_load_more_button_text ?? Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TYPE_LOAD_MORE');
            $loadMoreButtonType = $settings->pagination_load_more_button_type ?? 'dark';
            $paginationType = $settings->pagination_type ?? 'load-more';

            $output .= '<div class="sppb-dynamic-content-collection__pagination">';

            if ($page < $numberOfPages) {
                $output .= '<input type="hidden" name="sppb-dc-pagination-type" value="' . $paginationType . '">';
                if ($paginationType === 'infinite-scroll') {
                    $output .= '<div class="sppb-dynamic-content-collection__pagination-sentinel" data-total-pages="' . $numberOfPages . '">Loading...</div>';
                } else {
                    $output .= '<button type="button" data-text="' . $loadMoreButtonText . '" data-sppb-load-more-button data-total-pages="' . $numberOfPages . '" class="sppb-btn btn-sm sppb-btn-' . $loadMoreButtonType . '">' . $loadMoreButtonText . '</button>';
                }
                
                $output .= '<input type="hidden" name="sppb-dynamic-addon-id" value="' . $this->addon->id . '">';
                /** @var CMSApplication */
                $app = Factory::getApplication();
                $app->getDocument()->addScriptOptions("sppb-dc-addon-" . $this->addon->id, $this->addon);
                $app->getDocument()->addScriptOptions("sppb-root", Uri::root());
            }

            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Generate the CSS content.
     *
     * @return string The generated CSS content
     * 
     * @since 5.5.0
     */
    public function generateCSS()
    {
        if (!empty(static::$cssContent)) {
            return '<style type="text/css">' . implode(" ", array_values(static::$cssContent)) . '</style>';
        }
    }
}
