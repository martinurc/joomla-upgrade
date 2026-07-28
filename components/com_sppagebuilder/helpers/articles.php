<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/
//no direct access
defined ('_JEXEC') or die ('Restricted access');

use Joomla\CMS\Version;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\Utilities\ArrayHelper;

$version = new Version();
$JoomlaVersion = $version->getShortVersion();

if(version_compare($JoomlaVersion, '4.0.0', '<') && !class_exists('ContentHelperRoute')) {
	require_once (JPATH_SITE . '/components/com_content/helpers/route.php');
}

abstract class SppagebuilderHelperArticles
{
	/**
	 * Map Joomla com_fields types to SP Page Builder dynamic field types.
	 *
	 * @param string $joomlaType Raw type from #__fields.type
	 * @return string|null Dynamic type or null when unsupported
	 *
	 * @since 6.6.0
	 */
	public static function mapComContentCustomFieldTypeToDynamic($joomlaType)
	{
		static $map = [
			'text' => 'text',
			'textarea' => 'text',
			'url' => 'link',
			'editor' => 'rich-text',
			'media' => 'image',
			'calendar' => 'date-time',
			'radio' => 'text',
			'number' => 'number',
			'integer' => 'number',
		];

		$joomlaType = is_string($joomlaType) ? $joomlaType : '';

		return $map[$joomlaType] ?? null;
	}

