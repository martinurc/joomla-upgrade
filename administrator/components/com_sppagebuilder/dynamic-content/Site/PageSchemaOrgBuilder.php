<?php
/**
 * @package SP Page Builder
 * @subpackage  com_sppagebuilder
 *
 * Mirrors Joomla plg_system_schemaorg: stored payload, cleanupSchema, image URLs,
 * @graph Organization + WebSite + WebPage (with @id links), item isPartOf WebPage,
 * and Article-like defaults from the page when schema fields are blank.
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

/**
 * @since  5.x
 */
final class PageSchemaOrgBuilder
{
	/**
	 * @param   object               $pageData  SP Page Builder page row (id required for @id).
	 * @param   array<string,mixed>  $schema    Decoded attribs.schema (schemaType + type key).
	 * @param   Document             $document  Joomla document (WebPage name/description like core).
	 * @param   CMSApplication       $app
	 *
	 * @return  array<string,mixed>|null
	 */
	public static function build(object $pageData, array $schema, Document $document, CMSApplication $app): ?array
	{
		$schemaType = isset($schema['schemaType']) ? trim((string) $schema['schemaType']) : 'None';

		if ($schemaType === '' || strcasecmp($schemaType, 'none') === 0) {
			return null;
		}

		if (!isset($schema[$schemaType]) || !\is_array($schema[$schemaType])) {
			return null;
		}

		$itemSchema = json_decode(json_encode($schema[$schemaType]), true);

		if (!\is_array($itemSchema)) {
			return null;
		}

		if (!isset($itemSchema['@type']) || self::isEmptySchemaValue($itemSchema['@type'])) {
			$itemSchema['@type'] = $schemaType;
		}

		self::applyCreativeWorkDefaults($itemSchema, $schemaType, $pageData, $document, $app);

		$domain         = Uri::root();
		$ids            = self::schemaBaseIds($domain);
		$isArticleLike  = \in_array(strtolower($schemaType), ['article', 'blogposting'], true);
		$context        = 'com_sppagebuilder.page';
		$articleGraphId = $domain . '#/schema/' . str_replace('.', '/', $context) . '/' . (int) ($pageData->id ?? 0);

		self::ensureArticleSchemaOrgQuality($itemSchema, $schemaType, $app);

		// Same as Schemaorg plugin: relative top-level image → root + cleanImageUrl.
		if (!empty($itemSchema['image']) && \is_string($itemSchema['image'])) {
			$url = $itemSchema['image'];

			if (preg_match('#^(https?:)?//#i', $url) !== 1) {
				$itemSchema['image'] = self::rootImageUrl($url);
			}
		}

		if (!empty($itemSchema['thumbnailUrl']) && \is_string($itemSchema['thumbnailUrl'])) {
			$url = $itemSchema['thumbnailUrl'];

			if (preg_match('#^(https?:)?//#i', $url) !== 1) {
				$itemSchema['thumbnailUrl'] = self::rootImageUrl($url);
			}
		}

		if ($isArticleLike) {
			self::syncArticlePrimaryImageUrls($itemSchema);
		}

		$itemSchema['@id']      = $articleGraphId;
		$itemSchema['isPartOf'] = ['@id' => $ids['webPage']];

		$itemSchema = self::cleanupSchema($itemSchema);

		if ($itemSchema === []) {
			return null;
		}

		$siteName = trim((string) $app->get('sitename', ''));

		$organizationSchema = [
			'@type' => 'Organization',
			'@id'   => $ids['organization'],
			'url'   => $domain,
		];

		if ($siteName !== '') {
			$organizationSchema['name'] = $siteName;
		}

		$websiteSchema = [
			'@type'     => 'WebSite',
			'@id'       => $ids['website'],
			'url'       => $domain,
			'publisher' => ['@id' => $ids['organization']],
		];

		if ($siteName !== '') {
			$websiteSchema['name'] = $siteName;
		}

		$langTag = trim((string) ($pageData->language ?? ''));

		if ($langTag === '' || $langTag === '*') {
			$langTag = $app->getLanguage()->getTag();
		}

		$webPageSchema = [
			'@type'      => 'WebPage',
			'@id'        => $ids['webPage'],
			'url'        => htmlspecialchars(Uri::getInstance()->toString(), ENT_COMPAT, 'UTF-8'),
			'name'       => $document->getTitle(),
			'isPartOf'   => ['@id' => $ids['website']],
			'about'      => ['@id' => $ids['organization']],
			'inLanguage' => $langTag,
		];

		if ($isArticleLike) {
			$webPageSchema['mainEntity'] = ['@id' => $articleGraphId];
		}

		$metaDesc = trim(strip_tags((string) $document->getDescription()));

		if ($metaDesc !== '') {
			$webPageSchema['description'] = $document->getDescription();
		}

		$itemClean = self::stripEmptyJsonLdDeep($itemSchema);
		$orgClean  = self::stripEmptyJsonLdDeep($organizationSchema);
		$siteClean = self::stripEmptyJsonLdDeep($websiteSchema);
		$webClean  = self::stripEmptyJsonLdDeep($webPageSchema);

		if ($isArticleLike && $webClean !== null && \is_array($webClean)) {
			$schemaTitle = null;

			if (isset($itemClean['headline']) && \is_string($itemClean['headline']) && trim($itemClean['headline']) !== '') {
				$schemaTitle = $itemClean['headline'];
			} elseif (isset($itemClean['name']) && \is_string($itemClean['name']) && trim($itemClean['name']) !== '') {
				$schemaTitle = $itemClean['name'];
			}

			if ($schemaTitle !== null) {
				$webClean['name'] = $schemaTitle;
			}
		}

		if (
			$itemClean === null
			|| $itemClean === []
			|| !isset($itemClean['@type'])
			|| \count($itemClean) <= 1
		) {
			return null;
		}

		$graph = [$itemClean];

		if ($webClean !== null && $webClean !== [] && isset($webClean['@type'])) {
			\array_unshift($graph, $webClean);
		}

		if ($siteClean !== null && $siteClean !== [] && isset($siteClean['@type'])) {
			\array_unshift($graph, $siteClean);
		}

		if ($orgClean !== null && $orgClean !== [] && isset($orgClean['@type'])) {
			\array_unshift($graph, $orgClean);
		}

		return [
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		];
	}

