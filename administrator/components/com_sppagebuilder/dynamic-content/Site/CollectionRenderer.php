<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use AddonParser;
use FieldsHelper;
use JLoader;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;

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
     * Normalized collection limit from addon settings.
     *
     * @param object $settings Addon settings
     * @return int
     */
    protected function getCollectionLimit($settings)
    {
        $limit = (int) ($settings->limit ?? 20);

        return $limit > 0 ? $limit : 20;
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

    public function collectPaths($nodes)
    {
        $paths = [];

        foreach ($nodes as $node) {
            $rawPath = $node->settings->attribute->path ?? null;

            if (!empty($rawPath)) {
                $paths[] = $rawPath;
            }

            if (!empty($node->child_nodes)) {
                $childPaths = $this->collectPaths($node->child_nodes);
                $paths = array_merge($paths, $childPaths);
            }
        }

        return $paths;
    }

    /**
     * Fetch items with proper data structure
     *
     * @param int $limit The maximum number of items to fetch
     * @param string $direction The ordering direction (ASC/DESC)
     * @param int $offset The offset for pagination
     * @return array Array of items
     * 
     * @since 6.0.0
     */
    public function fetchArticleItems($limit, $direction, $offset = 0)
    {
        try {
            $app = Factory::getApplication();
            $input = $app->input;
            $option = $input->get('option', 'com_content', 'string');
            $view = $input->get('view', '', 'string');
            $catId = '';
            $state = 1;
            $featured = null;

            if ($option === 'com_content' && ($view === 'category' || $view === 'featured' || $view === 'archive')) {
                $catId = $input->get('id', '', 'string');
            }

            if ($view === 'featured') {
                $featured = 1;
            } else if ($view === 'archive') {
                $state = 2;
            }

            $db = Factory::getDbo();
            $authorised = \Joomla\CMS\Access\Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
            $nullDate = $db->quote($db->getNullDate());
            $nowDate = $db->quote(Factory::getDate()->toSql());
            $baseUrl = rtrim(Uri::root(), '/');

            $query = $db->getQuery(true);
            $query->select([
                'a.id', 'a.title', 'a.alias', 'a.introtext', 'a.fulltext',
                'a.catid', 'a.created', 'a.created_by', 'a.publish_up',
                'a.images', 'a.attribs', 'a.language', 'a.featured', 'a.hits',
                'b.title as category', 'b.alias as category_alias',
                'u.name as username', 'u.email as created_by_email',
                'CASE WHEN p.profile_value IS NOT NULL THEN CONCAT(' . $db->quote($baseUrl) . ', JSON_UNQUOTE(p.profile_value)) ELSE NULL END as profile_image'
            ])
            ->from($db->quoteName('#__content', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'b') . ' ON a.catid = b.id')
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = a.created_by')
            ->join('LEFT', $db->quoteName('#__user_profiles', 'p') . ' ON p.user_id = a.created_by AND p.profile_key = ' . $db->quote('profileimage.profile_image'))
            ->where('a.state = ' . $db->quote($state))
            ->where('a.access IN (' . implode(',', $authorised) . ')')
            ->where('b.extension = ' . $db->quote('com_content'))
            ->group('a.id');

            if (!empty($catId)) {
                $query->where('a.catid = ' . $db->quote($catId));
            }

            if ($featured !== null) {
                $query->where('a.featured = ' . $db->quote($featured));
            }

            $version = new Version();
            $JoomlaVersion = $version->getShortVersion();
            if ((float) $JoomlaVersion < 4) {
                $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
                $query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
            } else {
                $nowDate = Factory::getDate()->toSql();
                $query->extendWhere(
                    'AND',
                    [
                        $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . '=' . $nullDate,
                        $db->quoteName('a.publish_up') . ' <= :publishUp',
                    ],
                    'OR'
                )->extendWhere(
                    'AND',
                    [
                        $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . '=' . $nullDate,
                        $db->quoteName('a.publish_down') . ' >= :publishDown',
                    ],
                    'OR'
                )->bind([':publishUp', ':publishDown'], $nowDate);
            }

            if ($app->isClient('site') && $app->getLanguageFilter()) {
                $query->where('a.language IN (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
            }

            if ($direction === 'desc') {
                $query->order('a.publish_up DESC');
            } else {
                $query->order('a.publish_up ASC');
            }

            $query->setLimit($limit, $offset);
            $db->setQuery($query);
            $articles = $db->loadObjectList();

            if ((float) $JoomlaVersion >= 4) {
                JLoader::registerAlias('FieldsHelper', 'Joomla\Component\Fields\Administrator\Helper\FieldsHelper');
            } else {
                JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
            }

            return array_map(function ($article) use ($JoomlaVersion, $db) {
                $article->introtext = $article->introtext ?? '';
                $article->fulltext = $article->fulltext ?? '';
                $article->hits = $article->hits ?? 0;
                
                $custom_fields = FieldsHelper::getFields('com_content.article', $article);

                if (!\class_exists('SppagebuilderHelperArticles')) {
                    require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
                }
                \SppagebuilderHelperArticles::applyComContentCustomFieldValuesToItem($article, $custom_fields);
                
                $article->collection_id = CollectionIds::ARTICLES_COLLECTION_ID;
                $article->slug = $article->id . ':' . $article->alias;
                $article->catslug = $article->catid . ':' . $article->category_alias;
                
                if (version_compare($JoomlaVersion, '4.0.0', '>=')) {
                    $article->link = \Joomla\CMS\Router\Route::_(\Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($article->slug, $article->catid, $article->language));
                } else {
                    if (!class_exists('ContentHelperRoute')) {
                        require_once JPATH_SITE . '/components/com_content/helpers/route.php';
                    }
                    $article->link = \Joomla\CMS\Router\Route::_(\ContentHelperRoute::getArticleRoute($article->slug, $article->catid, $article->language));
                }

                $article->introtext = $this->replaceFieldShortcodes($article->introtext, $custom_fields);
                $article->fulltext = $this->replaceFieldShortcodes($article->fulltext, $custom_fields);

                $attribs = json_decode($article->attribs ?? '{}');
                $feature_img = '';
                if (isset($attribs->helix_ultimate_image) && $attribs->helix_ultimate_image) {
                    $feature_img = $attribs->helix_ultimate_image;
                } elseif (isset($attribs->spfeatured_image) && $attribs->spfeatured_image) {
                    $feature_img = $attribs->spfeatured_image;
                }

                if (!empty($feature_img)) {
                    $article->featured_image = $feature_img;
                    $img_baseurl = basename($feature_img);

                    $small = JPATH_ROOT . '/' . dirname($feature_img) . '/' . File::stripExt($img_baseurl) . '_small.' . File::getExt($img_baseurl);
                    if (file_exists($small)) {
                        $article->image_small = Uri::root(true) . '/' . dirname($feature_img) . '/' . File::stripExt($img_baseurl) . '_small.' . File::getExt($img_baseurl);
                    }

                    $thumbnail = JPATH_ROOT . '/' . dirname($feature_img) . '/' . File::stripExt($img_baseurl) . '_thumbnail.' . File::getExt($img_baseurl);
                    if (file_exists($thumbnail)) {
                        $article->image_thumbnail = Uri::root(true) . '/' . dirname($feature_img) . '/' . File::stripExt($img_baseurl) . '_thumbnail.' . File::getExt($img_baseurl);
                    } else {
                        $article->image_thumbnail = Uri::root(true) . '/' . $article->featured_image;
                    }

                    $medium = JPATH_ROOT . '/' . dirname($feature_img) . '/' . File::stripExt($img_baseurl) . '_medium.' . File::getExt($img_baseurl);
                    if (file_exists($medium)) {
                        $article->image_medium = Uri::root(true) . '/' . dirname($feature_img) . '/' . File::stripExt($img_baseurl) . '_medium.' . File::getExt($img_baseurl);
                    }

                    $large = JPATH_ROOT . '/' . dirname($feature_img) . '/' . File::stripExt($img_baseurl) . '_large.' . File::getExt($img_baseurl);
                    if (file_exists($large)) {
                        $article->image_large = Uri::root(true) . '/' . dirname($feature_img) . '/' . File::stripExt($img_baseurl) . '_large.' . File::getExt($img_baseurl);
                    }
                } else {
                    $article->featured_image = '';
                    $article->image_thumbnail = '';
                    $images = json_decode($article->images ?? '{}');
                    if (isset($images->image_intro) && $images->image_intro) {
                        if (strpos($images->image_intro, 'http://') !== false || strpos($images->image_intro, 'https://') !== false) {
                            $article->image_thumbnail = $images->image_intro;
                        } else {
                            $article->image_thumbnail = Uri::root(true) . '/' . $images->image_intro;
                        }
                    } elseif (isset($images->image_fulltext) && $images->image_fulltext) {
                        if (strpos($images->image_fulltext, 'http://') !== false || strpos($images->image_fulltext, 'https://') !== false) {
                            $article->image_thumbnail = $images->image_fulltext;
                        } else {
                            $article->image_thumbnail = Uri::root(true) . '/' . $images->image_fulltext;
                        }
                    } else {
                        $article->image_thumbnail = false;
                    }
                }

                $keysToAdd = [
                    'image_small',
                    'image_medium',
                    'image_large',
                    'image_intro',
                    'image_intro_alt',
                    'float_intro',
                    'image_intro_caption',
                    'image_fulltext',
                    'image_fulltext_alt',
                    'float_fulltext',
                    'image_fulltext_caption'
                ];
                
                foreach ($keysToAdd as $key) {
                    if (!isset($article->$key)) {
                        $article->$key = '';
                    }
                }

                if (isset($article->images)) {
                    $images = json_decode($article->images);
                    if (isset($images)) {
                        foreach ($images as $key => $value) {
                            $article->$key = $value;
                        }
                    }
                }

                $article->profile_image = $article->profile_image ?? '';
                if (empty($article->profile_image)) {
                    $enableGravatar = ComponentHelper::getParams('com_sppagebuilder')->get('enable_gravatar', 1);
                    if ($enableGravatar && !empty($article->created_by_email)) {
                        $hash = md5(strtolower(trim($article->created_by_email)));
                        $gravatarUrl = "https://www.gravatar.com/avatar/{$hash}?s=45&d=404";
                        $article->profile_image = $gravatarUrl;
                    }
                }

                $layoutQuery = $db->getQuery(true);
                $layoutQuery->select($db->quoteName(['content', 'text', 'css']));
                $layoutQuery->from($db->quoteName('#__sppagebuilder'));
                $layoutQuery->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'));
                $layoutQuery->where($db->quoteName('extension_view') . ' = ' . $db->quote('article'));
                $layoutQuery->where($db->quoteName('view_id') . ' = ' . (int) $article->id);
                $layoutQuery->where($db->quoteName('active') . ' = 1');
                $db->setQuery($layoutQuery);
                $layoutData = $db->loadObject();
                
                $article->layout = !empty($layoutData) ? $layoutData : null;

                return (array) $article;
            }, $articles);
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error fetching items for dynamic content: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get total count of articles matching filters
     *
     * @return int Total count of articles
     * 
     * @since 6.3.0
     */
    protected function getArticlesCount()
    {
        try {
            $app = Factory::getApplication();
            $input = $app->input;
            $option = $input->get('option', 'com_content', 'string');
            $view = $input->get('view', '', 'string');
            $catId = '';
            $state = 1;
            $featured = null;

            if ($option === 'com_content' && ($view === 'category' || $view === 'featured' || $view === 'archive')) {
                $catId = $input->get('id', '', 'string');
            }

            if ($view === 'featured') {
                $featured = 1;
            } else if ($view === 'archive') {
                $state = 2;
            }

            $db = Factory::getDbo();
            $authorised = \Joomla\CMS\Access\Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
            $nullDate = $db->quote($db->getNullDate());
            $nowDate = $db->quote(Factory::getDate()->toSql());

            $query = $db->getQuery(true);
            $query->select('COUNT(DISTINCT a.id)')
                ->from($db->quoteName('#__content', 'a'))
                ->join('LEFT', $db->quoteName('#__categories', 'b') . ' ON a.catid = b.id')
                ->where('a.state = ' . $db->quote($state))
                ->where('a.access IN (' . implode(',', $authorised) . ')')
                ->where('b.extension = ' . $db->quote('com_content'));

            if (!empty($catId)) {
                $query->where('a.catid = ' . $db->quote($catId));
            }

            if ($featured !== null) {
                $query->where('a.featured = ' . $db->quote($featured));
            }

            $version = new Version();
            $JoomlaVersion = $version->getShortVersion();
            if ((float) $JoomlaVersion < 4) {
                $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
                $query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
            } else {
                $nowDate = Factory::getDate()->toSql();
                $query->extendWhere(
                    'AND',
                    [
                        $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . '=' . $nullDate,
                        $db->quoteName('a.publish_up') . ' <= :publishUp',
                    ],
                    'OR'
                )->extendWhere(
                    'AND',
                    [
                        $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . '=' . $nullDate,
                        $db->quoteName('a.publish_down') . ' >= :publishDown',
                    ],
                    'OR'
                )->bind([':publishUp', ':publishDown'], $nowDate);
            }

            if ($app->isClient('site') && $app->getLanguageFilter()) {
                $query->where('a.language IN (' . $db->quote(Factory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')');
            }

            $db->setQuery($query);
            return (int) $db->loadResult();
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error getting articles count: ' . $e->getMessage(), 'error');
            return 0;
        }
    }

    private function replaceFieldShortcodes($text, $custom_fields) {
		$fieldMap = [];
		foreach ($custom_fields as $field) {
			if (isset($field->id)) {
				$fieldMap[$field->id] = (isset($field->value) && $field->value) ? $field->value : '';
			}
		}
        
		return preg_replace_callback('/\{field\s+(\d+)\}/', function($matches) use ($fieldMap) {
			$fieldId = $matches[1];
            $value = $fieldMap[$fieldId] ?? '';
			return isset($fieldMap[$fieldId]) ? $fieldMap[$fieldId] : '';
		}, $text);
	}

    /**
     * Fetch items with proper data structure
     *
     * @param int $limit The maximum number of items to fetch
     * @param string $direction The ordering direction (ASC/DESC)
     * @return array Array of items
     * 
     * @since 6.0.0
     */
    protected function fetchTagItems($limit, $direction)
    {
        try {

            $db = \Joomla\CMS\Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__tags')
                ->where('published = 1')
                ->order('title ' . $direction);
            $db->setQuery($query, 0, $limit);
            $tags = $db->loadObjectList();
            

            return array_map(function ($tag) {
                $tag->collection_id = CollectionIds::TAGS_COLLECTION_ID;
                $tag->title = $tag->title ?? '';
                $tag->alias = $tag->alias ?? '';
                $tag->description = $tag->description ?? '';
                return (array) $tag;
            }, $tags);
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error fetching items for dynamic content: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Fetch data based on source
     *
     * @param int $source The source type
     * @param int $limit The maximum number of items to fetch
     * @param string $direction The ordering direction (ASC/DESC)
     * @param int $offset The offset for pagination
     * @return array Array of items
     * 
     * @since 6.0.0
     */
    protected function fetchArticlesOrTagsData($source, $limit, $direction, $offset = 0)
    {
        if ($source === CollectionIds::TAGS_COLLECTION_ID) {
            return $this->fetchTagItems($limit, $direction);
        }
        

        return $this->fetchArticleItems($limit, $direction, $offset);
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
            $app = Factory::getApplication();
            $option = $app->input->get('option', '', 'string');
            $view = $app->input->get('view', '', 'string');
            if ($option === 'com_content' && ($view === 'category' || $view === 'archive' || $view === 'featured' || $view === 'article') && !empty($item['link'])) {
                $linkUrl = $item['link'];
            }
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
        $limit = $this->getCollectionLimit($addon->settings);
        $direction = $addon->settings->direction ?? 'ASC';
        [$referenceFilters, $regularFilters, $hasReferenceFilters] = CollectionData::partitionByReferenceFilters($addon->settings->filters);

        $collectionId = $addon->settings->source ?? null;
        $source = $addon->settings->source ?? -1;

        if ($source === CollectionIds::ARTICLES_COLLECTION_ID || $source === CollectionIds::TAGS_COLLECTION_ID) {
            $articlesCount = $this->getArticlesCount();
            $items = $this->fetchArticlesOrTagsData($source, $articlesCount, $direction);
            
            $filteredData = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setDirection($direction)
                ->applyArticleOrTagsFilter($source, $item, $addon->settings->filters)
                ->getData();
            
            return $filteredData;
        }

        $collectionFields = (new CollectionsService)->fetchCollectionFields($collectionId ?? -1);

        $allPaths = array_map(function ($item) {
            return CollectionItemsService::createFieldKey($item['path']);
        }, array_filter($collectionFields, function ($item) {
            return $item['type'] !== 'self';
        }));

        $path = $this->collectPaths($this->addon->child_nodes);

        if ($hasReferenceFilters) {
            $items = (new CollectionDataService)->getCollectionReferenceItemsOnDemand($item, $referenceFilters, $direction);

            // Apply the regular filters to the reference filtered data
            $newData = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setDirection($direction)
                ->applyFilters($regularFilters, $allPaths)
                ->applyUserFilters($allPaths)
                ->applyUserSearchFilters($collectionId, $path, $allPaths)
                ->getData();
        } else {
            $parentItem = CollectionHelper::getDetailPageData();
            $newData = (new CollectionData())
                ->setLimit($limit)
                ->setDirection($direction)
                ->setCurrentItemId($item['id'])
                ->loadDataBySource($addon->settings->source)
                ->setParentItem($parentItem ?? null)
                ->applyFilters($addon->settings->filters, $allPaths)
                ->applyUserFilters($allPaths)
                ->applyUserSearchFilters($collectionId, $path, $allPaths)
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
        $limit = $this->getCollectionLimit($settings);
        $direction = $settings->direction ?? 'ASC';
        $sortingColumn = (isset($settings->sorting_field_id) && $settings->sorting_field_id) ? $settings->sorting_field_id : null;
        $sortingColumn = $sortingColumn === 'default' ? null : $sortingColumn;
        $class = $settings->class ?? '';

        $collectionFields = (new CollectionsService)->fetchCollectionFields($collectionId ?? -1);

        $allPaths = array_map(function ($item) {
            return CollectionItemsService::createFieldKey($item['path']);
        }, array_filter($collectionFields, function ($item) {
            return $item['type'] !== 'self';
        }));

        $path = $this->collectPaths($this->addon->child_nodes);

        [$referenceFilters, $regularFilters, $hasReferenceFilters] = CollectionData::partitionByReferenceFilters($settings->filters);
        // If the addon has reference filter that means it is a detail page
        // So we need to get the data for the detail page
        if ($hasReferenceFilters) {
            $parentItem = CollectionHelper::getDetailPageData();
            $items = (new CollectionDataService)->getCollectionReferenceItemsOnDemand($parentItem, $referenceFilters, $direction);

            $data = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setSortingColumn($sortingColumn)
                ->setDirection($direction)
                ->applyFilters($regularFilters, $allPaths)
                ->applyUserFilters($allPaths)
                ->applyUserSearchFilters($collectionId, $path, $allPaths);
        } else {
            $parentItem = CollectionHelper::getDetailPageData();
            $data = (new CollectionData())
                ->setLimit($limit)
                ->setSortingColumn($sortingColumn)
                ->setDirection($direction)
                ->loadDataBySource($collectionId)
                ->setParentItem($parentItem ?? null)
                ->applyFilters($filters, $allPaths)
                ->applyUserFilters($allPaths)
                ->applyUserSearchFilters($collectionId, $path, $allPaths);
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
     * Render items for the collection addon.
     *
     * @return string The rendered content
     * 
     * @since 6.0.0
     */
    public function renderArticles()
    {
        $settings = $this->addon->settings;
        $limit = $this->getCollectionLimit($settings);
        $direction = $settings->direction ?? 'asc';
        $source = $settings->source ?? CollectionIds::ARTICLES_COLLECTION_ID;
        $filters = $settings->filters ?? null;
        $hasFilters = !empty($filters) && !empty($filters->conditions);
        $parentItem = null;

        if ($source === CollectionIds::ARTICLES_COLLECTION_ID) {
            if ($hasFilters) {
                $totalCount = $this->getArticlesCount();
                $allItems = $this->fetchArticlesOrTagsData($source, $totalCount, $direction, 0);
                
                $parentItem = CollectionHelper::getDetailPageDataFromArticles();
                $data = (new CollectionData())
                    ->setData($allItems)
                    ->applyArticleOrTagsFilter($source, $parentItem, $filters)
                    ->setLimit($limit)
                    ->setDirection($direction);
                
                $items = $data->getData();
                $totalCount = $data->getItemCount();
            } else {
                $totalCount = $this->getArticlesCount();
                $items = $this->fetchArticlesOrTagsData($source, $limit, $direction, 0);
            }
        } else if ($source === CollectionIds::TAGS_COLLECTION_ID) {
            $items = $this->fetchArticlesOrTagsData($source, $limit, $direction);
            $parentItem = CollectionHelper::getDetailPageDataFromArticles();
            $data = (new CollectionData())
                ->setData($items)
                ->applyArticleOrTagsFilter($source, $parentItem, $filters)
                ->setLimit($limit)
                ->setDirection($direction);
            
            $items = $data->getData();
            $totalCount = $data->getItemCount();
        }

        if (empty($items)) {
            return $this->renderNoRecordsMessage();
        }

        $data = (new CollectionData())
            ->setData($items)
            ->setLimit($limit)
            ->setDirection($direction)
            ->setTotalItems($totalCount);

        $this->setData($data);

        return $this->renderCollectionAddon($this->getDataArray(), $this->addon);
    }

    /**
     * Render no records message.
     *
     * @return string
     * 
     * @since 6.3.0
     */
    protected function renderNoRecordsMessage()
    {
        $settings = $this->addon->settings;
        $class = $settings->class ?? '';
        $id = 'sppb-dynamic-content-' . $this->addon->id;
        $message = $settings->no_records_message ?? Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS');
        $description = $settings->no_records_description ?? null;

        $output = '<div class="sppb-dynamic-content-collection ' . $class . '" id="' . $id . '">';
        $output .= '<div class="sppb-dynamic-content-no-records">';
        $output .= '<h4>' . $message . '</h4>';
        if ($description) {
            $output .= '<p>' . $description . '</p>';
        }
        $output .= '</div></div>';

        return $output;
    }

    /**
     * Render the pagination.
     *
     * @return string The rendered content
     * 
     * @since 6.3.0
     */
    public function renderPagination()
    {
        return $this->renderPaginationInternal(false);
    }

    /**
     * Render pagination for articles and tags.
     *
     * @return string The rendered content
     * 
     * @since 6.3.0
     */
    public function renderArticlesPagination()
    {
        $source = $this->addon->settings->source ?? CollectionIds::ARTICLES_COLLECTION_ID;
        $isArticlesOrTags = ($source === CollectionIds::ARTICLES_COLLECTION_ID || $source === CollectionIds::TAGS_COLLECTION_ID);
        return $this->renderPaginationInternal($isArticlesOrTags);
    }

    /**
     * Render pagination HTML.
     *
     * @param bool $useArticlesParent Whether to use articles parent item
     * @return string The rendered content
     * 
     * @since 6.3.0
     */
    protected function renderPaginationInternal($useArticlesParent = false)
    {
        if (empty($this->data) || !($this->addon->settings->pagination ?? false)) {
            return '';
        }

        $totalPages = $this->data->getTotalPages();
        if ($totalPages <= 1) {
            return '';
        }

        $settings = $this->addon->settings;
        $parentItem = $useArticlesParent 
            ? CollectionHelper::getDetailPageDataFromArticles() 
            : CollectionHelper::getDetailPageData();
        $buttonText = $settings->pagination_load_more_button_text ?? Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TYPE_LOAD_MORE');
        $buttonType = $settings->pagination_load_more_button_type ?? 'default';
        $paginationType = $settings->pagination_type ?? 'load-more';

        $parentItemJson = htmlspecialchars(json_encode($parentItem), ENT_QUOTES, 'UTF-8');

        $output = '<div class="sppb-dynamic-content-collection__pagination" data-parent-item="' . $parentItemJson . '">';
        $output .= '<input type="hidden" name="sppb-dc-pagination-type" value="' . $paginationType . '">';
        
        if ($paginationType === 'infinite-scroll') {
            $output .= '<div class="sppb-dynamic-content-collection__pagination-sentinel" data-total-pages="' . $totalPages . '">Loading...</div>';
        } elseif ($paginationType === 'numbered') {
            // 0 = show every page button (full range). Set only when the option is saved.
            $visiblePages = 0;
            if (
                isset($settings->pagination_numbered_visible_pages)
                && $settings->pagination_numbered_visible_pages !== ''
                && $settings->pagination_numbered_visible_pages !== null
            ) {
                $visiblePages = (int) $settings->pagination_numbered_visible_pages;
            }

            $showArrows = !empty($settings->pagination_numbered_show_arrows) ? '1' : '0';
            $showFirstLast = !empty($settings->pagination_numbered_show_first_last) ? '1' : '0';
            $showDots = !empty($settings->pagination_numbered_show_dots) ? '1' : '0';
            $paginationAria = htmlspecialchars(
                Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TYPE_NUMBERED'),
                ENT_QUOTES,
                'UTF-8'
            );
            $buttonTypeEsc = htmlspecialchars((string) $buttonType, ENT_QUOTES, 'UTF-8');
            $output .= '<nav class="sppb-dynamic-content-pagination-numbers"';
            $output .= ' data-sppb-numbered-pagination';
            $output .= ' data-total-pages="' . (int) $totalPages . '"';
            $output .= ' data-current-page="1"';
            $output .= ' data-visible-pages="' . (int) $visiblePages . '"';
            $output .= ' data-show-arrows="' . $showArrows . '"';
            $output .= ' data-show-first-last="' . $showFirstLast . '"';
            $output .= ' data-show-dots="' . $showDots . '"';
            $output .= ' data-button-type="' . $buttonTypeEsc . '"';
            $output .= ' aria-label="' . $paginationAria . '">';
            $output .= '</nav>';
        } else {
            $output .= '<button type="button" data-text="' . $buttonText . '" data-parent-item="' . $parentItemJson . '" data-sppb-load-more-button data-total-pages="' . $totalPages . '" class="sppb-btn btn-sm sppb-btn-' . $buttonType . '">' . $buttonText . '</button>';
        }
        
        $output .= '<input type="hidden" name="sppb-dynamic-addon-id" value="' . $this->addon->id . '">';
        
        $app = Factory::getApplication();
        $app->getDocument()->addScriptOptions('sppb-dc-addon-' . $this->addon->id, $this->addon);
        $app->getDocument()->addScriptOptions('sppb-root', Uri::root());
        $output .= '</div>';

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