	/**
	 * Published custom fields for articles (#__fields, context com_content.article).
	 *
	 * @return array
	 *
	 * @since 6.6.0
	 */
	public static function getPublishedArticleCustomFieldRows()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(['id', 'title', 'name', 'type']))
			->from($db->quoteName('#__fields'))
			->where($db->quoteName('context') . ' = ' . $db->quote('com_content.article'))
			->where($db->quoteName('state') . ' = 1')
			->order($db->quoteName('ordering') . ' ASC');

		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	/**
	 * Attach com_fields values to an article object using each field's machine name (name) as the property key.
	 *
	 * @param object $item Article row / object passed to FieldsHelper::getFields
	 * @param array|null $customFields Optional preloaded array from FieldsHelper::getFields
	 * @return void
	 *
	 * @since 6.6.0
	 */
	public static function applyComContentCustomFieldValuesToItem($item, $customFields = null)
	{
		if (!is_object($item)) {
			return;
		}

		if (!is_array($customFields)) {
			$customFields = self::loadComContentCustomFieldsForItem($item);
		}

		foreach ($customFields as $field) {
			if (empty($field->name)) {
				continue;
			}

			$joomlaType = isset($field->type) ? (string) $field->type : '';

			if (self::mapComContentCustomFieldTypeToDynamic($joomlaType) === null) {
				continue;
			}

			$raw = isset($field->value) ? $field->value : null;
			$item->{$field->name} = self::normalizeComContentCustomFieldStoredValue($raw, $joomlaType);
		}
	}

	/**
	 * @param object $item
	 * @return array
	 */
	private static function loadComContentCustomFieldsForItem($item)
	{
		$version = new Version();
		$JoomlaVersion = $version->getShortVersion();

		if ((float) $JoomlaVersion >= 4) {
			JLoader::registerAlias('FieldsHelper', 'Joomla\Component\Fields\Administrator\Helper\FieldsHelper');
		} else {
			JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
		}

		return FieldsHelper::getFields('com_content.article', $item);
	}

	/**
	 * @param mixed $raw
	 * @param string $joomlaType
	 * @return string
	 */
	private static function normalizeComContentCustomFieldStoredValue($raw, $joomlaType)
	{
		if ($raw === null) {
			return '';
		}

		if ($joomlaType === 'media') {
			$path = $raw;

			if (is_string($raw) && $raw !== '') {
				$decoded = json_decode($raw, true);

				if (is_array($decoded)) {
					if (!empty($decoded['imagefile'])) {
						$path = $decoded['imagefile'];
					} elseif (!empty($decoded['image'])) {
						$path = $decoded['image'];
					}
				}
			}

			if ($path === null || $path === '') {
				return '';
			}

			$path = (string) $path;

			if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
				return $path;
			}

			return Uri::root(true) . '/' . ltrim($path, '/');
		}

		return is_scalar($raw) ? (string) $raw : '';
	}

	public static function checkAuthorised($id) {
		if (empty($id)) {
			return false;
		}
		$authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__content'))
			->where($db->quoteName('id') . ' = ' . $id)
			->where($db->quoteName('state') . ' = ' . $db->quote(1))
			->where($db->quoteName('access') . ' IN (' . implode(',', $authorised) . ')');

		$db->setQuery($query);
		$article = $db->loadObject();
		if (empty($article)) {
			return false;
		}
		return true;
	}
	public static function getArticles( $count = 5, $ordering = 'latest', $catid = '', $include_subcategories = true, $post_format = '', $tagids = array(), $state = 1, $currentPage = 1) {

		$authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));

		$app = Factory::getApplication();
		$db = Factory::getDbo();
		$nullDate = $db->quote($db->getNullDate());
		$nowDate  = $db->quote(Factory::getDate()->toSql());

		$baseUrl = rtrim(\Joomla\CMS\Uri\Uri::root(), '/');

		$query = $db->getQuery(true);

		$query
		->select(['a.*', 'u.email as created_by_email', 'CASE WHEN p.profile_value IS NOT NULL THEN CONCAT(' . $db->quote($baseUrl) . ', JSON_UNQUOTE(p.profile_value)) ELSE NULL END as profile_image'])
		->from($db->quoteName('#__content', 'a'))
		->select($db->quoteName('b.alias', 'category_alias'))
		->select($db->quoteName('b.title', 'category'))
		->join('LEFT', $db->quoteName('#__categories', 'b') . ' ON (' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('b.id') . ')')
		->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by') . ' AND ' . $db->quoteName('a.created_by') . ' IS NOT NULL')
		->join('LEFT', $db->quoteName('#__user_profiles', 'p') . ' ON ' . $db->quoteName('p.user_id') . ' = ' . $db->quoteName('a.created_by') . ' AND ' . $db->quoteName('p.profile_key') . ' = ' . $db->quote('profileimage.profile_image'))
		->where($db->quoteName('b.extension') . ' = ' . $db->quote('com_content'))
		->group($db->quoteName('a.id'));

		if($post_format) {
			$query->where('('.$db->quoteName('a.attribs') . ' LIKE ' . $db->quote('%"post_format":"'. $post_format .'"%') . ' OR ' . $db->quoteName('a.attribs') . ' LIKE ' . $db->quote('%"helix_ultimate_article_format":"'. $post_format .'"%').')');
		}

		$query->where($db->quoteName('a.state') . ' = ' . $db->quote($state));

		// Category filter
		if (!is_array($catid))
		{
			$catid = [$catid];
		}

		if (!empty($catid))
		{
			
			$categories = self::getCategories($catid, $include_subcategories );
		
			$categories = array_filter(array_merge($categories, $catid));

			if (!empty($categories)) {
				$query->where($db->quoteName('a.catid')." IN (" . implode( ',', $categories ) . ")");
			}
		}

		// tags filter
		if (is_array($tagids) && count($tagids)) {
			$tagId = implode(',', ArrayHelper::toInteger($tagids));
			if ($tagId) {
				$subQuery = $db->getQuery(true)
					->select('DISTINCT content_item_id')
					->from($db->quoteName('#__contentitem_tag_map'))
					->where('tag_id IN (' . $tagId . ')')
					->where('type_alias = ' . $db->quote('com_content.article'));

				$query->innerJoin('(' . (string) $subQuery . ') AS tagmap ON tagmap.content_item_id = a.id');
			}
		}

		$version = new Version();
		$JoomlaVersion = $version->getShortVersion();
		
		// publishing
		if ((float) $JoomlaVersion < 4)
		{
			$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
			$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
		}
		else
		{
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

		// has order by
		if ($ordering == 'hits') {
			$query->order($db->quoteName('a.hits') . ' DESC');
		} elseif($ordering == 'featured') {
			$query->where($db->quoteName('a.featured') . ' = ' . $db->quote(1));
			$query->order($db->quoteName('a.publish_up') . ' DESC');
		} elseif($ordering == 'oldest') {
			$query->order($db->quoteName('a.publish_up') . ' ASC');
		} elseif($ordering == 'alphabet_asc') {
			$query->order($db->quoteName('a.title') . ' ASC');
		} elseif($ordering == 'alphabet_desc') {
			$query->order($db->quoteName('a.title') . ' DESC');
		} elseif($ordering == 'ordering_asc') {
			$query->order($db->quoteName('a.ordering') . ' ASC');
		} elseif($ordering == 'ordering_desc') {
			$query->order($db->quoteName('a.ordering') . ' DESC');
		} elseif($ordering == 'random') {
			$query->order($query->Rand());
		} else {
			$query->order($db->quoteName('a.publish_up') . ' DESC');
		}

		// Language filter
		if ($app->isClient('site') && $app->getLanguageFilter()) {
			$query->where('a.language IN (' . $db->Quote(Factory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')');
		}

		$start = ($currentPage - 1) * $count;

		// continue query
		$query->where($db->quoteName('a.access')." IN (" . implode( ',', $authorised ) . ")");
		$query->order($db->quoteName('a.created') . ' DESC');

		if ($currentPage === -1){
			$query->setLimit($count);
		} else {
			$query->setLimit($count, $start);
		}
		
		$db->setQuery($query);
		$items = $db->loadObjectList();

		

		$itemIds = [];
		foreach ($items as $itemEntry) {
			$itemIds[] = (int) $itemEntry->id;
		}

		$tagsMap = [];
		if (!empty($itemIds)) {
			$tagsHelper = new TagsHelper;
			$tagsMap = $tagsHelper->getMultipleItemTags('com_content.article', $itemIds, true);
		}

		foreach ($items as &$item) {
			
			$item->slug    	= $item->id . ':' . $item->alias;
			$item->catslug 	= $item->catid . ':' . $item->category_alias;
			$item->username = Factory::getUser($item->created_by)->name;
			$item->profile_image = $item->profile_image ?? '';
			$item->link 	= Route::_(version_compare($JoomlaVersion, '4.0.0', '>=') ? Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($item->slug, $item->catid, $item->language) : ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));
			self::applyComContentCustomFieldValuesToItem($item);
			$attribs 		= json_decode($item->attribs);

			$tagsHelper = new TagsHelper;
			$tagsHelper->itemTags = isset($tagsMap[$item->id]) ? $tagsMap[$item->id] : [];
			$item->tags = $tagsHelper;

			$feature_img = '';
			if (isset($attribs->helix_ultimate_image) && $attribs->helix_ultimate_image) {
				$feature_img = $attribs->helix_ultimate_image;
			} elseif (isset($attribs->spfeatured_image) && $attribs->spfeatured_image) {
				$feature_img = $attribs->spfeatured_image;
			}

			// Featured Image
			if(isset($feature_img) && $feature_img != NULL) {
				$item->featured_image = $featured_image = $feature_img;

				$img_baseurl = basename($featured_image);

				//Small
				$small = JPATH_ROOT . '/' . dirname($featured_image) . '/' . File::stripExt($img_baseurl) .  '_small.' . File::getExt($img_baseurl);
				if(file_exists($small)) {
					$item->image_small = Uri::root(true) . '/' . dirname($featured_image) . '/' . File::stripExt($img_baseurl) . '_small.' . File::getExt($img_baseurl);
				}

				//Thumb
				$thumbnail = JPATH_ROOT . '/' . dirname($featured_image) . '/' . File::stripExt($img_baseurl) .  '_thumbnail.' . File::getExt($img_baseurl);
				if(file_exists($thumbnail)) {
					$item->image_thumbnail = Uri::root(true) . '/' . dirname($featured_image) . '/' . File::stripExt($img_baseurl) . '_thumbnail.' . File::getExt($img_baseurl);
				} else {
					$item->image_thumbnail = Uri::root(true) . '/' . $item->featured_image;
				}

				//Medium
				$medium = JPATH_ROOT . '/' . dirname($featured_image) . '/' . File::stripExt($img_baseurl) .  '_medium.' . File::getExt($img_baseurl);
				if(file_exists($medium)) {
					$item->image_medium = Uri::root(true) . '/' . dirname($featured_image) . '/' . File::stripExt($img_baseurl) . '_medium.' . File::getExt($img_baseurl);
				}

				//Large
				$large = JPATH_ROOT . '/' . dirname($featured_image) . '/' . File::stripExt($img_baseurl) .  '_large.' . File::getExt($img_baseurl);
				if(file_exists($large)) {
					$item->image_large = Uri::root(true) . '/' . dirname($featured_image) . '/' . File::stripExt($img_baseurl) . '_large.' . File::getExt($img_baseurl);
				}
			} else {
				$images = json_decode($item->images);
				if(isset($images->image_intro) && $images->image_intro) {
					if(strpos($images->image_intro, "http://") !== false || strpos($images->image_intro, "https://") !== false){
						$item->image_thumbnail = $images->image_intro;
					} else {
						$item->image_thumbnail = Uri::root(true) . '/' . $images->image_intro;
					}
				} elseif (isset($images->image_fulltext) && $images->image_fulltext) {
					if(strpos($images->image_fulltext, "http://") !== false || strpos($images->image_fulltext, "https://") !== false){
						$item->image_thumbnail = $images->image_fulltext;
					} else {
						$item->image_thumbnail = Uri::root(true) . '/' . $images->image_fulltext;
					}
				} else {
					$item->image_thumbnail = false;
				}
			}


			// Post Format
			$item->post_format = 'standard';
			if(isset($attribs->helix_ultimate_article_format) && $attribs->helix_ultimate_article_format != '') {
				$item->post_format = $attribs->helix_ultimate_article_format;
			} elseif(isset($attribs->post_format) && $attribs->post_format != '') {
				$item->post_format = $attribs->post_format;
			}

			// Post Format Video
			if(isset($item->post_format) && $item->post_format == 'video') {
				
				$video_url = '';
				if (isset($attribs->helix_ultimate_video) && $attribs->helix_ultimate_video) {
					$video_url = $attribs->helix_ultimate_video;
				} elseif (isset($attribs->video) && $attribs->video) {
					$video_url = $attribs->video;
				}

				if(isset($video_url) && $video_url != NULL) {
					$video = parse_url($video_url);
					$video_src = '';
					switch($video['host']) {
						case 'youtu.be':
						$video_id 	= trim($video['path'],'/');
						$video_src 	= '//www.youtube.com/embed/' . $video_id;
						break;

						case 'www.youtube.com':
						case 'youtube.com':
						parse_str($video['query'], $query);
						$video_id 	= $query['v'];
						$video_src 	= '//www.youtube.com/embed/' . $video_id;
						break;

						case 'vimeo.com':
						case 'www.vimeo.com':
						$video_id 	= trim($video['path'],'/');
						$video_src 	= "//player.vimeo.com/video/" . $video_id;
					}

					$item->video_src = $video_src;
				} else {
					$item->video_src = '';
				}

				
			}

			// Post Format Audio
			if(isset($item->post_format) && $item->post_format == 'audio') {

				$audio_url = '';
				if (isset($attribs->helix_ultimate_audio) && $attribs->helix_ultimate_audio) {
					$audio_url = $attribs->helix_ultimate_audio;
				} elseif (isset($attribs->audio) && $attribs->audio) {
					$audio_url = $attribs->audio;
				}

				if(isset($audio_url) && $audio_url != NULL) {
					$item->audio_embed = $audio_url;
				} else {
					$item->audio_embed = '';
				}
			}

			// Post Format Quote
			if(isset($item->post_format) && $item->post_format == 'quote') {
				if(isset($attribs->quote_text) && $attribs->quote_text != NULL) {
					$item->quote_text = $attribs->quote_text;
				} else {
					$item->quote_text = '';
				}

				if(isset($attribs->quote_author) && $attribs->quote_author != NULL) {
					$item->quote_author = $attribs->quote_author;
				} else {
					$item->quote_author = '';
				}
			}

			// Post Format Status
			if(isset($item->post_format) && $item->post_format == 'status') {
				if(isset($attribs->post_status) && $attribs->post_status != NULL) {
					$item->post_status = $attribs->post_status;
				} else {
					$item->post_status = '';
				}
			}

			// Post Format Link
			if(isset($item->post_format) && $item->post_format == 'link') {
				if(isset($attribs->link_title) && $attribs->link_title != NULL) {
					$item->link_title = $attribs->link_title;
				} else {
					$item->link_title = '';
				}

				if(isset($attribs->link_url) && $attribs->link_url != NULL) {
					$item->link_url = $attribs->link_url;
				} else {
					$item->link_url = '';
				}
			}

			// Post Format Gallery
			if(isset($item->post_format) && $item->post_format == 'gallery') {

				$gallery_imgs = '';
				if (isset($attribs->helix_ultimate_gallery) && $attribs->helix_ultimate_gallery) {
					$gallery_imgs = $attribs->helix_ultimate_gallery;
				} elseif (isset($attribs->gallery) && $attribs->gallery) {
					$gallery_imgs = $attribs->gallery;
				}

				$item->imagegallery = new stdClass();
				$gallery_imgs;

				if(isset($gallery_imgs) && $gallery_imgs != NULL) {
					$gallery_img_decode = json_decode($gallery_imgs);
					$gallery_all_images = '';
					if (isset($gallery_img_decode->helix_ultimate_gallery_images) && $gallery_img_decode->helix_ultimate_gallery_images) {
						$gallery_all_images = $gallery_img_decode->helix_ultimate_gallery_images;
					} elseif (isset($gallery_img_decode->gallery_images) && $gallery_img_decode->gallery_images) {
						$gallery_all_images = $gallery_img_decode->gallery_images;
					}
					
					$gallery_images = array();
					if(isset($gallery_all_images) && is_array($gallery_all_images)){
						foreach ($gallery_all_images as $key=>$value) {
							$gallery_images[$key]['full'] = $value;
							$gallery_img_baseurl = basename($value);

							//Small
							$small = JPATH_ROOT . '/' . dirname($value) . '/' . File::stripExt($gallery_img_baseurl) .  '_small.' . File::getExt($gallery_img_baseurl);
							if(file_exists($small)) {
								$gallery_images[$key]['small'] = Uri::root(true) . '/' . dirname($value) . '/' . File::stripExt($gallery_img_baseurl) . '_small.' . File::getExt($gallery_img_baseurl);
							}

							//Thumbnail
							$thumbnail = JPATH_ROOT . '/' . dirname($value) . '/' . File::stripExt($gallery_img_baseurl) .  '_thumbnail.' . File::getExt($gallery_img_baseurl);
							if(file_exists($thumbnail)) {
								$gallery_images[$key]['thumbnail'] = Uri::root(true) . '/' . dirname($value) . '/' . File::stripExt($gallery_img_baseurl) . '_thumbnail.' . File::getExt($gallery_img_baseurl);
							}

							//Medium
							$medium = JPATH_ROOT . '/' . dirname($value) . '/' . File::stripExt($gallery_img_baseurl) .  '_medium.' . File::getExt($gallery_img_baseurl);
							if(file_exists($medium)) {
								$gallery_images[$key]['medium'] = Uri::root(true) . '/' . dirname($value) . '/' . File::stripExt($gallery_img_baseurl) . '_medium.' . File::getExt($gallery_img_baseurl);
							}

							//Large
							$large = JPATH_ROOT . '/' . dirname($value) . '/' . File::stripExt($gallery_img_baseurl) .  '_large.' . File::getExt($gallery_img_baseurl);
							if(file_exists($large)) {
								$gallery_images[$key]['large'] = Uri::root(true) . '/' . dirname($value) . '/' . File::stripExt($gallery_img_baseurl) . '_large.' . File::getExt($gallery_img_baseurl);
							}
						}
					}

					$item->imagegallery->images = $gallery_images;
				} else {
					$item->imagegallery->images = array();
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
				if (!isset($item->$key)) {
					$item->$key = '';
				}
			}

			if (isset($item->images)) {
				$images = json_decode($item->images);
				if (isset($images)) {
					foreach($images as $key => $value) {
						$item->$key = $value;
					}
				}
			}

			if (empty($item->profile_image)) {
				$enableGravatar = ComponentHelper::getParams('com_sppagebuilder')->get('enable_gravatar', 1);
				if ($enableGravatar && !empty($item->created_by_email)) {
					$gravatarUrl = self::getGravatarUrl($item->created_by_email, 45, '404');
					if ($gravatarUrl) {
						$item->profile_image = $gravatarUrl;
					}
				}
			}

			// Fetch layout data from SP Page Builder for this article
			$query = $db->getQuery(true);
			$query->select($db->quoteName(['content', 'text', 'css']));
			$query->from($db->quoteName('#__sppagebuilder'));
			$query->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'));
			$query->where($db->quoteName('extension_view') . ' = ' . $db->quote('article'));
			$query->where($db->quoteName('view_id') . ' = ' . (int) $item->id);
			$query->where($db->quoteName('active') . ' = 1');
			$db->setQuery($query);
			$layoutData = $db->loadObject();
			
			if (empty($layoutData)) {
				$item->layout = null;
				continue;
			}
			$item->layout = $layoutData;
		}

		return $items;
	}

	public static function getArticlesCount() {
		$app = Factory::getApplication();
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*)');
		$query->from($db->quoteName('#__content'));
		$db->setQuery($query);
		$count = $db->loadResult();
		return $count;
	}

	public static function getCategories($parent_id = [1], $include_subcategories = true, $child = false, $cats = array()) {

		$app = Factory::getApplication();
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
			->where($db->quoteName('published') . ' = ' . $db->quote(1))
			->where($db->quoteName('access')." IN (" . implode( ',', Factory::getUser()->getAuthorisedViewLevels() ) . ")")
			->where($db->quoteName('language')." IN (" . $db->Quote(Factory::getLanguage()->getTag()).", ".$db->Quote('*') . ")");

		if (!empty(array_filter($parent_id)))
		{
			$query->where($db->quoteName('parent_id')." IN (" . implode( ',', $parent_id ) . ")");
		}

		$query->order($db->quoteName('lft') . ' ASC');

		$db->setQuery($query);
		$rows = $db->loadObjectList();
		
		foreach ($rows as $row) {

			if($include_subcategories) {
				array_push($cats, $row->id);
				if (self::hasChildren($row->id)) {
					$cats = self::getCategories(array($row->id), $include_subcategories, true, $cats);
				}
			}
		}

		return $cats;
	}

	private static function hasChildren($parent_id = 1) {

		$app = Factory::getApplication();
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
			->where($db->quoteName('published') . ' = ' . $db->quote(1))
			->where($db->quoteName('access')." IN (" . implode( ',', Factory::getUser()->getAuthorisedViewLevels() ) . ")")
			->where($db->quoteName('language')." IN (" . $db->Quote(Factory::getLanguage()->getTag()).", ".$db->Quote('*') . ")")
			->where($db->quoteName('parent_id') . ' = ' . $db->quote($parent_id))
			->order($db->quoteName('created_time') . ' DESC');

		$db->setQuery($query);

		$childrens = $db->loadObjectList();



		if(is_array($childrens) && count($childrens)) {
			return true;
		}

		return false;
	}

	private static function getGravatarUrl($email, $size = 45, $default = '404') {
		if (empty($email)) {
			return false;
		}
		
		$hash = md5(strtolower(trim($email)));
		$url = "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
		
		return $url;
	}
}