	/**
	 * @return array{organization: string, website: string, webPage: string}
	 */
	private static function schemaBaseIds(string $domain): array
	{
		return [
			'organization' => $domain . '#/schema/Organization/base',
			'website'      => $domain . '#/schema/WebSite/base',
			'webPage'      => $domain . '#/schema/WebPage/base',
		];
	}

	private static function isEmptySchemaValue($value): bool
	{
		if ($value === null || $value === []) {
			return true;
		}

		if (\is_string($value)) {
			return trim($value) === '';
		}

		if (\is_array($value)) {
			foreach ($value as $v) {
				if (!self::isEmptySchemaValue($v)) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Schema tab did not save an author value (missing / null / empty array only — any other payload is treated as intentional).
	 *
	 * @param   array<string,mixed>  $item
	 */
	private static function schemaTabAuthorIsUnset(array $item): bool
	{
		if (!\array_key_exists('author', $item)) {
			return true;
		}

		$a = $item['author'];

		return $a === null || $a === [];
	}

	/**
	 * When schema form fields are blank, fill from the SP Page Builder page (same idea as com_content article JSON-LD).
	 *
	 * @param   array<string,mixed>  $item
	 */
	private static function applyCreativeWorkDefaults(
		array &$item,
		string $schemaType,
		object $pageData,
		Document $document,
		CMSApplication $app
	): void {
		$typeNorm      = strtolower($schemaType);
		$isArticleLike = \in_array($typeNorm, ['article', 'blogposting'], true);

		$pageTitle = trim((string) ($pageData->title ?? ''));

		if ($pageTitle === '') {
			$pageTitle = trim(strip_tags((string) $document->getTitle()));
		}

		if ($isArticleLike) {
			if (self::isEmptySchemaValue($item['description'] ?? null)) {
				$desc = trim(strip_tags((string) $document->getDescription()));

				if ($desc !== '') {
					$item['description'] = $document->getDescription();
				}
			}

			$langTag = trim((string) ($pageData->language ?? ''));

			if ($langTag === '' || $langTag === '*') {
				$langTag = $app->getLanguage()->getTag();
			}

			if (self::isEmptySchemaValue($item['inLanguage'] ?? null)) {
				$item['inLanguage'] = $langTag;
			}

			if (self::schemaTabAuthorIsUnset($item)) {
				$authorName = trim((string) ($pageData->author_name ?? ''));

				if ($authorName === '' && !empty($pageData->created_by)) {
					$authorName = self::loadUserDisplayName((int) $pageData->created_by);
				}

				if ($authorName !== '') {
					$item['author'] = [
						'@type' => 'Person',
						'name'  => $authorName,
					];
				}
			}

			$thumbEmpty = self::isEmptySchemaValue($item['thumbnailUrl'] ?? null);
			$imageEmpty = self::isEmptySchemaValue($item['image'] ?? null);

			if ($thumbEmpty && $imageEmpty) {
				$og = self::extractPageOgImagePath($pageData);

				if ($og !== null && $og !== '') {
					$item['thumbnailUrl'] = $og;
				}
			}

			if (!empty($pageData->catid) && self::isEmptySchemaValue($item['articleSection'] ?? null)) {
				$section = self::loadSppbCategoryTitle((int) $pageData->catid);

				if ($section !== '') {
					$item['articleSection'] = $section;
				}
			}

			$created = trim((string) ($pageData->created_on ?? ''));

			if ($created !== '') {
				if (self::isEmptySchemaValue($item['dateCreated'] ?? null)) {
					$item['dateCreated'] = $created;
				}

				if (self::isEmptySchemaValue($item['datePublished'] ?? null)) {
					$item['datePublished'] = $created;
				}
			}

			$modified = trim((string) ($pageData->modified_on ?? $pageData->modified ?? ''));

			if ($modified !== '' && self::isEmptySchemaValue($item['dateModified'] ?? null)) {
				$item['dateModified'] = $modified;
			}

			$hits = (int) ($pageData->hits ?? 0);

			if ($hits > 0 && self::isEmptySchemaValue($item['interactionStatistic'] ?? null)) {
				$item['interactionStatistic'] = [
					'@type'             => 'InteractionCounter',
					'interactionType'   => 'https://schema.org/ReadAction',
					'userInteractionCount' => $hits,
				];
			}

			self::finalizeArticleHeadlineAndName($item, $pageData, $document, $app);
		} elseif ($pageTitle !== '' && self::isEmptySchemaValue($item['name'] ?? null)) {
			$item['name'] = $pageTitle;
		}
	}

	private static function loadUserDisplayName(int $userId): string
	{
		if ($userId <= 0) {
			return '';
		}

		try {
			$container = Factory::getContainer();

			if ($container->has(UserFactoryInterface::class)) {
				$user = $container->get(UserFactoryInterface::class)->loadUserById($userId);

				return trim((string) ($user->name ?? ''));
			}
		} catch (\Throwable $e) {
		}

		try {
			$user = Factory::getUser($userId);

			return trim((string) ($user->name ?? ''));
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * @return string|null  Relative or absolute image path as stored on the page
	 */
	private static function extractPageOgImagePath(object $pageData): ?string
	{
		$og = $pageData->og_image ?? null;

		if ($og === null || $og === '') {
			return null;
		}

		if (\is_string($og) && strpos(trim($og), '{') === 0) {
			$decoded = json_decode($og);

			if (\is_object($decoded) && !empty($decoded->src)) {
				$og = (string) $decoded->src;
			} else {
				return null;
			}
		}

		if (\is_object($og) && isset($og->src)) {
			$og = (string) $og->src;
		}

		if (!\is_string($og)) {
			return null;
		}

		$og = trim($og);

		return $og === '' ? null : $og;
	}

	private static function loadSppbCategoryTitle(int $catId): string
	{
		if ($catId <= 0) {
			return '';
		}

		try {
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select($db->quoteName('title'))
				->from($db->quoteName('#__categories'))
				->where($db->quoteName('id') . ' = ' . $catId)
				->where($db->quoteName('extension') . ' = ' . $db->quote('com_sppagebuilder'))
				->where($db->quoteName('published') . ' = 1');
			$db->setQuery($query);

			return trim((string) $db->loadResult());
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * Article / BlogPosting: page Schema tab values win when set; only empty fields get fallbacks.
	 */
	private static function finalizeArticleHeadlineAndName(
		array &$item,
		object $pageData,
		Document $document,
		CMSApplication $app
	): void {
		$formH = trim((string) ($item['headline'] ?? ''));
		$formN = trim((string) ($item['name'] ?? ''));

		if ($formH !== '' && $formN !== '') {
			return;
		}

		if ($formH !== '') {
			$item['name'] = $formH;

			return;
		}

		if ($formN !== '') {
			$item['headline'] = $formN;

			return;
		}

		$canonical = self::canonicalArticleHeadingFromPage($pageData, $document, $app);

		if ($canonical !== '') {
			$item['headline'] = $canonical;
			$item['name']     = $canonical;
		}
	}

	/**
	 * Best-effort primary heading: longer of (page record title, document title with sitename stripped).
	 */
	private static function canonicalArticleHeadingFromPage(object $pageData, Document $document, CMSApplication $app): string
	{
		$fromRecord = trim((string) ($pageData->title ?? ''));
		$rawDoc     = trim(html_entity_decode(strip_tags((string) $document->getTitle()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		$fromDoc    = self::stripSitenameFromDocumentTitle($rawDoc, $app);

		if ($fromRecord === '') {
			return $fromDoc;
		}

		if ($fromDoc === '') {
			return $fromRecord;
		}

		return self::stringCharLength($fromRecord) >= self::stringCharLength($fromDoc) ? $fromRecord : $fromDoc;
	}

	private static function stripSitenameFromDocumentTitle(string $docTitle, CMSApplication $app): string
	{
		if ($docTitle === '') {
			return '';
		}

		$site = trim((string) $app->get('sitename', ''));

		if ($site === '') {
			return $docTitle;
		}

		$suffix = ' | ' . $site;
		$len    = self::stringCharLength($suffix);

		if ($len > 0 && self::stringEndsWithInsensitive($docTitle, $suffix)) {
			return trim(self::stringSubstring($docTitle, 0, self::stringCharLength($docTitle) - $len));
		}

		$prefix = $site . ' | ';

		if (self::stringStartsWithInsensitive($docTitle, $prefix)) {
			return trim(self::stringSubstringFrom($docTitle, self::stringCharLength($prefix)));
		}

		return $docTitle;
	}

	private static function stringCharLength(string $s): int
	{
		if (\function_exists('mb_strlen')) {
			return (int) mb_strlen($s, 'UTF-8');
		}

		return \strlen($s);
	}

	private static function stringSubstring(string $s, int $start, ?int $length): string
	{
		if (\function_exists('mb_substr')) {
			if ($length === null) {
				return mb_substr($s, $start);
			}

			return mb_substr($s, $start, $length);
		}

		return $length === null ? substr($s, $start) : substr($s, $start, $length);
	}

	private static function stringSubstringFrom(string $s, int $start): string
	{
		if (\function_exists('mb_substr')) {
			return mb_substr($s, $start);
		}

		return substr($s, $start);
	}

	private static function stringEndsWithInsensitive(string $haystack, string $suffix): bool
	{
		$h = self::stringCharLength($haystack);
		$s = self::stringCharLength($suffix);

		if ($s === 0 || $s > $h) {
			return false;
		}

		return strcasecmp(self::stringSubstring($haystack, $h - $s, $s), $suffix) === 0;
	}

	private static function stringStartsWithInsensitive(string $haystack, string $prefix): bool
	{
		$p = self::stringCharLength($prefix);

		if ($p === 0) {
			return true;
		}

		return strcasecmp(self::stringSubstring($haystack, 0, $p), $prefix) === 0;
	}

	/**
	 * ISO 8601 datetimes, CreativeWork.url, publisher + mainEntityOfPage (Schema.org / Google Article guidance).
	 *
	 * @param   array<string,mixed>  $item
	 */
	private static function ensureArticleSchemaOrgQuality(array &$item, string $schemaType, CMSApplication $app): void
	{
		if (!\in_array(strtolower($schemaType), ['article', 'blogposting'], true)) {
			return;
		}

		self::normalizeArticleDateTimeFields($item, $app);

		$item['url'] = htmlspecialchars(Uri::getInstance()->toString(), ENT_COMPAT, 'UTF-8');

		$ids = self::schemaBaseIds(Uri::root());

		$item['mainEntityOfPage'] = ['@id' => $ids['webPage']];
		$item['publisher']        = ['@id' => $ids['organization']];

		if (self::schemaTabAuthorIsUnset($item)) {
			$item['author'] = ['@id' => $ids['organization']];
		}
	}

	/**
	 * @param   array<string,mixed>  $item
	 */
	private static function normalizeArticleDateTimeFields(array &$item, CMSApplication $app): void
	{
		foreach (['dateCreated', 'datePublished', 'dateModified', 'uploadDate'] as $key) {
			if (!isset($item[$key]) || !\is_string($item[$key])) {
				continue;
			}

			$formatted = self::toSchemaOrgDateTime($item[$key], $app);

			if ($formatted !== null) {
				$item[$key] = $formatted;
			}
		}
	}

	private static function toSchemaOrgDateTime(string $raw, CMSApplication $app): ?string
	{
		$raw = trim($raw);

		if ($raw === '') {
			return null;
		}

		try {
			$date = Factory::getDate($raw, $app->get('offset'));

			return $date->format(\DateTime::ATOM);
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * Google recommends image on Article; mirror thumbnailUrl ↔ image when one side is missing.
	 *
	 * @param   array<string,mixed>  $item
	 */
	private static function syncArticlePrimaryImageUrls(array &$item): void
	{
		$img   = $item['image'] ?? null;
		$thumb = $item['thumbnailUrl'] ?? null;

		if (\is_string($img) && trim($img) !== '' && (!\is_string($thumb) || trim($thumb) === '')) {
			$item['thumbnailUrl'] = $img;
		} elseif (\is_string($thumb) && trim($thumb) !== '' && (!\is_string($img) || trim($img) === '')) {
			$item['image'] = $thumb;
		}
	}

	/**
	 * Port of Joomla\Plugin\System\Schemaorg\Extension\Schemaorg::cleanupSchema.
	 *
	 * @param   array<string,mixed>  $schema
	 *
	 * @return  array<string,mixed>
	 */
	private static function cleanupSchema(array $schema): array
	{
		$result = [];

		foreach ($schema as $key => $value) {
			if (\is_array($value)) {
				if (!empty($value['@type'])) {
					if ($value['@type'] === 'ImageObject') {
						if (!empty($value['url'])) {
							$value['url'] = self::prepareImage($value['url']);
						}

						if (empty($value['url'])) {
							$value = [];
						}
					} elseif ($value['@type'] === 'Date') {
						if (!empty($value['value'])) {
							$value['value'] = self::prepareDate($value['value']);
						}

						if (empty($value['value'])) {
							$value = [];
						}
					}

					// Go into the array (all @type nodes, same as Joomla Schemaorg::cleanupSchema).
					$value = self::cleanupSchema($value);

					if (empty($value) || \count($value) <= 1) {
						$value = null;
					}
				} elseif ($key === 'genericField') {
					foreach ($value as $field) {
						if (!\is_array($field)) {
							continue;
						}

						$title = trim((string) ($field['genericTitle'] ?? ''));

						if ($title === '') {
							continue;
						}

						$gv = $field['genericValue'] ?? null;

						if ($gv === null || $gv === '') {
							continue;
						}

						if (\is_string($gv) && trim($gv) === '') {
							continue;
						}

						$result[$title] = $gv;
					}

					continue;
				} else {
					$value = self::cleanupSchema($value);

					if ($value === []) {
						$value = null;
					}
				}
			}

			if ($value === null || $value === '' || (\is_string($value) && trim($value) === '')) {
				continue;
			}

			if (empty($value) && $value !== 0 && $value !== false) {
				continue;
			}

			$result[$key] = $value;
		}

		return $result;
	}

	/**
	 * Remove empty strings, empty lists, nulls; drop nodes that only contain @type (same hygiene as core JSON-LD output).
	 *
	 * @param   mixed  $value
	 *
	 * @return  mixed|null
	 */
	private static function stripEmptyJsonLdDeep($value)
	{
		if (\is_string($value)) {
			return trim($value) === '' ? null : $value;
		}

		if ($value === null) {
			return null;
		}

		if (!\is_array($value)) {
			return $value;
		}

		if ($value === []) {
			return null;
		}

		if (self::isListArray($value)) {
			$out = [];

			foreach ($value as $item) {
				$item = self::stripEmptyJsonLdDeep($item);

				if ($item === null || $item === [] || $item === '') {
					continue;
				}

				$out[] = $item;
			}

			return $out === [] ? null : $out;
		}

		$out = [];

		foreach ($value as $k => $v) {
			$v = self::stripEmptyJsonLdDeep($v);

			if ($v === null || $v === '' || $v === []) {
				continue;
			}

			if (\is_array($v) && isset($v['@type']) && \count($v) <= 1) {
				continue;
			}

			$out[$k] = $v;
		}

		return $out === [] ? null : $out;
	}

	/**
	 * @param   array<mixed>  $array
	 */
	private static function isListArray(array $array): bool
	{
		if ($array === []) {
			return true;
		}

		return \array_keys($array) === \range(0, \count($array) - 1);
	}

	/**
	 * @param   mixed  $image
	 *
	 * @return  mixed
	 */
	private static function prepareImage($image)
	{
		if (\is_array($image)) {
			if (\count($image) === 1 && isset($image['@id'])) {
				return $image;
			}

			$newImages = [];

			foreach ($image as $img) {
				$newImages[] = self::prepareImage($img);
			}

			return $newImages;
		}

		if (!\is_string($image) || $image === '') {
			return null;
		}

		try {
			$img = HTMLHelper::_('cleanImageUrl', $image);

			return $img->url ?? null;
		} catch (\Throwable $e) {
			return $image;
		}
	}

	/**
	 * @param   mixed  $date
	 *
	 * @return  mixed
	 */
	private static function prepareDate($date)
	{
		if (\is_array($date)) {
			if (\count($date) === 1 && isset($date['@id'])) {
				return $date;
			}

			$newDates = [];

			foreach ($date as $d) {
				$newDates[] = self::prepareDate($d);
			}

			return $newDates;
		}

		if (!\is_string($date) || $date === '') {
			return null;
		}

		try {
			return Factory::getDate($date)->setTime(0, 0)->format(\DateTime::ATOM);
		} catch (\Throwable $e) {
			return $date;
		}
	}

	private static function rootImageUrl(string $url): string
	{
		try {
			$img = HTMLHelper::_('cleanImageUrl', $url);

			return Uri::root() . ($img->url ?? ltrim($url, '/'));
		} catch (\Throwable $e) {
			return rtrim(Uri::root(), '/') . '/' . ltrim($url, '/');
		}
	}
}
