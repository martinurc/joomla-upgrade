<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

if (version_compare(JVERSION, '4.0.0', 'ge')) {
    require_once __DIR__ . '/eventbooking.j4.php';
} else {
    require_once __DIR__ . '/eventbooking.j3.php';
}
