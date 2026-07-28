<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

// The component types constants - for better readability 
define('RSFORM_FIELD_TEXTBOX', 1);
define('RSFORM_FIELD_TEXTAREA', 2);
define('RSFORM_FIELD_SELECTLIST', 3);
define('RSFORM_FIELD_CHECKBOXGROUP', 4);
define('RSFORM_FIELD_RADIOGROUP', 5);
define('RSFORM_FIELD_CALENDAR', 6);
define('RSFORM_FIELD_JQUERY_CALENDAR', 411);
define('RSFORM_FIELD_RANGE_SLIDER', 355);
define('RSFORM_FIELD_BUTTON', 7);
define('RSFORM_FIELD_CAPTCHA', 8);
define('RSFORM_FIELD_HASHCASH', 16);
define('RSFORM_FIELD_FILEUPLOAD', 9);
define('RSFORM_FIELD_FREETEXT', 10);
define('RSFORM_FIELD_HIDDEN', 11);
define('RSFORM_FIELD_SUBMITBUTTON', 13);
define('RSFORM_FIELD_PASSWORD', 14);
define('RSFORM_FIELD_TICKET', 15);
define('RSFORM_FIELD_PAGEBREAK', 41);
define('RSFORM_FIELD_BIRTHDAY', 211);
define('RSFORM_FIELD_GMAPS', 212);
define('RSFORM_FIELD_PREVIEW', 200);
define('RSFORM_FIELD_PHONENUMBER', 201);
define('RSFORM_FIELD_SURVEYTABLE', 202);

// Submission editing constants
define('RSFORM_DIR_CAPTION', 0);
define('RSFORM_DIR_INPUT', 1);
define('RSFORM_DIR_REQUIRED', 2);
define('RSFORM_DIR_NAME', 3);
define('RSFORM_DIR_VALIDATION', 4);
define('RSFORM_DIR_DESCRIPTION', 5);
define('RSFORM_DIR_ID', 6);

// Submissions Directory headers
define('RSFORM_STATIC_DATESUBMITTED', -1);
define('RSFORM_STATIC_USERIP', -2);
define('RSFORM_STATIC_USERNAME', -3);
define('RSFORM_STATIC_USERID', -4);
define('RSFORM_STATIC_LANGUAGE', -5);
define('RSFORM_STATIC_CONFIRMED', -6);
define('RSFORM_STATIC_CONFIRMEDIP', -8);
define('RSFORM_STATIC_CONFIRMEDDATE', -9);
define('RSFORM_STATIC_SUBMISSIONID', -7);