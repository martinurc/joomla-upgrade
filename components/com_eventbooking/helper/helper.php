<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Composer\Autoload\ClassLoader;
use Detection\MobileDetect;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\IpHelper;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class EventbookingHelper
{
	/**
	 * Return the current installed version
	 *
	 * @return string
	 */
	public static function getInstalledVersion()
	{
		return '4.9.4';
	}

	/**
	 * Helper method to determine if we are in Joomla 4
	 *
	 * @return bool
	 */
	public static function isJoomla4()
	{
		return version_compare(JVERSION, '4.0.0', '>=');
	}

	/**
	 * Method to check if Joomla version is greater than certain version number
	 *
	 * @param   string  $version
	 *
	 * @return bool
	 */
	public static function JoomlaVersionGreaterThan(string $version): bool
	{
		return version_compare(JVERSION, $version, '>=');
	}

	/**
	 * Helper method to determine if we are in Joomla 4
	 *
	 * @return bool
	 */
	public static function isJoomla5()
	{
		return version_compare(JVERSION, '4.4.99', '>');
	}

	/**
	 * Get lang to append to an URL
	 *
	 * @param   string  $language
	 *
	 * @return string
	 */
	public static function getLangLink($language = null)
	{
		if (Multilanguage::isEnabled())
		{
			$languages = LanguageHelper::getLanguages('lang_code');

			if (!$language || $language == '*')
			{
				$language = Factory::getLanguage()->getTag();
			}

			if (isset($languages[$language]))
			{
				return '&lang=' . $languages[$language]->sef;
			}
		}

		return '';
	}

	/**
	 * @param   int  $Itemid
	 *
	 * @return string
	 */
	public static function getItemIdLink($Itemid = 0): string
	{
		if ($Itemid > 0)
		{
			return '&Itemid=' . $Itemid;
		}

		return '';
	}

	/**
	 * Helper method to check if StipEasyImage is enabled and could be used in Events Booking
	 *
	 * @return bool
	 */
	public static function useStipEasyImage()
	{
		return file_exists(JPATH_LIBRARIES . '/easylib/vendor/autoload.php') && PluginHelper::isEnabled('content', 'ebstipeasyimage');
	}

	/**
	 * Helper method to print debug backtrace, use for debugging purpose when it's needed
	 *
	 * @return void
	 */
	public static function printDebugBackTrace()
	{
		$traces = debug_backtrace();

		foreach ($traces as $trace)
		{
			echo $trace['file'] . ':' . $trace['line'] . '<br />';
		}
	}

	/**
	 * Get root url of site (without path)
	 *
	 * @return bool|string
	 */
	public static function getRootUrl()
	{
		$rootUrl = rtrim(Uri::root(), '/');
		$path    = Uri::root(true);

		if (!empty($path) && ($path != '/'))
		{
			$rootUrl = substr($rootUrl, 0, -1 * strlen($path));
		}

		return $rootUrl;
	}

	/**
	 * Method to make sure $value is a string
	 *
	 * @param   mixed  $value
	 *
	 * @return string
	 */
	public static function ensureValueIsString($value)
	{
		if (!is_scalar($value))
		{
			return '';
		}

		return $value ?? '';
	}

	/**
	 * Replaces tags from subject with it's value
	 *
	 * @param   string  $subject
	 * @param   array   $replaces
	 *
	 * @return string
	 */
	public static function replaceCaseInsensitiveTags($subject, $replaces)
	{
		foreach ($replaces as $key => $value)
		{
			$value = self::ensureValueIsString($value);

			$subject = str_ireplace("[$key]", $value, $subject);
		}

		return $subject;
	}

	/**
	 * Replaces tags from subject with it's value
	 *
	 * @param   string  $subject
	 * @param   array   $replaces
	 *
	 * @return string
	 */
	public static function replaceUpperCaseTags($subject, $replaces)
	{
		foreach ($replaces as $key => $value)
		{
			$key   = strtoupper($key);
			$value = self::ensureValueIsString($value);

			$subject = str_replace("[$key]", $value, $subject);
		}

		return $subject;
	}

	/**
	 * Get hased field name to store the time which form started to be rendered
	 *
	 * @return string
	 */
	public static function getHashedFieldName()
	{
		$app = Factory::getApplication();

		$siteName = $app->get('sitename');
		$secret   = $app->get('secret');

		return md5('EB' . $siteName . $secret);
	}

	/**
	 * Method to get next upcoming event of a given event
	 *
	 * @param   int  $id
	 *
	 * @return stdClass
	 */
	public static function getNextChildEvent($id)
	{
		$db          = Factory::getDbo();
		$currentDate = $db->quote(EventbookingHelper::getServerTimeFromGMTTime());
		$query       = $db->getQuery(true)
			->select('event_date, event_end_date')
			->from('#__eb_events')
			->where('parent_id = ' . $id)
			->where('event_date >= ' . $currentDate)
			->order('event_date');
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Method to get user time from GMT time
	 *
	 * @param   string  $time
	 * @param   string  $format
	 *
	 * @return string
	 */
	public static function getUserTimeFromGMTTime($time = 'now', $format = 'Y-m-d H:i:s')
	{
		$gmtTz  = new DateTimeZone('GMT');
		$userTz = new DateTimeZone(Factory::getUser()->getParam('timezone', \Factory::getApplication()->get('offset', 'GMT')));
		$date   = new DateTime($time, $gmtTz);
		$date->setTimezone($userTz);

		return $date->format($format);
	}

	/**
	 * Get list of event custom fields
	 *
	 * @return array
	 */
	public static function getEventCustomFields()
	{
		$xml = simplexml_load_file(JPATH_ROOT . '/components/com_eventbooking/fields.xml');

		if ($xml === false)
		{
			return [];
		}

		$fields = $xml->fields->fieldset->children();
		$names  = [];

		foreach ($fields as $field)
		{
			$names[] = (string) $field->attributes()->name;
		}

		return $names;
	}

	/**
	 * Method to check if the given date is null date or greater than the given date
	 *
	 * @param   string  $date
	 * @param   string  $compareDate
	 *
	 * @return bool
	 */
	public static function isNullOrGreaterThan($date, $compareDate = 'Now')
	{
		if (!(int) $date)
		{
			return true;
		}

		$firstDate  = Factory::getDate($date, Factory::getApplication()->get('offset'));
		$secondDate = Factory::getDate($compareDate, Factory::getApplication()->get('offset'));

		return $firstDate >= $secondDate;
	}

	/**
	 * Method to check if the given date is null date or smaller than the given date
	 *
	 * @param   string  $date
	 * @param   string  $compareDate
	 *
	 * @return bool
	 */
	public static function isNullOrSmallerThan($date, $compareDate = 'Now')
	{
		if (!(int) $date)
		{
			return true;
		}

		$firstDate  = Factory::getDate($date, Factory::getApplication()->get('offset'));
		$secondDate = Factory::getDate($compareDate, Factory::getApplication()->get('offset'));

		return $firstDate <= $secondDate;
	}

	/**
	 * Generate ICS for events for PHP 7.2
	 *
	 * @param   array   $rowEvents
	 * @param   string  $organizerEmail
	 * @param   string  $organizerName
	 * @param   string  $filename
	 */
	public static function generateIcs($rowEvents, $organizerEmail, $organizerName, $filename = null)
	{
		$timezone = Factory::getApplication()->get('offset');

		$calendar = Calendar::create()
			->productIdentifier('Events Booking');

		foreach ($rowEvents as $row)
		{
			$params = new Registry($row->params ?? '{}');

			if ($params->get('ics_content'))
			{
				$description = $params->get('ics_content');
			}
			else
			{
				$description = $row->short_description;
			}

			if (!empty($row->registrant_params))
			{
				$params = new Registry($row->registrant_params);

				if ($params->get('zoom_join_url'))
				{
					$description .= "\r\n " . Text::_('EB_ZOOM_LINK') . ': ' . $params->get('zoom_join_url');
				}
			}

			$event = Event::create($row->title)
				->description(str_replace("\r\n", "\r\n ", strip_tags($description)));

			$date = Factory::getDate($row->event_date, $timezone);

			$event->startsAt(new DateTime($date->format('Y-m-d H:i:s')));

			if ((int) $row->event_end_date)
			{
				$date = Factory::getDate($row->event_end_date, $timezone);

				$event->endsAt(new DateTime($date->format('Y-m-d H:i:s')));
			}

			if ((int) $row->created_date)
			{
				$date = Factory::getDate($row->created_date, $timezone);
				$event->createdAt(new DateTime($date->format('Y-m-d H:i:s')));
			}

			if ($row->location_address)
			{
				$event->address((string) $row->location_address)
					->addressName((string) $row->location_name);

				if ($row->lat != 0 || $row->long != 0)
				{
					$event->coordinates($row->lat, $row->long);
				}
			}

			$event->organizer($organizerEmail, $organizerName);

			$calendar->event($event);
		}

		if ($filename)
		{
			File::write($filename, $calendar->get());
		}
		else
		{
			return $calendar;
		}
	}

	/**
	 * Get duration
	 *
	 * @param   string  $duration
	 * @param   bool    $local  true: to local timezone, false: to UTC timezone
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	public static function getDateDuration($duration, $local = false)
	{
		$timezone = Factory::getApplication()->get('offset');

		switch ($duration)
		{
			case 'today':
				$date = Factory::getDate('now', $timezone);
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$date->setTime(23, 59, 59);
				$toDate = $date->toSql($local);
				break;
			case 'tomorrow':
				$date = Factory::getDate('tomorrow', $timezone);
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('tomorrow', $timezone);
				$date->setTime(23, 59, 59);
				$toDate = $date->toSql($local);
				break;
			case 'yesterday':
				$date = Factory::getDate('now', $timezone);
				$date->modify('-1 day');
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$date->setTime(23, 59, 59);
				$date->modify('-1 day');
				$toDate = $date->toSql($local);
				break;
			case 'this_week':
				$date   = Factory::getDate('now', $timezone);
				$monday = $date->modify('Monday this week');
				$monday->setTime(0, 0, 0);
				$fromDate = $monday->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$sunday   = $date->modify('Sunday this week');
				$sunday->setTime(23, 59, 59);
				$toDate = $sunday->toSql($local);
				break;
			case 'next_week':
				$date   = Factory::getDate('now', $timezone);
				$monday = $date->modify('Monday next week');
				$monday->setTime(0, 0, 0);
				$fromDate = $monday->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$sunday   = $date->modify('Sunday next week');
				$sunday->setTime(23, 59, 59);
				$toDate = $sunday->toSql($local);
				break;
			case 'last_week':
				$date   = Factory::getDate('now', $timezone);
				$monday = $date->modify('Monday last week');
				$monday->setTime(0, 0, 0);
				$fromDate = $monday->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$sunday   = $date->modify('Sunday last week');
				$sunday->setTime(23, 59, 59);
				$toDate = $sunday->toSql($local);
				break;
			case 'this_month':
				$date = Factory::getDate('now', $timezone);
				$date->setDate($date->year, $date->month, 1);
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$date->setDate($date->year, $date->month, $date->daysinmonth);
				$date->setTime(23, 59, 59);
				$toDate = $date->toSql($local);
				break;
			case 'next_month':
				$date = Factory::getDate('first day of next month', $timezone);
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('last day of next month', $timezone);
				$date->setTime(23, 59, 59);
				$toDate = $date->toSql($local);
				break;
			case 'last_month':
				$date = Factory::getDate('first day of last month', $timezone);
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('last day of last month', $timezone);
				$date->setTime(23, 59, 59);
				$toDate = $date->toSql($local);
				break;
			case 'this_year':
				// This year
				$date = Factory::getDate('now', $timezone);
				$date->setDate($date->year, 1, 1);
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$date->setDate($date->year, 12, 31);
				$date->setTime(23, 59, 59);
				$toDate = $date->toSql($local);
				break;
			case 'last_year':
				$date = Factory::getDate('now', $timezone);
				$date->setDate($date->year - 1, 1, 1);
				$date->setTime(0, 0, 0);
				$date->setTimezone(new DateTimeZone('UCT'));
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$date->setDate($date->year - 1, 12, 31);
				$date->setTime(23, 59, 59);
				$date->setTimezone(new DateTimeZone('UCT'));
				$toDate = $date->toSql($local);
				break;
			case 'last_7_days':
				$date = Factory::getDate('now', $timezone);
				$date->modify('-7 days');
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$date->setTime(23, 59, 59);
				$toDate = $date->toSql($local);
				break;
			case 'last_30_days':
				$date = Factory::getDate('now', $timezone);
				$date->setTime(0, 0, 0);
				$fromDate = $date->toSql($local);
				$date     = Factory::getDate('now', $timezone);
				$date->modify('-30 days');
				$date->setTime(23, 59, 59);
				$toDate = $date->toSql($local);
				break;
			default:
				$fromDate = '';
				$toDate   = '';
				break;
		}

		return [$fromDate, $toDate];
	}

	/**
	 * Method to resize the given image
	 *
	 * @param   string  $source
	 * @param   string  $destination
	 * @param   int     $width
	 * @param   int     $height
	 *
	 * @return void
	 */
	public static function resizeImage($source, $destination, $width, $height)
	{
		$config = EventbookingHelper::getConfig();

		$fileExt = StringHelper::strtoupper(File::getExt($source));

		// For image file types not supported by Joomla core like WEBP, we use a new library to handle
		if (!in_array($fileExt, ['PNG', 'GIF', 'JPG', 'JPEG']))
		{
			static::resizeImageUseNewLib($source, $destination, $width, $height);

			return;
		}

		$options = [];

		if ($fileExt == 'PNG')
		{
			$imageType = IMAGETYPE_PNG;

			if ($config->get('resized_png_image_quality', -1) != -1)
			{
				$options['quality'] = $config->get('resized_png_image_quality');
			}
		}
		elseif ($fileExt == 'GIF')
		{
			$imageType = IMAGETYPE_GIF;
		}
		elseif (in_array($fileExt, ['JPG', 'JPEG']))
		{
			$imageType = IMAGETYPE_JPEG;

			if ($config->get('resized_jpeg_image_quality', -1) != -1)
			{
				$options['quality'] = $config->get('resized_jpeg_image_quality');
			}
		}
		else
		{
			$imageType = '';
		}

		$image = new Image($source);

		if ($config->get('resize_image_method') == 'crop_resize')
		{
			$image->cropResize($width, $height, false)
				->toFile($destination, $imageType, $options);
		}
		else
		{
			$image->resize($width, $height, false)
				->toFile($destination, $imageType, $options);
		}
	}

	/**
	 * Method to resize the given image using new library
	 *
	 * @param   string  $source
	 * @param   string  $destination
	 * @param   int     $width
	 * @param   int     $height
	 *
	 * @return void
	 * @throws ImageResizeException
	 */
	public static function resizeImageUseNewLib($source, $destination, $width, $height)
	{
		/** @var ClassLoader $autoLoader */
		$autoLoader = include JPATH_LIBRARIES . '/vendor/autoload.php';
		$autoLoader->setPsr4('Gumlet\\', JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/imageresize/lib');

		$config = EventbookingHelper::getConfig();

		$image = new ImageResize($source);

		// Set quality for the image resizing
		if ($config->get('resized_jpeg_image_quality', -1) != -1)
		{
			$image->quality_jpg = (int) $config->get('resized_jpeg_image_quality');
		}

		if ($config->get('resized_png_image_quality', -1) != -1)
		{
			$image->quality_png = (int) $config->get('resized_png_image_quality');
		}

		if ($config->get('resized_webp_image_quality', -1) != -1)
		{
			$image->quality_png = (int) $config->get('resized_webp_image_quality');
		}

		if ($config->get('resize_image_method') == 'crop_resize')
		{
			$image->crop($width, $height)
				->save($destination);
		}
		else
		{
			$image->resizeToBestFit($width, $height)
				->save($destination);
		}
	}

	/**
	 * Execute queries from the given file
	 *
	 * @param   string  $file
	 */
	public static function executeSqlFile($file)
	{
		$db      = Factory::getDbo();
		$sql     = file_get_contents($file);
		$queries = $db->splitSql($sql);

		foreach ($queries as $query)
		{
			$query = trim($query);

			if ($query != '' && $query[0] != '#')
			{
				$db->setQuery($query)
					->execute();
			}
		}
	}

	/**
	 * Helper method to write data to a log file, for debuging purpose
	 *
	 * @param   string  $logFile
	 * @param   array   $data
	 * @param   string  $message
	 */
	public static function logData($logFile, $data = [], $message = null)
	{
		$text = '[' . gmdate('m/d/Y g:i A') . '] - ';

		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
				foreach ($value as $keyValue => $valueValue)
				{
					if (!is_scalar($valueValue))
					{
						continue;
					}

					$text .= "$keyValue=$valueValue, ";
				}
			}
			else
			{
				$text .= "$key=$value, ";
			}
		}

		$text .= $message;

		$fp = fopen($logFile, 'a');
		fwrite($fp, $text . "\n\n");
		fclose($fp);
	}

	/**
	 * Method to add current time to URL to prevent caching
	 *
	 * @return string
	 */
	public static function addTimeToUrl()
	{
		$config = EventbookingHelper::getConfig();

		if (PluginHelper::isEnabled('system', 'cache') || $config->prevent_cache)
		{
			return '&pt=' . time();
		}

		return '';
	}

	/**
	 * Method to get server time from GMT time
	 *
	 * @param   string  $time
	 * @param   string  $format
	 *
	 * @return string
	 */
	public static function getServerTimeFromGMTTime($time = 'now', $format = 'Y-m-d H:i:s')
	{
		$gmtTz  = new DateTimeZone('GMT');
		$userTz = new DateTimeZone(Factory::getApplication()->get('offset', 'GMT'));
		$date   = new DateTime($time, $gmtTz);
		$date->setTimezone($userTz);

		return $date->format($format);
	}

	/**
	 * Method to normalize null datedate data before passing to calendar form field
	 *
	 * @param   stdClass  $item
	 * @param   array     $fields
	 *
	 * @return void
	 */
	public static function normalizeNullDateTimeData($item, $fields = [])
	{
		foreach ($fields as $field)
		{
			if ((int) $item->{$field} === 0)
			{
				$item->{$field} = '';
			}
		}
	}

	/**
	 * Check if a method is overrided in a child class
	 *
	 * @param $class
	 * @param $method
	 *
	 * @return bool
	 */
	public static function isMethodOverridden($class, $method)
	{
		if (class_exists($class) && method_exists($class, $method))
		{
			$reflectionMethod = new ReflectionMethod($class, $method);

			if ($reflectionMethod->getDeclaringClass()->getName() == $class)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Method to call a static overridable helper method
	 *
	 * @param   string  $helper
	 * @param   string  $method
	 * @param   array   $methodArgs
	 * @param   string  $alternativeHelper
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public static function callOverridableHelperMethod($helper, $method, $methodArgs = [], $alternativeHelper = null)
	{
		$callableMethods = [];

		if (strtolower($helper) == 'helper')
		{
			$helperMethod = 'EventbookingHelper::' . $method;
		}
		else
		{
			$helperMethod = 'EventbookingHelper' . ucfirst($helper) . '::' . $method;
		}

		$callableMethods[] = $helperMethod;

		if ($alternativeHelper)
		{
			$callableMethods[] = 'EventbookingHelperOverride' . ucfirst($alternativeHelper) . '::' . $method;
		}

		$callableMethods[] = 'EventbookingHelperOverride' . ucfirst($helper) . '::' . $method;

		foreach (array_reverse($callableMethods) as $callable)
		{
			if (is_callable($callable))
			{
				return call_user_func_array($callable, $methodArgs);
			}
		}

		throw new Exception(sprintf('Method %s does not exist in the helper %s', $method, $helper));
	}

	/**
	 * Get configuration data and store in config object
	 *
	 * @return RADConfig
	 */
	public static function getConfig()
	{
		static $config;

		if ($config === null)
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/config/config.php';

			$config = new RADConfig('#__eb_configs');

			$direction = $config->get('events_dropdown_order_direction', 'ASC');

			if ($config->show_event_date)
			{
				$config->set('sort_events_dropdown', 'event_date ' . $direction . ', title');
			}
			else
			{
				$config->set('sort_events_dropdown', 'title');
			}

			// Make sure some important config data has value
			if (!$config->thumb_width)
			{
				$config->set('thumb_width', 200);
			}

			if (!$config->thumb_height)
			{
				$config->set('thumb_height', 200);
			}

			if (!$config->date_field_format)
			{
				$config->set('date_field_format', '%Y-%m-%d');
			}

			// For shopping cart, we set collect_member_information = collect_member_information_in_cart to avoid
			// having to modify code in different places
			if ($config->multiple_booking)
			{
				$config->collect_member_information = $config->collect_member_information_in_cart;
			}

			// Backward compatible purpose
			$config->show_coupon_code_in_registrant_list = $config->enable_coupon;
			$config->link_thumb_to_event_detail_page     = 1;
			$config->bypass_captcha_for_registered_user  = 0;

			// Support translating datetime formats
			if (Factory::getApplication()->isClient('site') && $fieldSuffix = EventbookingHelper::getFieldSuffix())
			{
				foreach (['date_format', 'event_date_format', 'event_time_format'] as $key)
				{
					if (strlen($config->get($key . $fieldSuffix, '')) > 0)
					{
						$config->set($key, $config->get($key . $fieldSuffix, ''));
					}
				}
			}
		}

		return $config;
	}

	/**
	 * Override global configuration by settings from event
	 *
	 * @param   RADConfig               $config
	 * @param   EventbookingTableEvent  $event
	 */
	public static function overrideGlobalConfig($config, $event)
	{
		$params = new Registry((string) $event->params);

		$config->user_registration = $params->get('user_registration', $config->user_registration);
	}

	/**
	 * Set event messages data from the messages configured inside category
	 *
	 * @param   EventbookingTableEvent     $event
	 * @param   EventbookingTableCategory  $category
	 * @param   array                      $keys
	 * @param   string                     $fieldSuffix
	 *
	 * @return void
	 */
	public static function setEventMessagesDataFromCategory($event, $category, $keys = [], $fieldSuffix = '')
	{
		// Set multilingual messages
		if ($fieldSuffix)
		{
			foreach ($keys as $key)
			{
				$key = $key . $fieldSuffix;

				if (!EventbookingHelper::isValidMessage($event->{$key}) && !empty($category->{$key}))
				{
					$event->{$key} = $category->{$key};
				}
			}
		}
		else
		{
			foreach ($keys as $key)
			{
				if (!EventbookingHelper::isValidMessage($event->{$key}) && !empty($category->{$key}))
				{
					$event->{$key} = $category->{$key};
				}
			}
		}
	}

	/**
	 * Set event string data from the string configured inside category
	 *
	 * @param   EventbookingTableEvent     $event
	 * @param   EventbookingTableCategory  $category
	 * @param   array                      $keys
	 * @param   string                     $fieldSuffix
	 *
	 * @return void
	 */
	public static function setEventStringsDataFromCategory($event, $category, $keys = [], $fieldSuffix = '')
	{
		// Set multilingual messages
		if ($fieldSuffix)
		{
			foreach ($keys as $key)
			{
				$key = $key . $fieldSuffix;

				if (!strlen(trim($event->{$key})) && !empty($category->{$key}))
				{
					$event->{$key} = $category->{$key};
				}
			}
		}
		else
		{
			foreach ($keys as $key)
			{
				if (!strlen(trim($event->{$key})) && !empty($category->{$key}))
				{
					$event->{$key} = $category->{$key};
				}
			}
		}
	}

	/**
	 * Get specify config value
	 *
	 * @param   string  $key
	 *
	 * @return string
	 */
	public static function getConfigValue($key, $default = null)
	{
		$config = self::getConfig();

		return $config->get($key, $default);
	}

	/**
	 *  Method to check to see whether a module is enabled
	 *
	 * @param   string  $module
	 *
	 * @return bool
	 */
	public static function isModuleEnabled($module)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__modules')
			->where('published = 1')
			->where('module = ' . $db->quote($module));
		$db->setQuery($query);

		return $db->loadResult() > 0;
	}

	/**
	 * Check to see whether the return value is a valid date format
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public static function isValidDate($value)
	{
		// basic date format yyyy-mm-dd
		$expr = '/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/D';

		return preg_match($expr, $value, $match) && checkdate($match[2], $match[3], $match[1]);
	}

	/**
	 * Get the device type (desktop, tablet, mobile) accessing the extension
	 *
	 * @return string
	 */
	public static function getDeviceType()
	{
		$session    = Factory::getApplication()->getSession();
		$deviceType = $session->get('eb_device_type');

		// If no data found from session, using mobile detect class to detect the device type
		if (!$deviceType)
		{
			$mobileDetect = new MobileDetect();
			$deviceType   = 'desktop';

			if ($mobileDetect->isMobile())
			{
				$deviceType = 'mobile';
			}

			if ($mobileDetect->isTablet())
			{
				$deviceType = 'tablet';
			}

			// Store the device type into session so that we don't have to find it for next request
			$session->set('eb_device_type', $deviceType);
		}

		return $deviceType;
	}

	/**
	 * Get default theme
	 *
	 * @return stdClass
	 */
	public static function getDefaultTheme()
	{
		static $theme;

		if ($theme === null)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from('#__eb_themes')
				->where('published = 1');
			$db->setQuery($query);
			$theme         = $db->loadObject();
			$theme->params = new Registry($theme->params);
		}

		return $theme;
	}

	/**
	 * Get page params of the given view
	 *
	 * @param $active
	 * @param $views
	 *
	 * @return Registry
	 */
	public static function getViewParams($active, $views)
	{
		if ($active && isset($active->query['view']) && in_array($active->query['view'], $views))
		{
			return $active->getParams();
		}

		return new Registry();
	}

	/**
	 * Apply some fixes for request data
	 *
	 * @return void
	 */
	public static function prepareRequestData()
	{
		//Remove cookie vars from request data
		$cookieVars = array_keys($_COOKIE);

		if (count($cookieVars))
		{
			foreach ($cookieVars as $key)
			{
				if (!isset($_POST[$key]) && !isset($_GET[$key]))
				{
					unset($_REQUEST[$key]);
				}
			}
		}

		if (isset($_REQUEST['start']) && !isset($_REQUEST['limitstart']))
		{
			$_REQUEST['limitstart'] = $_REQUEST['start'];
		}

		if (!isset($_REQUEST['limitstart']))
		{
			$_REQUEST['limitstart'] = 0;
		}

		// Fix PayPal IPN sending to wrong URL
		if (!empty($_POST['txn_type']) && empty($_REQUEST['task']) && empty($_REQUEST['view']))
		{
			$_REQUEST['task']           = 'payment_confirm';
			$_REQUEST['payment_method'] = 'os_paypal';
		}
	}

	/**
	 * Get the email messages used for sending emails or displaying in the form
	 *
	 * @return RADConfig
	 */
	public static function getMessages()
	{
		static $message;

		if (!$message)
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/config/config.php';

			$message = new RADConfig('#__eb_messages', 'message_key', 'message');
		}

		return $message;
	}

	/**
	 * Load component css to use it inside module
	 */
	public static function loadComponentCssForModules()
	{
		static $loaded = false;

		if ($loaded == true)
		{
			return;
		}

		if (Factory::getApplication()->input->getCmd('option') === 'com_eventbooking')
		{
			return;
		}

		$document      = Factory::getApplication()->getDocument();
		$config        = self::getConfig();
		$rootUrl       = Uri::root(true);
		$calendarTheme = $config->get('calendar_theme', 'default');

		// Load twitter bootstrap css
		if ($config->load_bootstrap_css_in_frontend && in_array($config->get('twitter_bootstrap_version', 2), [2, 5]))
		{
			if (EventbookingHelper::isJoomla4())
			{
				HTMLHelper::_('bootstrap.loadCss');
			}
			else
			{
				$document->addStyleSheet($rootUrl . '/media/com_eventbooking/assets/bootstrap/css/bootstrap.min.css');
			}
		}

		// Load font-awesome
		if ($config->get('load_font_awesome', '1'))
		{
			$document->addStyleSheet($rootUrl . '/media/com_eventbooking/assets/css/font-awesome.min.css');
		}

		// Load component css, module css can also be added here
		$document->addStyleSheet(
			$rootUrl . '/media/com_eventbooking/assets/css/style.min.css',
			['version' => EventbookingHelper::getInstalledVersion()]
		)
			->addStyleSheet(
				$rootUrl . '/media/com_eventbooking/assets/css/themes/' . $calendarTheme . '.css',
				['version' => EventbookingHelper::getInstalledVersion()]
			);

		$theme = EventbookingHelper::getDefaultTheme();

		// Call init script of theme to allow it to load it's own javascript + css files if needed
		if (file_exists(JPATH_ROOT . '/components/com_eventbooking/themes/' . $theme->name . '/init.php'))
		{
			require_once JPATH_ROOT . '/components/com_eventbooking/themes/' . $theme->name . '/init.php';
		}

		// Load custom css
		$customCssFile = JPATH_ROOT . '/media/com_eventbooking/assets/css/custom.css';

		if (file_exists($customCssFile) && filesize($customCssFile) > 0)
		{
			$document->addStyleSheet($rootUrl . '/media/com_eventbooking/assets/css/custom.css', ['version' => filemtime($customCssFile)]);
		}

		// Mark it as loaded to avoid the code from running again from second call
		$loaded = true;
	}

	/**
	 * Get field suffix used in sql query
	 *
	 * @param   null  $activeLanguage
	 *
	 * @return string
	 */
	public static function getFieldSuffix($activeLanguage = null)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideHelper', 'getFieldSuffix'))
		{
			return EventbookingHelperOverrideHelper::getFieldSuffix($activeLanguage);
		}

		$prefix = '';

		if ($activeLanguage !== '*' && Multilanguage::isEnabled())
		{
			if (!$activeLanguage)
			{
				$activeLanguage = Factory::getLanguage()->getTag();
			}

			if ($activeLanguage != self::getDefaultLanguage())
			{
				$languages = LanguageHelper::getLanguages('lang_code');

				if (isset($languages[$activeLanguage]))
				{
					$prefix = '_' . $languages[$activeLanguage]->sef;
				}
			}
		}

		return $prefix;
	}

	/**
	 * Get list of  none default languages uses on the site
	 *
	 * @return array
	 */
	public static function getLanguages()
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideHelper', 'getLanguages'))
		{
			return EventbookingHelperOverrideHelper::getLanguages();
		}

		$languages = LanguageHelper::getLanguages('lang_code');

		unset($languages[self::getDefaultLanguage()]);

		return array_values($languages);
	}

	/**
	 * Get front-end default language
	 *
	 * @return string
	 */
	public static function getDefaultLanguage()
	{
		$params = ComponentHelper::getParams('com_languages');

		return $params->get('site', 'en-GB');
	}

	/**
	 * Get sef of current language
	 *
	 * @return mixed
	 */
	public static function addLangLinkForAjax()
	{
		Factory::getApplication()->getDocument()->addScriptDeclaration(
			'var langLinkForAjax="' . self::getLangLink() . '";'
		);
	}

	/**
	 * This function is used to check to see whether we need to update the database to support multilingual or not
	 *
	 * @return boolean
	 */
	public static function isSynchronized()
	{
		$db             = Factory::getDbo();
		$fields         = array_keys($db->getTableColumns('#__eb_categories'));
		$extraLanguages = self::getLanguages();

		if (count($extraLanguages))
		{
			foreach ($extraLanguages as $extraLanguage)
			{
				$prefix = $extraLanguage->sef;

				if (!in_array('name_' . $prefix, $fields))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to get layout for add/edit event form
	 *
	 * @return []
	 */
	public static function getAddEditEventFormLayout()
	{
		$component = ComponentHelper::getComponent('com_eventbooking');
		$menus     = Factory::getApplication()->getMenu('site');
		$items     = $menus->getItems('component_id', $component->id);

		foreach ($items as $item)
		{
			if (isset($item->query['view']) && $item->query['view'] == 'event' && isset($item->query['layout']) && in_array(
					$item->query['layout'],
					['simple', 'form']
				))
			{
				return [$item->id, $item->query['layout']];
			}
		}

		$config = EventbookingHelper::getConfig();

		return [0, $config->get('submit_event_form_layout') ?: 'form'];
	}

	/**
	 * Convert payment amount to USD currency in case the currency is not supported by the payment gateway
	 *
	 * @param $amount
	 * @param $currency
	 *
	 * @return float
	 */
	public static function convertAmountToUSD($amount, $currency)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideHelper', 'convertAmountToUSD'))
		{
			return EventbookingHelperOverrideHelper::convertAmountToUSD($amount, $currency);
		}

		$session = Factory::getApplication()->getSession();

		if ($session->get('exchange_rate_' . $currency))
		{
			$rate = (float) $session->get('exchange_rate_' . $currency);
		}
		else
		{
			$config = EventbookingHelper::getConfig();
			$appId  = $config->get('open_exchange_rates_app_id') ?: 'ac4e04ddfd9a4e25a7476111b609181a';
			$url    = 'https://openexchangerates.org/api/latest.json?app_id=' . $appId . '&base=USD';

			$headers = [
				'User-Agent' => 'Events Booking ' . EventbookingHelper::getInstalledVersion(),
			];

			$http     = HttpFactory::getHttp();
			$response = $http->get($url, $headers);
			$rate     = (float) $config->get('exchange_rate', 1);

			if ($response->code == 200)
			{
				$jsonResponse = json_decode($response->body);

				if ($jsonResponse && isset($jsonResponse->rates->{$currency}))
				{
					$rate = (float) $jsonResponse->rates->{$currency};
				}
			}

			if ($rate <= 0)
			{
				$rate = 1;
			}

			$session->set('exchange_rate_' . $currency, $rate);
		}

		return round($amount / $rate, 2);
	}

	/**
	 * Convert payment amount to EUR currency in case the currency is not supported by the payment gateway
	 *
	 * @param $amount
	 * @param $currency
	 *
	 * @return float
	 */
	public static function convertAmountToEuro($amount, $currency)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideHelper', 'convertAmountToEuro'))
		{
			return EventbookingHelperOverrideHelper::convertAmountToEuro($amount, $currency);
		}

		$session = Factory::getApplication()->getSession();

		if ($session->get('eur_exchange_rate_' . $currency))
		{
			$rate = (float) $session->get('eur_exchange_rate_' . $currency);
		}
		else
		{
			$config = EventbookingHelper::getConfig();
			$url    = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

			$headers = [
				'User-Agent' => 'Events Booking ' . EventbookingHelper::getInstalledVersion(),
			];

			$http     = HttpFactory::getHttp();
			$response = $http->get($url, $headers);
			$rate     = (float) $config->get('eur_exchange_rate_', 1);

			if ($response->code == 200)
			{
				$element = new SimpleXMLElement($response->body);
				$element->registerXPathNamespace('xmlns', 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref');

				$elements = $element->xpath('//xmlns:Cube[@currency="' . $currency . '"]/@rate');

				if ($elements)
				{
					$rate = (float) $elements[0]['rate'];
				}
			}

			if ($rate <= 0)
			{
				$rate = 1;
			}

			$session->set('eur_exchange_rate_' . $currency, $rate);
		}

		return round($amount / $rate, 2);
	}

	/**
	 * Builds an exchange rate from the response content.
	 *
	 * @param   string  $content
	 *
	 * @return float
	 *
	 * @throws Exception
	 */
	protected static function buildExchangeRate($content)
	{
		$document = new DOMDocument();

		if (false === @$document->loadHTML('<?xml encoding="utf-8" ?>' . $content))
		{
			throw new Exception('The page content is not loadable');
		}

		$xpath = new DOMXPath($document);
		$nodes = $xpath->query('//span[@id="knowledge-currency__tgt-amount"]');

		if (1 !== $nodes->length)
		{
			$nodes = $xpath->query('//div[@class="vk_ans vk_bk" or @class="dDoNo vk_bk"]');
		}

		if (1 !== $nodes->length)
		{
			$nodes = $xpath->query('//div[@class="vk_ans vk_bk" or @class="dDoNo vk_bk gsrt"]');
		}

		if (1 !== $nodes->length)
		{
			throw new Exception('The currency is not supported or Google changed the response format');
		}

		$nodeContent = $nodes->item(0)->textContent;

		// Beware of "3 417.36111 Colombian pesos", with a non breaking space
		$bid = strtr($nodeContent, ["\xc2\xa0" => '']);

		if (false !== strpos($bid, ' '))
		{
			$bid = strstr($bid, ' ', true);
		}
		// Does it have thousands separator?
		if (strpos($bid, ',') && strpos($bid, '.'))
		{
			$bid = str_replace(',', '', $bid);
		}

		if (!is_numeric($bid))
		{
			throw new Exception('The currency is not supported or Google changed the response format');
		}

		return $bid;
	}

	/**
	 * Synchronize Events Booking database to support multilingual
	 */
	public static function setupMultilingual()
	{
		$db        = Factory::getDbo();
		$languages = self::getLanguages();
		$config    = EventbookingHelper::getConfig();

		if (count($languages))
		{
			$categoryTableFields   = array_keys($db->getTableColumns('#__eb_categories'));
			$eventTableFields      = array_keys($db->getTableColumns('#__eb_events'));
			$fieldTableFields      = array_keys($db->getTableColumns('#__eb_fields'));
			$locationTableFields   = array_keys($db->getTableColumns('#__eb_locations'));
			$ticketTypeTableFields = array_keys($db->getTableColumns('#__eb_ticket_types'));
			$agendaTableFields     = array_keys($db->getTableColumns('#__eb_agendas'));
			$speakerTableFields    = array_keys($db->getTableColumns('#__eb_speakers'));

			foreach ($languages as $language)
			{
				$prefix = $language->sef;

				$varcharFields = [
					'name',
					'alias',
					'page_title',
					'page_heading',
					'meta_keywords',
					'meta_description',
					'category_detail_url',
				];

				foreach ($varcharFields as $varcharField)
				{
					$fieldName = $varcharField . '_' . $prefix;

					if (!in_array($fieldName, $categoryTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` VARCHAR( 255 );";
						$db->setQuery($sql);
						$db->execute();
					}
				}

				if ($config->activate_simple_multilingual)
				{
					$textFields = [
						'description',
					];
				}
				else
				{
					$textFields = [
						'description',
						'admin_email_body',
						'user_email_body',
						'user_email_body_offline',
						'group_member_email_body',
						'thanks_message',
						'thanks_message_offline',
						'registration_approved_email_body',
					];
				}

				foreach ($textFields as $textField)
				{
					$fieldName = $textField . '_' . $prefix;

					if (!in_array($fieldName, $categoryTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_categories` ADD  `$fieldName` TEXT NULL;";
						$db->setQuery($sql);
						$db->execute();
					}
				}


				if ($config->activate_simple_multilingual)
				{
					$varcharFields = [
						'title',
						'alias',
						'meta_keywords',
						'meta_description',
						'price_text',
						'registration_handle_url',
					];
				}
				else
				{
					$varcharFields = [
						'title',
						'alias',
						'page_title',
						'page_heading',
						'meta_keywords',
						'meta_description',
						'price_text',
						'registration_handle_url',
					];
				}

				foreach ($varcharFields as $varcharField)
				{
					$fieldName = $varcharField . '_' . $prefix;

					if (!in_array($fieldName, $eventTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` VARCHAR( 255 );";
						$db->setQuery($sql);
						$db->execute();
					}
				}

				if ($config->activate_simple_multilingual)
				{
					$textFields = [
						'short_description',
						'description',
					];
				}
				else
				{
					$textFields = [
						'short_description',
						'description',
						'registration_form_message',
						'registration_form_message_group',
						'admin_email_body',
						'user_email_body',
						'user_email_body_offline',
						'group_member_email_body',
						'thanks_message',
						'thanks_message_offline',
						'registration_approved_email_body',
						'invoice_format',
						'ticket_layout',
					];
				}

				foreach ($textFields as $textField)
				{
					$fieldName = $textField . '_' . $prefix;

					if (!in_array($fieldName, $eventTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_events` ADD  `$fieldName` TEXT NULL;";
						$db->setQuery($sql);
						$db->execute();
					}
				}

				$fieldName = 'title_' . $prefix;

				if (!in_array($fieldName, $fieldTableFields))
				{
					$sql = "ALTER TABLE  `#__eb_fields` ADD  `$fieldName` VARCHAR( 255 );";
					$db->setQuery($sql);
					$db->execute();
				}

				$textFields = [
					'description',
					'values',
					'default_values',
					'depend_on_options',
					'place_holder',
				];

				foreach ($textFields as $textField)
				{
					$fieldName = $textField . '_' . $prefix;

					if (!in_array($fieldName, $fieldTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_fields` ADD  `$fieldName` TEXT NULL;";
						$db->setQuery($sql);
						$db->execute();
					}
				}

				$varcharFields = [
					'name',
					'alias',
				];

				foreach ($varcharFields as $varcharField)
				{
					$fieldName = $varcharField . '_' . $prefix;

					if (!in_array($fieldName, $locationTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_locations` ADD  `$fieldName` VARCHAR( 255 );";
						$db->setQuery($sql);
						$db->execute();
					}
				}

				$fieldName = 'description_' . $prefix;

				if (!in_array($fieldName, $locationTableFields))
				{
					$sql = "ALTER TABLE  `#__eb_locations` ADD  `$fieldName` TEXT NULL;";
					$db->setQuery($sql);
					$db->execute();
				}

				$fieldName = 'title_' . $prefix;

				if (!in_array($fieldName, $ticketTypeTableFields))
				{
					$sql = "ALTER TABLE  `#__eb_ticket_types` ADD  `$fieldName` VARCHAR( 255 ) NOT NULL DEFAULT '';";
					$db->setQuery($sql);
					$db->execute();
				}

				$fieldName = 'description_' . $prefix;

				if (!in_array($fieldName, $ticketTypeTableFields))
				{
					$sql = "ALTER TABLE  `#__eb_ticket_types` ADD  `$fieldName` TEXT NULL;";
					$db->setQuery($sql);
					$db->execute();
				}

				$fieldName = 'title_' . $prefix;

				if (!in_array($fieldName, $agendaTableFields))
				{
					$sql = "ALTER TABLE  `#__eb_agendas` ADD  `$fieldName` VARCHAR( 255 ) NOT NULL DEFAULT '';";
					$db->setQuery($sql);
					$db->execute();
				}

				$fieldName = 'description_' . $prefix;

				if (!in_array($fieldName, $agendaTableFields))
				{
					$sql = "ALTER TABLE  `#__eb_agendas` ADD  `$fieldName` TEXT NULL;";
					$db->setQuery($sql);
					$db->execute();
				}

				$varcharFields = [
					'name',
					'title',
					'url',
				];

				foreach ($varcharFields as $varcharField)
				{
					$fieldName = $varcharField . '_' . $prefix;

					if (!in_array($fieldName, $speakerTableFields))
					{
						$sql = "ALTER TABLE  `#__eb_speakers` ADD  `$fieldName` VARCHAR( 255 );";
						$db->setQuery($sql);
						$db->execute();
					}
				}

				$fieldName = 'description_' . $prefix;

				if (!in_array($fieldName, $speakerTableFields))
				{
					$sql = "ALTER TABLE  `#__eb_speakers` ADD  `$fieldName` TEXT NULL;";
					$db->setQuery($sql);
					$db->execute();
				}
			}
		}
	}

	/**
	 * Count total none-offline payment methods.
	 *
	 * @return int
	 */
	public static function getNumberNoneOfflinePaymentMethods()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__eb_payment_plugins')
			->where('published = 1')
			->where('NAME NOT LIKE "os_offline%"');
		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	/**
	 * Get URL of the site, using for Ajax request
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public static function getSiteUrl()
	{
		$config  = static::getConfig();
		$siteUrl = Uri::root();

		if ($config->remove_www_from_site_url)
		{
			$siteUrl = str_replace('www.', '', $siteUrl);
		}

		return $siteUrl;
	}

	/**
	 * List of validate rules supported by the extension via jQuery validation engine
	 *
	 * @return array
	 */
	public static function validateRules()
	{
		$config     = self::getConfig();
		$dateFormat = $config->date_field_format ?: '%Y-%m-%d';
		$dateFormat = str_replace('%', '', $dateFormat);
		$dateNow    = HTMLHelper::_('date', 'now', $dateFormat);

		return [
			'',
			'custom[integer]',
			'custom[number]',
			'custom[email]',
			'custom[url]',
			'custom[phone]',
			"custom[date],past[$dateNow]",
			'custom[ipv4]',
			'minSize[6]',
			'maxSize[12]',
			'custom[integer],min[-5]',
			'custom[integer],max[50]]',
		];
	}

	/**
	 * Get Itemid of Event Booking extension
	 *
	 * @return int
	 */
	public static function getItemid()
	{
		JLoader::register('EventbookingHelperRoute', JPATH_ROOT . '/components/com_eventbooking/helper/route.php');

		return EventbookingHelperRoute::getDefaultMenuItem();
	}

	/**
	 * Get default Itemid for registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return int
	 */
	public static function getDefaultItemidForRegistration($row)
	{
		if (Multilanguage::isEnabled() && $row->language && $row->language != '*')
		{
			return EventbookingHelperRoute::getDefaultMenuItem($row->language);
		}

		return EventbookingHelperRoute::getDefaultMenuItem();
	}

	/**
	 * Round amount according to the settings in Configuration
	 *
	 * @param   float  $amount
	 * @param          $config
	 *
	 * @return float
	 */
	public static function roundAmount($amount, $config)
	{
		$decimals = isset($config->decimals) ? (int) $config->decimals : 2;

		return round((float) $amount, $decimals);
	}

	/**
	 * Format the currency according to the settings in Configuration
	 *
	 * @param   float      $amount  the input amount
	 * @param   RADConfig  $config  the config object
	 *
	 * @return string   the formatted string
	 */
	public static function formatAmount($amount, $config)
	{
		$decimals      = isset($config->decimals) ? (int) $config->decimals : 2;
		$dec_point     = $config->dec_point ?? '.';
		$thousands_sep = $config->thousands_sep ?? ',';

		return number_format((float) $amount, $decimals, $dec_point, $thousands_sep);
	}

	/**
	 * Format the currency according to the settings in Configuration
	 *
	 * @param   float      $amount  the input amount
	 * @param   RADConfig  $config  the config object
	 *
	 * @return string   the formatted string
	 */
	public static function formatPrice($amount, $config)
	{
		$decimals = isset($config->decimals) ? (int) $config->decimals : 2;

		return number_format((float) $amount, $decimals);
	}

	/**
	 * Format the currency according to the settings in Configuration
	 *
	 * @param   float      $amount          the input amount
	 * @param   RADConfig  $config          the config object
	 * @param   string     $currencySymbol  the currency symbol. If null, the one in configuration will be used
	 *
	 * @return string   the formatted string
	 */
	public static function formatCurrency($amount, $config, $currencySymbol = null)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideHelper', 'formatCurrency'))
		{
			return EventbookingHelperOverrideHelper::formatCurrency($amount, $config, $currencySymbol);
		}

		$decimals      = isset($config->decimals) ? (int) $config->decimals : 2;
		$dec_point     = $config->dec_point ?? '.';
		$thousands_sep = $config->thousands_sep ?? ',';
		$symbol        = $currencySymbol ?: $config->currency_symbol;

		return $config->currency_position ? (number_format((float) $amount, $decimals, $dec_point, $thousands_sep) . $symbol) : ($symbol .
			number_format((float) $amount, $decimals, $dec_point, $thousands_sep));
	}

	/**
	 * Load Event Booking language file
	 */
	public static function loadLanguage()
	{
		static $loaded;

		if (!$loaded)
		{
			$lang = Factory::getLanguage();
			$lang->load('com_eventbookingcommon', JPATH_ADMINISTRATOR);
			$lang->load('com_eventbooking', JPATH_ROOT);

			$loaded = true;
		}
	}

	/**
	 * Method to load component frontend component language
	 *
	 * @param $tag
	 * @param $force
	 */
	public static function loadComponentLanguage($tag, $force = false)
	{
		$language = Factory::getLanguage();

		if ($force && (!$tag || $tag == '*'))
		{
			$tag = self::getDefaultLanguage();
		}

		if ($tag && $tag != '*' && ($tag != $language->getTag() || $force))
		{
			$language->load('com_eventbookingcommon', JPATH_ADMINISTRATOR, $tag, true);
			$language->load('com_eventbooking', JPATH_ROOT, $tag, true);
		}
	}

	/**
	 * Load frontend language file for the registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public static function loadRegistrantLanguage($row)
	{
		// Load the default frontend language
		$tag = $row->language;

		if (!$tag || $tag == '*')
		{
			$tag = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		}

		Factory::getLanguage()->load('com_eventbookingcommon', JPATH_ADMINISTRATOR, $tag, false, false);
		Factory::getLanguage()->load('com_eventbooking', JPATH_ROOT, $tag);
	}

	/**
	 * Display list of files which users can choose for event attachment
	 *
	 * @param   array      $attachment
	 * @param   RADConfig  $config
	 * @param   string     $inputName
	 *
	 * @return mixed
	 */
	public static function attachmentList($attachment, $config, $inputName = 'available_attachment')
	{
		$path    = JPATH_ROOT . '/' . ($config->attachments_path ?: 'media/com_eventbooking');
		$files   = Folder::files(
			$path,
			strlen(trim($config->attachment_file_types)) ? $config->attachment_file_types : 'bmp|gif|jpg|png|swf|zip|doc|pdf|xls|zip'
		);
		$options = [];

		foreach ($files as $file)
		{
			$options[] = HTMLHelper::_('select.option', $file, $file);
		}

		return EventbookingHelperHtml::getChoicesJsSelect(
			HTMLHelper::_(
				'select.genericlist',
				$options,
				$inputName . '[]',
				'class="advancedSelect input-xlarge" multiple="multiple" size="6" ',
				'value',
				'text',
				$attachment
			)
		);
	}

	/**
	 * Get total events of a category
	 *
	 * @param   int       $categoryId
	 * @param   bool      $includeChildren
	 * @param   Registry  $params
	 *
	 * @return int
	 * @throws Exception
	 */
	public static function getTotalEvent($categoryId, $includeChildren = true, $params = null)
	{
		$user   = Factory::getUser();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);
		$config = self::getConfig();

		$arrCats   = [];
		$cats      = [];
		$arrCats[] = $categoryId;
		$cats[]    = $categoryId;

		if ($includeChildren)
		{
			while (count($arrCats))
			{
				$catId = array_pop($arrCats);

				//Get list of children category
				$query->clear()
					->select('id')
					->from('#__eb_categories')
					->where('parent = ' . $catId)
					->where('published = 1');
				$db->setQuery($query);
				$childrenCategories = $db->loadColumn();
				$arrCats            = array_merge($arrCats, $childrenCategories);
				$cats               = array_merge($cats, $childrenCategories);
			}
		}

		$query->clear()
			->select('COUNT(DISTINCT a.id)')
			->from('#__eb_events AS a')
			->innerJoin('#__eb_event_categories AS b ON a.id = b.event_id')
			->where('b.category_id IN (' . implode(',', $cats) . ')')
			->where('published = 1')
			->where('a.hidden = 0')
			->where('`access` IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');

		if ($config->hide_past_events)
		{
			$currentDate = $db->quote(HTMLHelper::_('date', 'Now', 'Y-m-d'));

			if ($config->show_children_events_under_parent_event)
			{
				$query->where(
					'(DATE(a.event_date) >= ' . $currentDate . ' OR DATE(a.cut_off_date) >= ' . $currentDate . ' OR DATE(a.max_end_date) >= ' . $currentDate . ')'
				);
			}
			else
			{
				$query->where('(DATE(a.event_date) >= ' . $currentDate . ' OR DATE(a.cut_off_date) >= ' . $currentDate . ')');
			}
		}

		if ($params)
		{
			if ($params->get('hide_children_events'))
			{
				$query->where('a.parent_id = 0');
			}
		}
		elseif ($config->show_children_events_under_parent_event)
		{
			$query->where('a.parent_id = 0');
		}

		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	/**
	 * Get all dependencies custom fields
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public static function getAllDependencyFields($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$queue  = [$id];
		$fields = [$id];

		while (count($queue))
		{
			$masterFieldId = array_pop($queue);

			//Get list of dependency fields of this master field
			$query->clear()
				->select('id')
				->from('#__eb_fields')
				->where('depend_on_field_id=' . $masterFieldId);
			$db->setQuery($query);
			$rows = $db->loadObjectList();

			if (count($rows))
			{
				foreach ($rows as $row)
				{
					$queue[]  = $row->id;
					$fields[] = $row->id;
				}
			}
		}

		return $fields;
	}

	/**
	 * Get max number of registrants allowed for an event
	 *
	 * @param $event
	 *
	 * @return int
	 */
	public static function getMaxNumberRegistrants($event)
	{
		$config         = EventbookingHelper::getConfig();
		$eventCapacity  = (int) $event->event_capacity;
		$maxGroupNumber = (int) $event->max_group_number ?: (int) $config->get('default_max_number_registrants', 0);

		if ($eventCapacity)
		{
			$maxRegistrants = $eventCapacity - $event->total_registrants;
		}
		else
		{
			$maxRegistrants = -1;
		}

		if ($maxGroupNumber)
		{
			if ($maxRegistrants == -1)
			{
				$maxRegistrants = $maxGroupNumber;
			}
			else
			{
				$maxRegistrants = min($maxRegistrants, $maxGroupNumber);
			}
		}

		if ($maxRegistrants == -1)
		{
			//Default max registrants, we should only allow smaller than 10 registrants to make the form not too long
			$maxRegistrants = 20;
		}

		return $maxRegistrants;
	}

	/**
	 * Get country code
	 *
	 * @param   string  $countryName
	 *
	 * @return string
	 */
	public static function getCountryCode($countryName)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		if (empty($countryName))
		{
			$config      = EventbookingHelper::getConfig();
			$countryName = $config->get('default_country');
		}

		$query->select('country_2_code')
			->from('#__eb_countries')
			->where('LOWER(name) = ' . $db->quote(StringHelper::strtolower($countryName)));
		$db->setQuery($query);
		$countryCode = $db->loadResult();

		if (!$countryCode)
		{
			$countryCode = 'US';
		}

		return $countryCode;
	}

	/**
	 * Get state_2_code of a state, use to pass to payment gateway
	 *
	 * @param   string  $country
	 * @param   string  $state
	 *
	 * @return string
	 */
	public static function getStateCode($country, $state)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('state_2_code')
			->from('#__eb_states AS a')
			->innerJoin('#__eb_countries AS b ON a.country_id = b.id')
			->where('a.state_name = ' . $db->quote($state))
			->where('b.name = ' . $db->quote($country));
		$db->setQuery($query);

		return $db->loadResult() ?: $state;
	}

	/**
	 * Get categories of the given events
	 *
	 * @param   array  $eventIds
	 *
	 * @return array
	 */
	public static function getCategories($eventIds = [])
	{
		if (count($eventIds))
		{
			$db          = Factory::getDbo();
			$query       = $db->getQuery(true);
			$fieldSuffix = EventbookingHelper::getFieldSuffix();
			$query->select($db->quoteName(['a.id', 'a.name' . $fieldSuffix, 'a.color_code'], [null, 'name', null]))
				->from('#__eb_categories AS a')
				->where('published = 1')
				->where(
					'id IN (SELECT category_id FROM #__eb_event_categories WHERE event_id IN (' . implode(
						',',
						$eventIds
					) . ') AND main_category = 1)'
				)
				->order('a.ordering');

			$db->setQuery($query);

			return $db->loadObjectList();
		}

		return [];
	}

	/**
	 * @param   string  $fileExts
	 *
	 * @return string[]
	 */
	public static function normalizeFileExts(?string $fileExts): array
	{
		if (!$fileExts)
		{
			$fileExts = 'doc|docx|ppt|pptx|pdf|zip|rar|bmp|gif|jpg|jpeg|png|swf|zipx';
		}

		$fileExts = explode('|', $fileExts);
		$fileExts = array_map('trim', $fileExts);

		return array_map('strtolower', $fileExts);
	}

	/**
	 * Display copy right information
	 */
	public static function displayCopyRight()
	{
		echo '<div class="copyright" style="text-align:center;margin-top: 5px;"><a href="https://joomdonation.com/joomla-extensions/events-booking-joomla-events-registration.html" target="_blank"><strong>Event Booking</strong></a> version ' .
			self::getInstalledVersion() . ', Copyright (C) 2010 - ' . date('Y') .
			' <a href="https://joomdonation.com" target="_blank"><strong>Ossolution Team</strong></a></div>';
	}

	/**
	 * Check if the given message entered via HTML editor has actual data
	 *
	 * @param $string
	 *
	 * @return bool
	 */
	public static function isValidMessage($string)
	{
		if (!is_string($string) || strlen($string) === 0)
		{
			return false;
		}

		$string = strip_tags($string, '<img>');

		$string = str_replace('&nbsp;', '', $string);
		$string = str_replace("\xc2\xa0", ' ', $string);

		// Remove all special characters
		$string = str_replace(['.', ' ', "\n", "\t", "\r"], '', $string);

		$string = trim($string);

		if (strlen($string) > 10)
		{
			return true;
		}

		return false;
	}

	/**
	 * Format invoice number
	 *
	 * @param   string                       $invoiceNumber
	 * @param   RADConfig                    $config
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return string formatted invoice number
	 */
	public static function formatInvoiceNumber($invoiceNumber, $config, $row = null)
	{
		if (!$invoiceNumber)
		{
			return '';
		}

		if (!empty($row->invoice_year))
		{
			$year = $row->invoice_year;
		}
		elseif (!empty($row->register_date))
		{
			$date = Factory::getDate($row->register_date);
			$year = $date->format('Y');
		}
		else
		{
			$year = 0;
		}

		$invoicePrefix = str_replace('[YEAR]', $year, $config->invoice_prefix);

		if (strlen($year) == 4)
		{
			$invoicePrefix = str_replace('[YEAR_LAST2_DIGITS]', substr($year, 2), $invoicePrefix);
		}
		else
		{
			$invoicePrefix = str_replace('[YEAR_LAST2_DIGITS]', '', $invoicePrefix);
		}

		return $invoicePrefix . str_pad($invoiceNumber, $config->invoice_number_length ?: 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Format certificate number
	 *
	 * @param   int        $id
	 * @param   RADConfig  $config
	 *
	 * @return string formatted certificate number
	 */
	public static function formatCertificateNumber($id, $config)
	{
		$db  = Factory::getDbo();
		$row = new EventbookingTableRegistrant($db);
		$row->load($id);

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);
		$event       = EventbookingHelperDatabase::getEvent($row->event_id, null, $fieldSuffix);
		$prefix      = str_replace('[EVENT_TITLE]', File::makeSafe($event->title), $config->certificate_prefix);

		return $prefix .
			str_pad($id, $config->certificate_number_length ?: 5, '0', STR_PAD_LEFT);
	}

	/**
	 * Update max child date of a recurring event
	 *
	 * @param $parentId
	 */
	public static function updateParentMaxEventDate($parentId)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('MAX(event_date) AS max_event_date, MAX(cut_off_date) AS max_cut_off_date')
			->from('#__eb_events')
			->where('published = 1')
			->where('parent_id = ' . $parentId);
		$db->setQuery($query);
		$maxDateInfo  = $db->loadObject();
		$maxEventDate = $maxDateInfo->max_event_date;

		if ((int) $maxDateInfo->max_cut_off_date)
		{
			$oMaxEventDate  = new DateTime($maxDateInfo->max_event_date);
			$oMaxCutOffDate = new DateTime($maxDateInfo->max_cut_off_date);

			if ($oMaxCutOffDate > $oMaxEventDate)
			{
				$maxEventDate = $maxDateInfo->max_cut_off_date;
			}
		}

		$query->clear()
			->update('#__eb_events')
			->set('max_end_date = ' . $db->quote($maxEventDate))
			->where('id = ' . $parentId);
		$db->setQuery($query);
		$db->execute();

		return $maxEventDate;
	}

	/**
	 * Get TCPDF
	 *
	 * @param   string  $title
	 * @param   string  $pageOrientation
	 * @param   string  $pageFormat
	 *
	 * @return TCPDF
	 */
	public static function getTCPDF($title, $pageOrientation = null, $pageFormat = null)
	{
		require_once JPATH_ROOT . '/components/com_eventbooking/tcpdf/config/tcpdf_config.php';

		JLoader::register('TCPDF', JPATH_ROOT . '/components/com_eventbooking/tcpdf/tcpdf.php');

		if ($pageOrientation === null)
		{
			$pageOrientation = PDF_PAGE_ORIENTATION;
		}

		if ($pageFormat === null)
		{
			$pageFormat = PDF_PAGE_FORMAT;
		}

		$config = EventbookingHelper::getConfig();

		$pdf = new TCPDF($pageOrientation, PDF_UNIT, $pageFormat, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor(Factory::getApplication()->get('sitename'));
		$pdf->SetTitle($title);
		$pdf->SetSubject($title);
		$pdf->SetKeywords($title);
		$pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
		$pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(
			$config->get('pdf_margin_left', PDF_MARGIN_LEFT),
			$config->get('pdf_margin_top', 0),
			$config->get('pdf_margin_right', PDF_MARGIN_RIGHT)
		);
		$pdf->setHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->setFooterMargin(PDF_MARGIN_FOOTER);
		//set auto page breaks
		$pdf->SetAutoPageBreak(true, $config->get('pdf_margin_bottom', PDF_MARGIN_BOTTOM));

		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$font = empty($config->pdf_font) ? 'times' : $config->pdf_font;

		// True type font
		if (substr($font, -4) == '.ttf')
		{
			$font = TCPDF_FONTS::addTTFfont(JPATH_ROOT . '/components/com_eventbooking/tcpdf/fonts/' . $font, 'TrueTypeUnicode', '', 96);
		}

		$pdf->SetFont($font, '', 8);

		return $pdf;
	}

	/**
	 * Get invoice content for a single registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public static function getInvoiceContentForRegistration($row)
	{
		// Variable to cache event data to avoid having to query database again in case exporting multiple registrations invoices
		static $events;

		$config = self::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true);

		$fieldSuffix = EventbookingHelper::getFieldSuffix($row->language);

		if (!isset($events[$row->event_id . $fieldSuffix]))
		{
			$query->select('*')
				->from('#__eb_events')
				->where('id = ' . (int) $row->event_id);

			if ($fieldSuffix)
			{
				EventbookingHelperDatabase::getMultilingualFields($query, ['title'], $fieldSuffix);
			}

			$db->setQuery($query);

			$events[$row->event_id . $fieldSuffix] = $db->loadObject();
		}

		$rowEvent = $events[$row->event_id . $fieldSuffix];

		if ($config->multiple_booking)
		{
			if (self::isValidMessage($config->{'invoice_format_cart' . $fieldSuffix}))
			{
				$invoiceOutput = $config->{'invoice_format_cart' . $fieldSuffix};
			}
			else
			{
				$invoiceOutput = $config->invoice_format_cart;
			}
		}
		else
		{
			if ($fieldSuffix && self::isValidMessage($rowEvent->{'invoice_format' . $fieldSuffix}))
			{
				$invoiceOutput = $rowEvent->{'invoice_format' . $fieldSuffix};
			}
			elseif (self::isValidMessage($rowEvent->invoice_format))
			{
				$invoiceOutput = $rowEvent->invoice_format;
			}
			elseif ($fieldSuffix && self::isValidMessage($config->{'invoice_format' . $fieldSuffix}))
			{
				$invoiceOutput = $config->{'invoice_format' . $fieldSuffix};
			}
			else
			{
				$invoiceOutput = $config->invoice_format;
			}
		}

		$invoiceOutput = EventbookingHelperRegistration::processQRCODE($row, $invoiceOutput, false);

		if (strpos($invoiceOutput, '[QRCODE]') !== false)
		{
			EventbookingHelper::generateQrcode($row->id);
			$imgTag        = '<img src="media/com_eventbooking/qrcodes/' . $row->id . '.png" border="0" />';
			$invoiceOutput = str_ireplace('[QRCODE]', $imgTag, $invoiceOutput);
		}

		$replaces = EventbookingHelperRegistration::getRegistrationReplaces($row, $rowEvent, 0, $config->multiple_booking);

		$replaces['invoice_number'] = EventbookingHelper::callOverridableHelperMethod(
			'Helper',
			'formatInvoiceNumber',
			[$row->invoice_number, $config, $row]
		);

		if ((int) $row->payment_date)
		{
			$replaces['invoice_date'] = HTMLHelper::_('date', $row->payment_date, $config->date_format);
		}
		else
		{
			$replaces['invoice_date'] = HTMLHelper::_('date', $row->register_date, $config->date_format);
		}

		if ($row->published == 0)
		{
			$invoiceStatus = Text::_('EB_INVOICE_STATUS_PENDING');
		}
		elseif ($row->published == 1)
		{
			if ($row->payment_status == 0)
			{
				$invoiceStatus = Text::_('EB_PARTIAL_PAYMENT');
			}
			else
			{
				$invoiceStatus = Text::_('EB_INVOICE_STATUS_PAID');
			}
		}
		elseif ($row->published == 2)
		{
			$invoiceStatus = Text::_('EB_INVOICE_STATUS_CANCELLED');
		}
		else
		{
			$invoiceStatus = Text::_('EB_INVOICE_STATUS_UNKNOWN');
		}

		$replaces['INVOICE_STATUS'] = $invoiceStatus;
		unset($replaces['total_amount']);
		unset($replaces['discount_amount']);
		unset($replaces['tax_amount']);

		if ($config->multiple_booking)
		{
			$query->clear()
				->select('a.*, b.event_date, b.event_end_date, b.custom_fields, l.address AS location_address')
				->select($db->quoteName(['b.title' . $fieldSuffix, 'l.name' . $fieldSuffix], ['title', 'location_name']))
				->from('#__eb_registrants AS a')
				->innerJoin('#__eb_events AS b ON a.event_id = b.id')
				->leftJoin('#__eb_locations AS l On b.location_id = l.id')
				->where("(a.id = $row->id OR a.cart_id = $row->id)")
				->order('a.id');
			$db->setQuery($query);
			$rowEvents = $db->loadObjectList();

			$totalNumberRegistrants = 0;
			$groupMembersNames      = [];

			foreach ($rowEvents as $rowEvent)
			{
				$totalNumberRegistrants += $rowEvent->number_registrants;

				$query->clear()
					->select('first_name, last_name')
					->from('#__eb_registrants')
					->where('group_id = ' . $rowEvent->id)
					->order('id');
				$db->setQuery($query);
				$rowMembers = $db->loadObjectList();

				foreach ($rowMembers as $rowMember)
				{
					$groupMembersNames[] = trim($rowMember->first_name . ' ' . $rowMember->last_name);
				}
			}

			$replaces['ALL_GROUP_MEMBERS_NAMES']  = implode(', ', $groupMembersNames);
			$replaces['TOTAL_NUMBER_REGISTRANTS'] = $totalNumberRegistrants;

			$subTotal                           = $replaces['amt_total_amount'];
			$taxAmount                          = $replaces['amt_tax_amount'];
			$discountAmount                     = $replaces['amt_discount_amount'];
			$total                              = $replaces['amt_amount'];
			$paymentProcessingFee               = $replaces['amt_payment_processing_fee'];
			$replaces['EVENTS_LIST']            = EventbookingHelperHtml::loadCommonLayout(
				'emailtemplates/tmpl/invoice_items.php',
				[
					'rowEvents'            => $rowEvents,
					'subTotal'             => $subTotal,
					'taxAmount'            => $taxAmount,
					'discountAmount'       => $discountAmount,
					'paymentProcessingFee' => $paymentProcessingFee,
					'total'                => $total,
					'config'               => $config,
				]
			);
			$replaces['SUB_TOTAL']              = EventbookingHelper::formatCurrency($subTotal, $config);
			$replaces['DISCOUNT_AMOUNT']        = EventbookingHelper::formatCurrency($discountAmount, $config);
			$replaces['TAX_AMOUNT']             = EventbookingHelper::formatCurrency($taxAmount, $config);
			$replaces['PAYMENT_PROCESSING_FEE'] = EventbookingHelper::formatCurrency($paymentProcessingFee, $config);
			$replaces['TOTAL_AMOUNT']           = EventbookingHelper::formatCurrency($total, $config);
			$replaces['DEPOSIT_AMOUNT']         = EventbookingHelper::formatCurrency($replaces['amt_deposit_amount'], $config);
			$replaces['DUE_AMOUNT']             = EventbookingHelper::formatCurrency($replaces['amt_due_amount'], $config);
		}
		else
		{
			$replaces['ITEM_QUANTITY'] = 1;
			$replaces['ITEM_AMOUNT']   = $replaces['ITEM_SUB_TOTAL'] = self::formatCurrency(
				$row->total_amount,
				$config,
				$rowEvent->currency_symbol
			);

			if ($row->number_registrants > 0)
			{
				$amountPerRegistrant = (self::roundAmount($row->total_amount, $config) / $row->number_registrants);
			}
			else
			{
				$amountPerRegistrant = $row->total_amount;
			}

			$replaces['AVG_AMOUNT_PER_REGISTRANT'] = self::formatCurrency($amountPerRegistrant, $config, $rowEvent->currency_symbol);
			$replaces['DISCOUNT_AMOUNT']           = self::formatCurrency($row->discount_amount, $config, $rowEvent->currency_symbol);
			$replaces['SUB_TOTAL']                 = self::formatCurrency(
				$row->total_amount - $row->discount_amount,
				$config,
				$rowEvent->currency_symbol
			);
			$replaces['TAX_AMOUNT']                = self::formatCurrency($row->tax_amount, $config, $rowEvent->currency_symbol);
			$replaces['PAYMENT_PROCESSING_FEE']    = self::formatCurrency($row->payment_processing_fee, $config, $rowEvent->currency_symbol);
			$replaces['TOTAL_AMOUNT']              = self::formatCurrency($row->amount, $config, $rowEvent->currency_symbol);

			$itemName = Text::_('EB_EVENT_REGISTRATION');
			$itemName = str_ireplace('[EVENT_TITLE]', $rowEvent->title, $itemName);
			$itemName = str_replace('[EVENT_DATE]', HTMLHelper::_('date', $rowEvent->event_date, $config->date_format, null), $itemName);
			$itemName = str_replace('[FIRST_NAME]', $row->first_name, $itemName);
			$itemName = str_replace('[LAST_NAME]', $row->last_name, $itemName);
			$itemName = str_replace('[REGISTRANT_ID]', $row->id, $itemName);

			$replaces['ITEM_NAME'] = $itemName;
			$itemRate              = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'getRegistrationRate',
				[$rowEvent->id, $row->number_registrants]
			);
			$replaces['ITEM_RATE'] = self::formatCurrency($itemRate, $config, $rowEvent->currency_symbol);
		}

		$invoiceOutput = EventbookingHelper::replaceUpperCaseTags($invoiceOutput, $replaces);

		return EventbookingHelper::callOverridableHelperMethod('Html', 'processConditionalText', [$invoiceOutput]);
	}

	/**
	 * Generate invoice PDF
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return string
	 */
	public static function generateInvoicePDF($row)
	{
		$config = EventbookingHelper::getConfig();

		self::loadRegistrantLanguage($row);

		$invoiceNumber = self::callOverridableHelperMethod('Helper', 'formatInvoiceNumber', [$row->invoice_number, $config, $row]);

		$invoiceOutput = self::callOverridableHelperMethod('Helper', 'getInvoiceContentForRegistration', [$row]);

		$filePath = JPATH_ROOT . '/media/com_eventbooking/invoices/' . $invoiceNumber . '.pdf';

		$page          = new stdClass();
		$page->content = $invoiceOutput;

		EventbookingHelperPdf::generatePDFFile([$page], $filePath, ['title' => 'Invoice', 'type' => 'invoice']);

		return $filePath;
	}

	/**
	 * Method to generate invoices for multiple registration records
	 *
	 * @param   array  $rows
	 *
	 * @return string
	 */
	public static function generateRegistrantsInvoices($rows)
	{
		// Load frontend language
		EventbookingHelper::loadLanguage();

		$pages = [];

		foreach ($rows as $row)
		{
			$invoiceOutput = self::callOverridableHelperMethod('Helper', 'getInvoiceContentForRegistration', [$row]);

			$page          = new stdClass();
			$page->content = $invoiceOutput;
			$pages[]       = $page;
		}

		$filename = File::makeSafe('registrations_invoices_' . Factory::getDate()->toSql() . '.pdf');

		$filePath = JPATH_ROOT . '/media/com_eventbooking/invoices/' . $filename;

		EventbookingHelperPdf::generatePDFFile($pages, $filePath, ['title' => 'Invoice', 'type' => 'invoice']);

		return $filePath;
	}

	/**
	 * Generate certificate for the given registration records
	 *
	 * @param   array      $rows
	 * @param   RADConfig  $config
	 *
	 * @return array
	 */
	public static function generateCertificates($rows, $config)
	{
		return EventbookingHelper::callOverridableHelperMethod('Certificate', 'generateCertificates', [$rows, $config]);
	}

	/**
	 * Generate PDF file contains exported registrants
	 *
	 * @param   array  $rows
	 * @param   array  $fields
	 * @param   array  $headers
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function generateRegistrantsPDF($rows, $fields, $headers)
	{
		$pdfOutput = EventbookingHelperHtml::loadSharedLayout(
			'common/registrants_pdf.php',
			['rows' => $rows, 'fields' => $fields, 'headers' => $headers]
		);

		//Filename
		$filePath = JPATH_ROOT . '/media/com_eventbooking/registrants.pdf';

		$page          = new stdClass();
		$page->content = $pdfOutput;

		$config = EventbookingHelper::getConfig();

		$options = [
			'title'                => 'Registrants Export',
			'type'                 => 'registrants_invoice',
			'PDF_PAGE_ORIENTATION' => $config->get('registrants_page_orientation') ?: 'P',
			'PDF_PAGE_FORMAT'      => $config->get('registrants_page_format') ?: 'A4',
		];

		EventbookingHelperPdf::generatePDFFile([$page], $filePath, $options);

		return $filePath;
	}

	/**
	 * Display warning if the site is using PHP older than required version
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function displayPHPVersionWarning()
	{
		$config        = self::getConfig();
		$minPHPVersion = '7.4.0';

		if (version_compare(PHP_VERSION, $minPHPVersion, '<')
			&& $config->get('display_php_version_warning', 1)
		)
		{
			Factory::getApplication()->enqueueMessage(
				Text::sprintf('EB_PHP_VERSION_WARNING', PHP_VERSION, $minPHPVersion, $minPHPVersion),
				'warning'
			);
		}
	}

	/**
	 * Generate QRcode for a transaction
	 *
	 * @param $registrantId
	 */
	public static function generateQrcode($registrantId)
	{
		EventbookingHelper::callOverridableHelperMethod('Registration', 'generateQrcode', [$registrantId]);
	}

	/**
	 * Convert all img tags to use absolute URL
	 *
	 * @param   string  $content
	 *
	 * @return string
	 */
	public static function convertImgTags($content)
	{
		$siteUrl = Uri::root();

		// Replace none SEF URLs by absolute SEF URLs
		if (strpos($content, 'href="index.php?') !== false)
		{
			preg_match_all('#href="index.php\?([^"]+)"#m', $content, $matches);

			foreach ($matches[1] as $urlQueryString)
			{
				$content = str_replace(
					'href="index.php?' . $urlQueryString . '"',
					'href="' . Route::link('site', 'index.php?' . $urlQueryString, 0, true) . '"',
					$content
				);
			}
		}

		// Replace relative links, image sources with absolute Urls
		$protocols  = '[a-zA-Z0-9\-]+:';
		$attributes = ['href=', 'src='];

		foreach ($attributes as $attribute)
		{
			if (strpos($content, $attribute) !== false)
			{
				$regex = '#\s' . $attribute . '"(?!/|' . $protocols . '|\#|\')([^"]*)"#m';

				$content = preg_replace($regex, ' ' . $attribute . '"' . $siteUrl . '$1"', $content);
			}
		}

		return $content;
	}

	/**
	 * Get list of recurring event dates
	 *
	 * @param   string  $startDate
	 * @param   string  $endDate
	 * @param   int     $dailyFrequency
	 * @param   int     $numberOccurencies
	 *
	 * @return array
	 */
	public static function getDailyRecurringEventDates($startDate, $endDate, $dailyFrequency, $numberOccurencies)
	{
		$eventDates   = [$startDate];
		$timeZone     = new DateTimeZone(Factory::getApplication()->get('offset'));
		$date         = new DateTime($startDate, $timeZone);
		$dateInterval = new DateInterval('P' . $dailyFrequency . 'D');

		if ($numberOccurencies)
		{
			for ($i = 1; $i < $numberOccurencies; $i++)
			{
				$date->add($dateInterval);
				$eventDates[] = $date->format('Y-m-d H:i:s');
			}
		}
		else
		{
			$recurringEndDate = new DateTime($endDate . ' 23:59:59', $timeZone);

			while (true)
			{
				$date->add($dateInterval);

				if ($date <= $recurringEndDate)
				{
					$eventDates[] = $date->format('Y-m-d H:i:s');
				}
				else
				{
					break;
				}
			}
		}

		return $eventDates;
	}

	/**
	 * Get weekly recurring event dates
	 *
	 * @param   string  $startDate
	 * @param   string  $endDate
	 * @param   Int     $weeklyFrequency
	 * @param   int     $numberOccurrences
	 * @param   array   $weekDays
	 *
	 * @return array
	 */
	public static function getWeeklyRecurringEventDates($startDate, $endDate, $weeklyFrequency, $numberOccurrences, $weekDays)
	{
		$eventDates = [];

		$timeZone           = new DateTimeZone(Factory::getApplication()->get('offset'));
		$recurringStartDate = new Datetime($startDate, $timeZone);
		$hour               = $recurringStartDate->format('H');
		$minutes            = $recurringStartDate->format('i');
		$dayOfWeek          = $recurringStartDate->format('w');
		$startWeek          = clone $recurringStartDate;

		if ($dayOfWeek > 0)
		{
			$startWeek->modify('- ' . $dayOfWeek . ' day');
		}

		$startWeek->setTime($hour, $minutes, 0);
		$dateInterval = new DateInterval('P' . $weeklyFrequency . 'W');

		if ($numberOccurrences)
		{
			$count = 0;

			while ($count < $numberOccurrences)
			{
				foreach ($weekDays as $weekDay)
				{
					$date = clone $startWeek;

					if ($weekDay > 0)
					{
						$date->add(new DateInterval('P' . $weekDay . 'D'));
					}

					if (($date >= $recurringStartDate) && ($count < $numberOccurrences))
					{
						$eventDates[] = $date->format('Y-m-d H:i:s');
						$count++;
					}
				}

				$startWeek->add($dateInterval);
			}
		}
		else
		{
			$recurringEndDate = new DateTime($endDate . ' 23:59:59', $timeZone);

			while (true)
			{
				foreach ($weekDays as $weekDay)
				{
					$date = clone $startWeek;

					if ($weekDay > 0)
					{
						$date->add(new DateInterval('P' . $weekDay . 'D'));
					}

					if (($date >= $recurringStartDate) && ($date <= $recurringEndDate))
					{
						$eventDates[] = $date->format('Y-m-d H:i:s');
					}
				}

				if ($date > $recurringEndDate)
				{
					break;
				}

				$startWeek->add($dateInterval);
			}
		}

		return $eventDates;
	}

	/**
	 * Get list of monthly recurring
	 *
	 * @param   string  $startDate
	 * @param   string  $endDate
	 * @param   int     $monthlyFrequency
	 * @param   int     $numberOccurrences
	 * @param   string  $monthDays
	 *
	 * @return array
	 */
	public static function getMonthlyRecurringEventDates($startDate, $endDate, $monthlyFrequency, $numberOccurrences, $monthDays)
	{
		$eventDates         = [];
		$timeZone           = new DateTimeZone(Factory::getApplication()->get('offset'));
		$recurringStartDate = new Datetime($startDate, $timeZone);
		$date               = clone $recurringStartDate;
		$dateInterval       = new DateInterval('P' . $monthlyFrequency . 'M');
		$monthDays          = explode(',', $monthDays);

		if ($numberOccurrences)
		{
			$count = 0;

			while ($count < $numberOccurrences)
			{
				$currentMonth = $date->format('m');
				$currentYear  = $date->format('Y');

				foreach ($monthDays as $day)
				{
					$date->setDate($currentYear, $currentMonth, $day);

					if (($date >= $recurringStartDate) && ($count < $numberOccurrences))
					{
						$eventDates[] = $date->format('Y-m-d H:i:s');
						$count++;
					}
				}

				$date->add($dateInterval);
			}
		}
		else
		{
			$recurringEndDate = new DateTime($endDate . ' 23:59:59', $timeZone);

			while (true)
			{
				$currentMonth = $date->format('m');
				$currentYear  = $date->format('Y');

				foreach ($monthDays as $day)
				{
					$date->setDate($currentYear, $currentMonth, $day);

					if (($date >= $recurringStartDate) && ($date <= $recurringEndDate))
					{
						$eventDates[] = $date->format('Y-m-d H:i:s');
					}
				}

				if ($date > $recurringEndDate)
				{
					break;
				}

				$date->add(new DateInterval('P' . $monthlyFrequency . 'M'));
			}
		}

		return $eventDates;
	}

	/**
	 * Get list of event dates for recurring events happen on specific date in a month
	 *
	 * @param   string  $startDate
	 * @param   string  $endDate
	 * @param   int     $monthlyFrequency
	 * @param   int     $numberOccurrences
	 * @param   string  $n
	 * @param   string  $day
	 *
	 * @return array
	 */
	public static function getMonthlyRecurringAtDayInWeekEventDates($startDate, $endDate, $monthlyFrequency, $numberOccurrences, $n, $day)
	{
		$eventDates         = [];
		$timeZone           = new DateTimeZone(Factory::getApplication()->get('offset'));
		$recurringStartDate = new Datetime($startDate, $timeZone);
		$date               = clone $recurringStartDate;
		$dateInterval       = new DateInterval('P' . $monthlyFrequency . 'M');

		if ($numberOccurrences)
		{
			$count = 0;

			while ($count < $numberOccurrences)
			{
				$currentMonth = $date->format('M');
				$currentYear  = $date->format('Y');
				$timeString   = "$n $day";
				$timeString   .= " of $currentMonth $currentYear";
				$date->modify($timeString);
				$date->setTime($recurringStartDate->format('H'), $recurringStartDate->format('i'), 0);

				if (($date >= $recurringStartDate) && ($count < $numberOccurrences))
				{
					$eventDates[] = $date->format('Y-m-d H:i:s');
					$count++;
				}

				// Reset to the first day of month to avoid wrong date calculated when months are added to current date
				$date->setDate($date->format('Y'), $date->format('n'), 1);

				$date->add($dateInterval);
			}
		}
		else
		{
			$recurringEndDate = new DateTime($endDate . ' 23:59:59', $timeZone);

			while (true)
			{
				$currentMonth = $date->format('M');
				$currentYear  = $date->format('Y');
				$timeString   = "$n $day";
				$timeString   .= " of $currentMonth $currentYear";
				$date->modify($timeString);
				$date->setTime($recurringStartDate->format('H'), $recurringStartDate->format('i'), 0);

				if (($date >= $recurringStartDate) && ($date <= $recurringEndDate))
				{
					$eventDates[] = $date->format('Y-m-d H:i:s');
				}

				if ($date > $recurringEndDate)
				{
					break;
				}

				// Reset to the first day of month to avoid wrong date calculated when months are added to current date
				$date->setDate($date->format('Y'), $date->format('n'), 1);

				$date->add($dateInterval);
			}
		}

		return $eventDates;
	}

	/**
	 * Calculate level for categories, used when upgrade from old version to new version
	 *
	 * @param        $id
	 * @param        $list
	 * @param        $children
	 * @param   int  $maxlevel
	 * @param   int  $level
	 *
	 * @return mixed
	 */
	public static function calculateCategoriesLevel($id, $list, &$children, $maxlevel = 9999, $level = 1)
	{
		if (@$children[$id] && $level <= $maxlevel)
		{
			foreach ($children[$id] as $v)
			{
				$id        = $v->id;
				$v->level  = $level;
				$list[$id] = $v;
				$list      = self::calculateCategoriesLevel($id, $list, $children, $maxlevel, $level + 1);
			}
		}

		return $list;
	}

	/**
	 * Get User IP address
	 *
	 * @return mixed
	 */
	public static function getUserIp()
	{
		$config = EventbookingHelper::getConfig();

		if ($config->get('store_user_ip', 1))
		{
			return IpHelper::getIp();
		}

		return '';
	}

	/**
	 * Method to check and make sure common language file for certain language exist
	 *
	 * @param   string  $tag
	 */
	public static function ensureCommonLanguageFileExist($tag)
	{
		// Ignore, we always have common language file for English
		if ($tag === 'en-GB')
		{
			return;
		}

		$coLanguageFile = JPATH_ADMINISTRATOR . '/language/' . $tag . '/' . $tag . '.com_eventbookingcommon.ini';

		// Common language file for this language was created before, stop
		if (File::exists($coLanguageFile))
		{
			return;
		}

		// No frontend language file, we need to create common language file for this language base on English language
		if (!File::exists(JPATH_ROOT . '/language/' . $tag . '/' . $tag . '.com_eventbooking.ini'))
		{
			File::copy(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_eventbookingcommon.ini', $coLanguageFile);

			return;
		}

		// Generate common language file base on frontend and backend translation
		$feLanguageFileExist = false;
		$beLanguageFileExist = false;
		$feLanguageFile      = JPATH_ROOT . '/language/' . $tag . '/' . $tag . '.com_eventbooking.ini';
		$beLanguageFile      = JPATH_ADMINISTRATOR . '/language/' . $tag . '/' . $tag . '.com_eventbooking.ini';
		$beLanguage          = new Registry();
		$feLanguage          = new Registry();
		$coLanguage          = new Registry();

		// Load frontend language registry if exists to populate common language file
		if (File::exists($feLanguageFile))
		{
			$feLanguageFileExist = true;
			$feLanguage->loadFile($feLanguageFile, 'INI');
		}

		// Load backend language registry if exists to populate common language file
		if (File::exists($beLanguageFile))
		{
			$beLanguageFileExist = true;
			$beLanguage->loadFile($beLanguageFile, 'INI');
		}

		$coLanguage->loadFile(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_eventbookingcommon.ini', 'INI');

		foreach ($coLanguage->toArray() as $key => $value)
		{
			if ($feLanguage->exists($key))
			{
				$coLanguage->set($key, $feLanguage->get($key));
				$feLanguage->remove($key);
				$beLanguage->remove($key);
			}
			elseif ($beLanguage->exists($key))
			{
				$coLanguage->set($key, $beLanguage->get($key));
				$beLanguage->remove($key);
			}
		}

		if ($feLanguageFileExist)
		{
			LanguageHelper::saveToIniFile($feLanguageFile, $feLanguage->toArray());
		}

		if ($beLanguageFileExist)
		{
			LanguageHelper::saveToIniFile($beLanguageFile, $beLanguage->toArray());
		}

		LanguageHelper::saveToIniFile($coLanguageFile, $coLanguage->toArray());
	}

	/**
	 * Check to see whether this users has permission to edit registrant
	 */
	public static function checkEditRegistrant($rowRegistrant)
	{
		EventbookingHelperLegacy::checkEditRegistrant($rowRegistrant);
	}

	/**
	 * Calculate discount rate which the current user will receive
	 *
	 * @param $discount
	 * @param $groupIds
	 *
	 * @return float
	 */
	public static function calculateMemberDiscount($discount, $groupIds)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideRegistration', 'calculateMemberDiscount'))
		{
			// This is added for backward compatible purpose
			return EventbookingHelperOverrideRegistration::calculateMemberDiscount($discount, $groupIds);
		}

		return EventbookingHelper::callOverridableHelperMethod('Registration', 'calculateMemberDiscountForUser', [$discount, $groupIds]);
	}

	/**
	 * Check to see whether this event still accept registration
	 *
	 * @param   EventbookingTableEvent  $event
	 *
	 * @return bool
	 */
	public static function acceptRegistration($event)
	{
		return EventbookingHelper::callOverridableHelperMethod('Registration', 'acceptRegistration', [$event]);
	}

	/**
	 * Get all custom fields for an event
	 *
	 * @param   int  $eventId
	 *
	 * @return array
	 */
	public static function getAllEventFields($eventId)
	{
		return EventbookingHelperRegistration::getAllEventFields($eventId);
	}

	/**
	 * Get name of published core fields in the system
	 *
	 * @return array
	 */
	public static function getPublishedCoreFields()
	{
		return EventbookingHelperRegistration::getPublishedCoreFields();
	}

	/**
	 * Get the form fields to display in deposit payment form
	 *
	 * @return array
	 */
	public static function getDepositPaymentFormFields()
	{
		return EventbookingHelperRegistration::getDepositPaymentFormFields();
	}

	/**
	 * Get the form fields to display in registration form
	 *
	 * @param   int     $eventId  (ID of the event or ID of the registration record in case the system use shopping cart)
	 * @param   int     $registrationType
	 * @param   string  $activeLanguage
	 *
	 * @return array
	 */
	public static function getFormFields($eventId = 0, $registrationType = 0, $activeLanguage = null)
	{
		return EventbookingHelperRegistration::getFormFields($eventId, $registrationType, $activeLanguage);
	}

	/**
	 * Get registration rate for group registration
	 *
	 * @param   int  $eventId
	 * @param   int  $numberRegistrants
	 *
	 * @return mixed
	 */
	public static function getRegistrationRate($eventId, $numberRegistrants)
	{
		return EventbookingHelper::callOverridableHelperMethod('Registration', 'getRegistrationRate', [$eventId, $numberRegistrants]);
	}

	/**
	 * Calculate fees use for individual registration
	 *
	 * @param   object     $event
	 * @param   RADForm    $form
	 * @param   array      $data
	 * @param   RADConfig  $config
	 * @param   string     $paymentMethod
	 *
	 * @return array
	 */
	public static function calculateIndividualRegistrationFees($event, $form, $data, $config, $paymentMethod = null)
	{
		return EventbookingHelperRegistration::calculateIndividualRegistrationFees($event, $form, $data, $config, $paymentMethod);
	}

	/**
	 * Calculate fees use for group registration
	 *
	 * @param   object     $event
	 * @param   RADForm    $form
	 * @param   array      $data
	 * @param   RADConfig  $config
	 * @param   string     $paymentMethod
	 *
	 * @return array
	 */
	public static function calculateGroupRegistrationFees($event, $form, $data, $config, $paymentMethod = null)
	{
		return EventbookingHelperRegistration::calculateGroupRegistrationFees($event, $form, $data, $config, $paymentMethod);
	}

	/**
	 * Calculate registration fee for cart registration
	 *
	 * @param   EventbookingHelperCart  $cart
	 * @param   RADForm                 $form
	 * @param   array                   $data
	 * @param   RADConfig               $config
	 * @param   string                  $paymentMethod
	 *
	 * @return array
	 */
	public static function calculateCartRegistrationFee($cart, $form, $data, $config, $paymentMethod = null)
	{
		return EventbookingHelperRegistration::calculateCartRegistrationFee($cart, $form, $data, $config, $paymentMethod);
	}

	/**
	 * Check to see whether we will show billing form on group registration
	 *
	 * @param   int  $eventId
	 *
	 * @return boolean
	 */
	public static function showBillingStep($eventId)
	{
		return EventbookingHelperRegistration::showBillingStep($eventId);
	}

	/**
	 * Get the form data used to bind to the RADForm object
	 *
	 * @param   array   $rowFields
	 * @param   int     $eventId
	 * @param   int     $userId
	 * @param   object  $config
	 *
	 * @return array
	 */
	public static function getFormData($rowFields, $eventId, $userId, $config)
	{
		return EventbookingHelperRegistration::getFormData($rowFields, $eventId, $userId);
	}

	/**
	 * Get data of registrant using to auto populate registration form
	 *
	 * @param   EventbookingTableRegistrant  $rowRegistrant
	 * @param   array                        $rowFields
	 *
	 * @return array
	 */
	public static function getRegistrantData($rowRegistrant, $rowFields)
	{
		return EventbookingHelperRegistration::getRegistrantData($rowRegistrant, $rowFields);
	}

	/**
	 * Create a user account
	 *
	 * @param   array  $data
	 *
	 * @return int Id of created user
	 */
	public static function saveRegistration($data)
	{
		return EventbookingHelperRegistration::saveRegistration($data);
	}

	/**
	 * We only need to generate invoice for paid events only
	 *
	 * @param $row
	 *
	 * @return bool
	 */
	public static function needInvoice($row)
	{
		return EventbookingHelper::callOverridableHelperMethod('Registration', 'needInvoice', [$row]);
	}

	/**
	 * Get the invoice number for this registration record
	 *
	 * @return int
	 */
	public static function getInvoiceNumber()
	{
		return EventbookingHelper::callOverridableHelperMethod('Registration', 'getInvoiceNumber');
	}

	/**
	 * Helper function for sending emails to registrants and administrator
	 *
	 * @param   RegistrantEventBooking  $row
	 * @param   object                  $config
	 */
	public static function sendEmails($row, $config)
	{
		EventbookingHelper::callOverridableHelperMethod('Mail', 'sendEmails', [$row, $config]);
	}

	/**
	 * Update Group Members record to have same information with billing record
	 *
	 * @param   int  $groupId
	 */
	public static function updateGroupRegistrationRecord($groupId)
	{
		EventbookingHelperRegistration::updateGroupRegistrationRecord($groupId);
	}

	/**
	 * Method to build common tags use for email messages
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 *
	 * @return array
	 */
	public static function buildDepositPaymentTags($row, $config)
	{
		return EventbookingHelper::callOverridableHelperMethod('Registration', 'buildDepositPaymentTags', [$row, $config]);
	}

	/**
	 * Build tags related to event
	 *
	 * @param   EventbookingTableEvent  $event
	 * @param   RADConfig               $config
	 *
	 * @return array
	 */
	public static function buildEventTags($event, $config)
	{
		return EventbookingHelperRegistration::buildEventTags($event, $config);
	}

	/**
	 * Build tags array to use to replace the tags use in email & messages
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADForm                      $form
	 * @param   EventbookingTableEvent       $event
	 * @param   RADConfig                    $config
	 * @param   bool                         $loadCss
	 *
	 * @return array
	 */
	public static function buildTags($row, $form, $event, $config, $loadCss = true)
	{
		return EventbookingHelperRegistration::buildTags($row, $form, $event, $config, $loadCss);
	}

	/**
	 * Get email content, used for [REGISTRATION_DETAIL] tag
	 *
	 * @param   RADConfig                    $config
	 * @param   EventbookingTableRegistrant  $row
	 * @param   bool                         $loadCss
	 * @param   RADForm                      $form
	 * @param   bool                         $toAdmin
	 *
	 * @return string
	 */
	public static function getEmailContent($config, $row, $loadCss = true, $form = null, $toAdmin = false)
	{
		return EventbookingHelperRegistration::getEmailContent($config, $row, $loadCss, $form, $toAdmin);
	}

	/**
	 * Get group member detail, using for [MEMBER_DETAIL] tag in the email message
	 *
	 * @param   RADConfig                    $config
	 * @param   EventbookingTableRegistrant  $rowMember
	 * @param   EventbookingTableEvent       $rowEvent
	 * @param   EventbookingTableLocation    $rowLocation
	 * @param   bool                         $loadCss
	 * @param   RADForm                      $memberForm
	 *
	 * @return string
	 */
	public static function getMemberDetails($config, $rowMember, $rowEvent, $rowLocation, $loadCss = true, $memberForm = null)
	{
		return EventbookingHelperRegistration::getMemberDetails($config, $rowMember, $rowEvent, $rowLocation, $loadCss, $memberForm);
	}

	/**
	 * Check to see whether the current users can access View List function
	 *
	 * @param   int  $eventId
	 *
	 * @return bool
	 */
	public static function canViewRegistrantList($eventId = 0)
	{
		return EventbookingHelper::callOverridableHelperMethod('Acl', 'canViewRegistrantList', [$eventId]);
	}

	/**
	 * Check to see whether this event can be cancelled
	 *
	 * @param   int  $eventId
	 *
	 * @return bool
	 */
	public static function canCancel($eventId)
	{
		return EventbookingHelperAcl::canCancel($eventId);
	}

	public static function canExportRegistrants($eventId = 0)
	{
		return EventbookingHelperAcl::canExportRegistrants($eventId);
	}

	/**
	 * Check to see whether the current user can change status (publish/unpublish) of the given event
	 *
	 * @param $eventId
	 *
	 * @return bool
	 */
	public static function canChangeEventStatus($eventId)
	{
		return EventbookingHelperAcl::canChangeEventStatus($eventId);
	}

	/**
	 * Check to see whether the user can cancel registration for the given event
	 *
	 * @param $eventId
	 *
	 * @return bool|int
	 */
	public static function canCancelRegistration($eventId)
	{
		return EventbookingHelperAcl::canCancelRegistration($eventId);
	}

	/**
	 * Check to see whether the current user can edit registrant
	 *
	 * @param   int  $eventId
	 *
	 * @return boolean
	 */
	public static function checkEditEvent($eventId)
	{
		return EventbookingHelperAcl::checkEditEvent($eventId);
	}

	/**
	 * Check to see whether the current user can delete the given registrant
	 *
	 * @param   int  $id
	 *
	 * @return bool
	 */
	public static function canDeleteRegistrant($id = 0)
	{
		return EventbookingHelperAcl::canDeleteRegistrant($id);
	}

	/**
	 * Download PDF Certificates
	 *
	 * @param   array      $rows
	 * @param   RADConfig  $config
	 */
	public static function downloadCertificates($rows, $config)
	{
		EventbookingHelperLegacy::downloadCertificates($rows, $config);
	}

	/**
	 * Generate and download invoice of given registration record
	 *
	 * @param   int  $id
	 */
	public static function downloadInvoice($id)
	{
		EventbookingHelperLegacy::downloadInvoice($id);
	}

	/**
	 * Process download a file
	 *
	 * @param   string  $file  : Full path to the file which will be downloaded
	 */
	public static function processDownload($filePath, $filename, $detectFilename = false)
	{
		EventbookingHelperLegacy::processDownload($filePath, $filename, $detectFilename);
	}

	/**
	 * Get mimetype of a file
	 *
	 * @return string
	 */
	public static function getMimeType($ext)
	{
		return EventbookingHelperLegacy::getMimeType($ext);
	}

	/**
	 * Read file
	 *
	 * @param   string  $filename
	 * @param   bool    $retbytes
	 *
	 * @return bool
	 */
	public static function readfile_chunked($filename, $retbytes = true)
	{
		return EventbookingHelperLegacy::readfile_chunked($filename, $retbytes);
	}

	public static function getDeliciousButton($title, $link)
	{
		return EventbookingHelperLegacy::getDeliciousButton($title, $link);
	}

	public static function getDiggButton($title, $link)
	{
		return EventbookingHelperLegacy::getDiggButton($title, $link);
	}

	public static function getFacebookButton($title, $link)
	{
		return EventbookingHelperLegacy::getFacebookButton($title, $link);
	}

	public static function getGoogleButton($title, $link)
	{
		return EventbookingHelperLegacy::getGoogleButton($title, $link);
	}

	public static function getStumbleuponButton($title, $link)
	{
		return EventbookingHelperLegacy::getStumbleuponButton($title, $link);
	}

	public static function getTechnoratiButton($title, $link)
	{
		return EventbookingHelperLegacy::getTechnoratiButton($title, $link);
	}

	public static function getTwitterButton($title, $link)
	{
		return EventbookingHelperLegacy::getTwitterButton($title, $link);
	}

	public static function getLinkedInButton($title, $link)
	{
		return EventbookingHelperLegacy::getLinkedInButton($title, $link);
	}

	/**
	 * @return string
	 */
	public static function validateEngine()
	{
		return EventbookingHelperLegacy::validateEngine();
	}

	public static function getURL()
	{
		return EventbookingHelperLegacy::getURL();
	}

	/**
	 * Get language use for re-captcha
	 *
	 * @return string
	 */
	public static function getRecaptchaLanguage()
	{
		return EventbookingHelperLegacy::getRecaptchaLanguage();
	}

	/**
	 * Generate user selection box
	 *
	 * @param   int     $userId
	 * @param   string  $fieldName
	 * @param   int     $registrantId
	 *
	 * @return string
	 */
	public static function getUserInput($userId, $fieldName = 'user_id', $registrantId = 0)
	{
		return EventbookingHelperHtml::getUserInput($userId, $fieldName, $registrantId);
	}

	/**
	 * Generate article selection box
	 *
	 * @param   int     $fieldValue
	 * @param   string  $fieldName
	 *
	 * @return string
	 */
	public static function getArticleInput($fieldValue, $fieldName = 'article_id')
	{
		return EventbookingHelperHtml::getArticleInput($fieldValue, $fieldName);
	}

	/**
	 * Check to see whether the current users can add events from front-end
	 */
	public static function checkAddEvent()
	{
		return Factory::getUser()->authorise('eventbooking.addevent', 'com_eventbooking');
	}

	/**
	 * Check to see whether the current user can
	 *
	 * @param   int  $eventId
	 */
	public static function checkEventAccess($eventId)
	{
		EventbookingHelperLegacy::checkEventAccess($eventId);
	}

	/**
	 * Check to see whether a users to access to registration history
	 */
	public static function checkAccessHistory()
	{
		EventbookingHelperLegacy::checkAccessHistory();
	}

	/**
	 * Send notification emails to waiting list users when someone cancel registration
	 *
	 * @param $row
	 * @param $config
	 */
	public static function notifyWaitingList($row, $config)
	{
		EventbookingHelper::callOverridableHelperMethod('Mail', 'sendWaitingListNotificationEmail', [$row, $config]);
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
		return EventbookingHelperLegacy::getColorCodeOfEvent($eventId);
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
		return EventbookingHelperLegacy::getEventMainCategory($eventId);
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
		return EventbookingHelperLegacy::parentCategories($row);
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
		return EventbookingHelperLegacy::getTotalRegistrants($eventId);
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
		return EventbookingHelperLegacy::getPaymentMethodTitle($methodName);
	}

	/**
	 * Make sure the common tag which user entered is valid format
	 *
	 * @param   string  $tagName
	 *
	 * @return string
	 */
	public static function normalizeCommonTagName(string $tagName)
	{
		if (strpos($tagName, '[') === 0)
		{
			$tagName = substr($tagName, 1);
		}

		if (substr($tagName, -1) == ']')
		{
			$tagName = substr($tagName, 0, -1);
		}

		return $tagName;
	}

	/**
	 * Get list of fields which can be used to update from parent event to children events
	 *
	 * @return array
	 */
	public static function getEventFieldsToUpdate()
	{
		$fieldsToUpdate = [
			'created_by',
			'main_category_id',
			'thumb',
			'image',
			'location_id',
			'tax_rate',
			'registration_type',
			'title',
			'short_description',
			'description',
			'access',
			'registration_access',
			'individual_price',
			'price_text',
			'tax_rate',
			'event_capacity',
			'registration_type',
			'min_group_number',
			'max_group_number',
			'discount_type',
			'discount',
			'discount_groups',
			'discount_amounts',
			'enable_cancel_registration',
			'early_bird_discount_amount',
			'early_bird_discount_type',
			'deposit_type',
			'deposit_amount',
			'paypal_email',
			'from_name',
			'from_email',
			'reply_to_email',
			'notification_emails',
			'registration_form_message',
			'registration_form_message_group',
			'admin_email_body',
			'user_email_body',
			'user_email_body_offline',
			'group_member_email_body',
			'thanks_message',
			'thanks_message_offline',
			'registration_approved_email_body',
			'reminder_email_subject',
			'reminder_email_body',
			'second_reminder_email_subject',
			'second_reminder_email_body',
			'third_reminder_email_subject',
			'third_reminder_email_body',
			'params',
			'currency_code',
			'currency_symbol',
			'custom_field_ids',
			'custom_fields',
			'published',
			'send_first_reminder',
			'first_reminder_frequency',
			'send_second_reminder',
			'second_reminder_frequency',
			'send_third_reminder',
			'third_reminder_frequency',
			'payment_methods',
			'published',
			'members_discount_apply_for',
			'enable_coupon',
			'activate_waiting_list',
			'collect_member_information',
			'prevent_duplicate_registration',
			'activate_tickets_pdf',
			'ticket_start_number',
			'ticket_prefix',
			'attachment',
			'meta_keywords',
			'meta_description',
		];

		if (File::exists(JPATH_ADMINISTRATOR . '/components/com_eventbooking/data/exclude_fields.php'))
		{
			$excludeFields = require JPATH_ADMINISTRATOR . '/components/com_eventbooking/data/exclude_fields.php';

			$fieldsToUpdate = array_diff($fieldsToUpdate, $excludeFields);
		}

		if (File::exists(JPATH_ADMINISTRATOR . '/components/com_eventbooking/data/include_fields.php'))
		{
			$includeFields = require JPATH_ADMINISTRATOR . '/components/com_eventbooking/data/include_fields.php';

			$fieldsToUpdate = array_merge($fieldsToUpdate, $includeFields);
		}

		return $fieldsToUpdate;
	}

	/**
	 * Check to see if module should be shown on current page/view
	 *
	 * @param   string  $excludeFrom
	 *
	 * @return bool
	 */
	public static function needToShowModule(string $pages = ''): bool
	{
		if (strlen($pages))
		{
			$showIfMatch = true;

			if (strpos($pages, '!') !== false)
			{
				$pages       = substr($pages, 1);
				$showIfMatch = false;
			}

			// $pages should have value in this syntax com_eventbooking: category,event; com_content: article,categories or category,event
			$components = [];
			$pages      = array_map('trim', explode(';', $pages));

			foreach ($pages as $page)
			{
				$pos = strpos($page, ':');

				if (strpos($page, ':') !== false)
				{
					$component = trim(substr($page, 0, $pos));
					$views     = substr($page, $pos + 1);
				}
				else
				{
					$component = 'com_eventbooking';
					$views     = $page;
				}

				$components[$component] = array_map('trim', explode(',', $views));
			}

			$option = Factory::getApplication()->input->getCmd('option', '');
			$view   = Factory::getApplication()->input->getCmd('view', '');

			if (in_array($view, $components[$option] ?? []))
			{
				return $showIfMatch;
			}
		}

		return true;
	}
}
