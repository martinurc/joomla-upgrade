<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class EventbookingHelperLegacy
{
	/**
	 * Download PDF Certificates
	 *
	 * @param   array      $rows
	 * @param   RADConfig  $config
	 */
	public static function downloadCertificates($rows, $config)
	{
		[$fileName, $filePath] = EventbookingHelper::callOverridableHelperMethod('Certificate', 'generateCertificates', [$rows, $config]);

		// Process download
		while (@ob_end_clean())
		{
		}
		self::processDownload($filePath, $fileName);
	}

	/**
	 * Generate and download invoice of given registration record
	 *
	 * @param   int  $id
	 */
	public static function downloadInvoice($id)
	{
		$db     = Factory::getDbo();
		$config = EventbookingHelper::getConfig();
		$row    = new EventbookingTableRegistrant($db);
		$row->load($id);

		if (Factory::getApplication()->isClient('administrator'))
		{
			EventbookingHelper::loadComponentLanguage($row->language, true);
		}

		$invoiceStorePath = JPATH_ROOT . '/media/com_eventbooking/invoices/';

		if ($row)
		{
			if (!$row->invoice_number)
			{
				$row->invoice_number = EventbookingHelper::callOverridableHelperMethod('Registration', 'getInvoiceNumber', [$row]);
				$row->store();
			}

			$invoiceNumber = EventbookingHelper::callOverridableHelperMethod('Helper', 'formatInvoiceNumber', [$row->invoice_number, $config, $row]);

			$invoicePath = EventbookingHelper::callOverridableHelperMethod('Helper', 'generateInvoicePDF', [$row]);

			if (!$invoicePath)
			{
				$invoicePath = $invoiceStorePath . $invoiceNumber . '.pdf';
			}

			$fileName = $invoiceNumber . '.pdf';
			while (@ob_end_clean())
			{
			}
			self::processDownload($invoicePath, $fileName);
		}
	}

	/**
	 * Process download a file
	 *
	 * @param   string  $file  : Full path to the file which will be downloaded
	 */
	public static function processDownload($filePath, $filename, $detectFilename = false)
	{
		$fsize    = @filesize($filePath);
		$mod_date = date('r', filemtime($filePath));
		$cont_dis = 'attachment';

		if ($detectFilename)
		{
			$pos = strpos($filename, '_');

			if ($pos !== false)
			{
				$filename = substr($filename, $pos + 1);
			}
		}

		$ext  = File::getExt($filename);
		$mime = self::getMimeType($ext);

		// required for IE, otherwise Content-disposition is ignored
		if (ini_get('zlib.output_compression'))
		{
			ini_set('zlib.output_compression', 'Off');
		}

		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Expires: 0');
		header('Content-Transfer-Encoding: binary');
		header(
			'Content-Disposition:' . $cont_dis . '; filename="' . $filename . '"; modification-date="' . $mod_date . '"; size=' . $fsize .
			';'
		); //RFC2183
		header('Content-Type: ' . $mime); // MIME type
		header('Content-Length: ' . $fsize);

		if (!ini_get('safe_mode'))
		{ // set_time_limit doesn't work in safe mode
			@set_time_limit(0);
		}

		self::readfile_chunked($filePath);
	}

	/**
	 * Read file
	 *
	 * @param   string  $filename
	 * @param           $retbytes
	 *
	 * @return bool
	 */
	public static function readfile_chunked($filename, $retbytes = true)
	{
		$chunksize = 1 * (1024 * 1024); // how many bytes per chunk
		$cnt       = 0;
		$handle    = fopen($filename, 'rb');

		if ($handle === false)
		{
			return false;
		}

		while (!feof($handle))
		{
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			@ob_flush();
			flush();
			if ($retbytes)
			{
				$cnt += strlen($buffer);
			}
		}

		$status = fclose($handle);

		if ($retbytes && $status)
		{
			return $cnt; // return num. bytes delivered like readfile() does.
		}

		return $status;
	}

	/**
	 * Get mimetype of a file
	 *
	 * @return string
	 */
	public static function getMimeType($ext)
	{
		$mimeExtensionMap = JPATH_ROOT . '/components/com_eventbooking/helper/mime.mapping.php';

		return $mimeExtensionMap[$ext] ?? 'application/octet-stream';
	}

	public static function getDeliciousButton($title, $link)
	{
		$img_url = Uri::root(true) . '/media/com_eventbooking/assets/images/socials/delicious.png';
		$alt     = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Delicious');

		return '<a href="http://del.icio.us/post?url=' . rawurlencode($link) . '&amp;title=' . rawurlencode($title) . '" title="' . $alt . '" target="blank" >
		<img src="' . $img_url . '" alt="' . $alt . '" />
		</a>';
	}

	public static function getDiggButton($title, $link)
	{
		$img_url = Uri::root(true) . '/media/com_eventbooking/assets/images/socials/digg.png';
		$alt     = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Digg');

		return '<a href="http://digg.com/submit?url=' . rawurlencode($link) . '&amp;title=' . rawurlencode($title) . '" title="' . $alt . '" target="blank" >
        <img src="' . $img_url . '" alt="' . $alt . '" />
        </a>';
	}

	public static function getFacebookButton($title, $link)
	{
		$img_url = Uri::root(true) . '/media/com_eventbooking/assets/images/socials/facebook.png';
		$alt     = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'FaceBook');

		return '<a href="http://www.facebook.com/sharer.php?u=' . rawurlencode($link) . '&amp;t=' . rawurlencode($title) . '" title="' . $alt . '" target="blank" >
        <img src="' . $img_url . '" alt="' . $alt . '" />
        </a>';
	}

	public static function getGoogleButton($title, $link)
	{
		$img_url = Uri::root(true) . '/media/com_eventbooking/assets/images/socials/google.png';
		$alt     = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Google Bookmarks');

		return '<a href="http://www.google.com/bookmarks/mark?op=edit&bkmk=' . rawurlencode($link) . '" title="' . $alt . '" target="blank" >
        <img src="' . $img_url . '" alt="' . $alt . '" />
        </a>';
	}

	public static function getStumbleuponButton($title, $link)
	{
		$img_url = Uri::root(true) . '/media/com_eventbooking/assets/images/socials/stumbleupon.png';
		$alt     = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Stumbleupon');

		return '<a href="http://www.stumbleupon.com/submit?url=' . rawurlencode($link) . '&amp;title=' . rawurlencode($title) . '" title="' . $alt . '" target="blank" >
        <img src="' . $img_url . '" alt="' . $alt . '" />
        </a>';
	}

	public static function getTechnoratiButton($title, $link)
	{
		$img_url = Uri::root(true) . '/media/com_eventbooking/assets/images/socials/technorati.png';
		$alt     = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Technorati');

		return '<a href="http://technorati.com/faves?add=' . rawurlencode($link) . '" title="' . $alt . '" target="blank" >
        <img src="' . $img_url . '" alt="' . $alt . '" />
        </a>';
	}

	public static function getTwitterButton($title, $link)
	{
		$img_url = Uri::root(true) . '/media/com_eventbooking/assets/images/socials/twitter.png';
		$alt     = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Twitter');

		return '<a href="http://twitter.com/?status=' . rawurlencode($title . ' ' . $link) . '" title="' . $alt . '" target="blank" >
        <img src="' . $img_url . '" alt="' . $alt . '" />
        </a>';
	}

	public static function getLinkedInButton($title, $link)
	{
		$img_url = Uri::root(true) . '/media/com_eventbooking/assets/images/socials/linkedin.png';
		$alt     = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'LinkedIn');

		return '<a href="http://www.linkedin.com/shareArticle?mini=true&amp;url=' . $link . '&amp;title=' . $title . '" title="' . $alt . '" target="_blank" ><img src="' . $img_url . '" alt="' . $alt . '" /></a>';
	}

	/**
	 * Get language use for re-captcha
	 *
	 * @return string
	 */
	public static function getRecaptchaLanguage()
	{
		$language  = Factory::getLanguage();
		$tag       = explode('-', $language->getTag());
		$tag       = $tag[0];
		$available = ['en', 'pt', 'fr', 'de', 'nl', 'ru', 'es', 'tr'];

		if (in_array($tag, $available))
		{
			return "lang : '" . $tag . "',";
		}
	}

	/**
	 * @return string
	 */
	public static function validateEngine()
	{
		$config     = self::getConfig();
		$dateFormat = $config->date_field_format ?: '%Y-%m-%d';
		$dateFormat = str_replace('%', '', $dateFormat);
		$dateNow    = HTMLHelper::_('date', Factory::getDate(), $dateFormat);

		//validate[required,custom[integer],min[-5]] text-input
		$validClass = [
			'',
			'validate[custom[integer]]',
			'validate[custom[number]]',
			'validate[custom[email]]',
			'validate[custom[url]]',
			'validate[custom[phone]]',
			"validate[custom[date],past[$dateNow]]",
			'validate[custom[ipv4]]',
			'validate[minSize[6]]',
			'validate[maxSize[12]]',
			'validate[custom[integer],min[-5]]',
			'validate[custom[integer],max[50]]',
		];

		return json_encode($validClass);
	}

	public static function getURL()
	{
		static $url;

		if (!$url)
		{
			$config = EventbookingHelper::getConfig();
			$url    = EventbookingHelper::getSiteUrl();

			if ($config->get('use_https'))
			{
				$url = str_replace('http://', 'https://', $url);
			}
		}

		return $url;
	}

	/**
	 * Check to see whether this users has permission to edit registrant
	 */
	public static function checkEditRegistrant($rowRegistrant)
	{
		if (!EventbookingHelperAcl::canEditRegistrant($rowRegistrant))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('NOT_AUTHORIZED'), 'error');
			$app->redirect(Uri::root(), 403);
		}
	}

	/**
	 * Check to see whether a users to access to registration history
	 * Enter description here
	 */
	public static function checkAccessHistory()
	{
		$user = Factory::getUser();

		if (!$user->id)
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('NOT_AUTHORIZED'), 'error');
			$app->redirect(Uri::root(), 403);
		}
	}

	/**
	 * Check to see whether the current user can
	 *
	 * @param   int  $eventId
	 */
	public static function checkEventAccess($eventId)
	{
		$user  = Factory::getUser();
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('`access`')
			->from('#__eb_events')
			->where('id=' . $eventId);
		$db->setQuery($query);
		$access = (int) $db->loadResult();

		if (!in_array($access, $user->getAuthorisedViewLevels()))
		{
			$app = Factory::getApplication();
			$app->enqueueMessage(Text::_('NOT_AUTHORIZED'), 'error');
			$app->redirect(Uri::root(), 403);
		}
	}

	/**
	 * Get color code of an event based on in category
	 *
	 * @param   int  $eventId
	 *
	 * @return array
	 */
	public static function getColorCodeOfEvent($eventId)
	{
		static $colors;

		if (!isset($colors[$eventId]))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('color_code')
				->from('#__eb_categories AS a')
				->innerJoin('#__eb_events AS b ON a.id = b.main_category_id')
				->where('b.id = ' . $eventId);
			$db->setQuery($query);
			$colors[$eventId] = $db->loadResult();
		}

		return $colors[$eventId];
	}

	/**
	 * Method to get main category of an event
	 *
	 * @param $eventId
	 *
	 * @return mixed
	 */
	public static function getEventMainCategory($eventId)
	{
		static $categories;

		if (!isset($categories[$eventId]))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName(['text_color', 'color_code']))
				->from('#__eb_categories AS a')
				->innerJoin('#__eb_events AS b ON a.id = b.main_category_id')
				->where('b.id = ' . $eventId);
			$db->setQuery($query);
			$categories[$eventId] = $db->loadObject();
		}

		return $categories[$eventId];
	}

	/**
	 * Parent category select list
	 *
	 * @param   object  $row
	 *
	 * @return string
	 */
	public static function parentCategories($row)
	{
		$db          = Factory::getDbo();
		$query       = $db->getQuery(true);
		$fieldSuffix = EventbookingHelper::getFieldSuffix();

		$query->select('id, parent AS parent_id')
			->select('name' . $fieldSuffix . ' AS title')
			->from('#__eb_categories');

		if ($row->id)
		{
			$query->where('id != ' . $row->id);
		}

		if (!$row->parent)
		{
			$row->parent = 0;
		}

		$db->setQuery($query);
		$rows     = $db->loadObjectList();
		$children = [];

		// first pass - collect children
		foreach ($rows as $v)
		{
			$pt            = $v->parent_id;
			$list          = $children[$pt] ?? [];
			$list[]        = $v;
			$children[$pt] = $list;
		}

		$list = HTMLHelper::_('menu.treerecurse', 0, '', [], $children, 9999, 0, 0);

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '0', Text::_('Top'));

		foreach ($list as $item)
		{
			$options[] = HTMLHelper::_('select.option', $item->id, '&nbsp;&nbsp;&nbsp;' . $item->treename);
		}

		return HTMLHelper::_(
			'select.genericlist',
			$options,
			'parent',
			[
				'option.text.toHtml' => false,
				'option.text'        => 'text',
				'option.value'       => 'value',
				'list.attr'          => ' class="inputbox" ',
				'list.select'        => $row->parent,
			]
		);
	}

	/**
	 * Get total registrants of the given event
	 *
	 * @param   int  $eventId
	 *
	 * @return int
	 */
	public static function getTotalRegistrants($eventId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('SUM(number_registrants) AS total_registrants')
			->from('#__eb_registrants')
			->where('event_id = ' . $eventId)
			->where('group_id = 0')
			->where('(published=1 OR (published = 0 AND payment_method LIKE "os_offline%"))');
		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	/**
	 * Get title of the given payment method
	 *
	 * @param   string  $methodName
	 *
	 * @return string
	 */
	public static function getPaymentMethodTitle($methodName)
	{
		static $titles;

		if (!isset($titles[$methodName]))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('title')
				->from('#__eb_payment_plugins')
				->where('name = ' . $db->quote($methodName));
			$db->setQuery($query);
			$methodTitle = $db->loadResult();

			if ($methodTitle)
			{
				$titles[$methodName] = $methodTitle;
			}
			else
			{
				$titles[$methodName] = $methodName;
			}
		}

		return $titles[$methodName];
	}
}
