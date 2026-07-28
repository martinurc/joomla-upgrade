<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Router\Route;

/**
 * Layout variables
 *
 * @var stdClass $location
 * @var int       $Itemid
 */

if ((float) $location->lat != 0
	|| (float) $location->long != 0
	|| EventbookingHelper::isValidMessage($location->description))
{
	if ($location->image || EventbookingHelper::isValidMessage($location->description))
	{
	?>
		<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=map&location_id=' . $location->id . '&Itemid=' . $Itemid); ?>"><?php echo $location->name; ?></a>
	<?php
	}
	else
	{
	?>
		<a href="<?php echo Route::_('index.php?option=com_eventbooking&view=map&location_id=' . $location->id . '&Itemid=' . $Itemid . '&tmpl=component'); ?>" class="eb-colorbox-map"><?php echo $location->name; ?></a>
	<?php
	}
}
else
{
	echo $location->name;
}