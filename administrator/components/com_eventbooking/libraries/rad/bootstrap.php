<?php
/**
 * Register the prefix so that the classes in RAD library can be auto-load
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

// define('EB_DEBUG', true);

// Special case for 32 bits
if (strtotime('2099-12-31 00:00:00') === false)
{
	define('EB_TBC_DATE', '2030-12-31 00:00:00');
}
else
{
	define('EB_TBC_DATE', '2099-12-31 00:00:00');
}

JLoader::registerPrefix('RAD', dirname(__FILE__));

$app = Factory::getApplication();

if (!$app->isClient('administrator'))
{
	JLoader::registerPrefix('EventbookingTable', JPATH_ADMINISTRATOR . '/components/com_eventbooking/table');
}

if (!$app->isClient('site'))
{
	JLoader::register('EventbookingHelper', JPATH_ROOT . '/components/com_eventbooking/helper/helper.php');
	JLoader::registerPrefix('EventbookingHelper', JPATH_ROOT . '/components/com_eventbooking/helper');
}

if ($app->isClient('api'))
{
	JLoader::registerPrefix('Eventbooking', JPATH_ADMINISTRATOR . '/components/com_eventbooking');
}
else
{
	JLoader::registerPrefix('Eventbooking', JPATH_BASE . '/components/com_eventbooking');
}

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_eventbooking/table');

require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/table/eventbooking.php';

require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/vendor/autoload.php';

if (!$app->isClient('site'))
{
	// Traits
	JLoader::register('EventbookingViewEvent', JPATH_ROOT . '/components/com_eventbooking/view/event.php');
	JLoader::register('EventbookingViewRegistrants', JPATH_ROOT . '/components/com_eventbooking/view/registrants.php');
	JLoader::register('EventbookingViewRegistrant', JPATH_ROOT . '/components/com_eventbooking/view/registrant.php');
	JLoader::register('EventbookingModelFilter', JPATH_ROOT . '/components/com_eventbooking/model/filter.php');
}
else
{
	JLoader::register('EventbookingModelMassmail', JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/massmail.php');
	JLoader::register('EventbookingModelCommonEvent', JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/common/event.php');
	JLoader::register('EventbookingModelCommonRegistrants', JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/common/registrants.php');
	JLoader::register(
		'EventbookingControllerCommonRegistrant',
		JPATH_ADMINISTRATOR . '/components/com_eventbooking/controller/common/registrant.php'
	);
}

JLoader::register('os_payments', JPATH_ROOT . '/components/com_eventbooking/payments/os_payments.php');
JLoader::register('os_payment', JPATH_ROOT . '/components/com_eventbooking/payments/os_payment.php');

if (EventbookingHelper::isJoomla5())
{
	JLoader::registerAlias('JDatabaseQuery', \Joomla\Database\DatabaseQuery::class);

	// Force autoload class to make it available for using
	class_exists('JDatabaseQuery');
}

/**
 * error_reporting(E_ALL);
 * ini_set('display_errors', 'On');
 **/

// Disable STRICT_TRANS_TABLES mode
if (EventbookingHelper::isJoomla4())
{
	$db = Factory::getDbo();
	$db->setQuery("SET sql_mode=(SELECT REPLACE(@@sql_mode,'STRICT_TRANS_TABLES',''));");
	$db->execute();
}

// Workaround for buggy PHP installation
if (!defined('CURL_SSLVERSION_TLSv1_2'))
{
	define('CURL_SSLVERSION_TLSv1_2', 6);
}