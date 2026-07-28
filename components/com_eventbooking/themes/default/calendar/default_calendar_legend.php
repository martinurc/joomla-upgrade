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
 * -----------------
 * @var   array    $eventIds
 */

$categories      = EventbookingHelper::getCategories($eventIds);
$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
?>
<div id="eb-calendar-legend" class="<?php echo $bootstrapHelper->getClassMapping('clearfix'); ?>">
	<ul>
		<?php
		foreach ($categories as $category)
		{
		?>
			<li>
				<span class="eb-category-legend-color" style="background: #<?php echo $category->color_code; ?>"></span>
				<a href="<?php echo Route::_(EventbookingHelperRoute::getCategoryRoute($category->id, $this->Itemid)); ?>"><?php echo $category->name; ?></a>
			</li>
		<?php
		}
		?>
	</ul>
</div>