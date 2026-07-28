<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

/**
 * Layout variables
 *
 * @var stdClass[] $categories
 * @var int       $Itemid
 */

$html = [];

foreach ($categories as $category)
{
	$categoryUrl  = Route::_(EventbookingHelperRoute::getCategoryRoute($category->id, $Itemid));
	$html[] = '<a href="' . $categoryUrl . '">' . $category->name . '</a>';
}

echo implode(', ', $html);

