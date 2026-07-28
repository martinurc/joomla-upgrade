<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var stdClass                 $item
 * @var Joomla\Registry\Registry $params
 */

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$config          = EventbookingHelper::getConfig();
$clearfix        = $bootstrapHelper->getClassMapping('clearfix');

if ($item->image && file_exists(JPATH_ROOT . '/images/com_eventbooking/categories/thumb/' . basename($item->image)))
{
	$imageExist = true;
	$itemClass  = '';
}
else
{
	$imageExist = false;
	$itemClass  = ' eb-category-overlay-no-image';
}
?>
<div class="eb-category-item eb-category-item-grid-overlay<?php echo $itemClass; ?>">
	<?php
		if ($imageExist)
		{
		?>
			<a href="<?php echo $item->url; ?>" class="eb-category-thumb-link">
				<img<?php if (!empty($imgLoadingAttr)) echo $imgLoadingAttr; ?> src="<?php echo Uri::root(true) . '/images/com_eventbooking/categories/thumb/' . basename($item->image); ?>" alt="<?php echo $item->image_alt ?: $item->name; ?>" class="eb-category-thumb" />
			</a>
		<?php
		}
	?>
	<div class="eb-category-information <?php echo $clearfix; ?>">
		<a href="<?php echo $item->url; ?>" class="eb-category-link">
			<?php echo $item->name; ?>
		</a>
		<?php
		if ($config->show_number_events)
		{
		?>
			<br />
			<span class="<?php echo $bootstrapHelper->getClassMapping('badge badge-info'); ?>"><?php echo $item->total_events ;?> <?php echo $item->total_events == 1 ? Text::_('EB_EVENT') :  Text::_('EB_EVENTS') ; ?></span>
		<?php
		}
		?>
	</div>
</div>