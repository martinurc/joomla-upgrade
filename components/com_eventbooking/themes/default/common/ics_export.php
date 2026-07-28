<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
?>
<a href="<?php echo Route::_('&format=ics&limitstart=0'); ?>" class="eb-ical-export-link <?php echo $bootstrapHelper->getClassMapping('pull-right'); ?>"><i class="<?php echo $bootstrapHelper->getClassMapping('icon-download'); ?>"></i><?php echo Text::_('EB_ICAL_EXPORT'); ?></a>

