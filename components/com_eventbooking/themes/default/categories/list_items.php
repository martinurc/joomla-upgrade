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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$clearfixClass   = $bootstrapHelper->getClassMapping('clearfix');

if ($this->params->get('thumb_position', 'left') === 'left')
{
	$thumbClass = $bootstrapHelper->getClassMapping('eb-thumb-left pull-left');
}
else
{
	$thumbClass = $bootstrapHelper->getClassMapping('eb-thumb-right pull-right');
}

if ($this->params->get('image_lazy_loading', 'lazy'))
{
	$imgLoadingAttr = ' loading="lazy"';
}
else
{
	$imgLoadingAttr = '';
}

$lazyLoadingStartIndex = $this->params->get('image_lazy_loading_start_index', 0);

$rootUri = Uri::root(true);

if ($this->categoryId && $this->params->get('show_sub_categories_text'))
{
	if ($this->input->getInt('hmvc_call'))
	{
		$hTag = 'h3';
	}
	else
	{
		$hTag = 'h2';
	}
?>
	<<?php echo $hTag; ?> class="eb-sub-categories-heading"><?php echo Text::_('EB_SUB_CATEGORIES'); ?></<?php echo $hTag; ?>
<?php
}
?>
<div class="eb-categories-list-items">
	<?php
	if (isset($this->categories))
	{
		// In this case, the layout is loaded from category view to display sub-categories
		$categories = $this->categories;
	}
	else
	{
		$categories = $this->items;
	}

	$itemDisplayIndex = 0;

	foreach ($categories as $category)
	{
		if ($category->category_detail_url)
		{
			$categoryLink = $category->category_detail_url;
		}
		elseif ($itemId = EventbookingHelperRoute::getCategoriesMenuId($category->id))
		{
			$categoryLink = Route::_('index.php?option=com_eventbooking&view=categories&id=' . $category->id . '&Itemid=' . $itemId);
		}
		else
		{
			$categoryLink = Route::_(EventbookingHelperRoute::getCategoryRoute($category->id, $this->Itemid));
		}
	?>
	    <div class="eb-category-item">
		    <h3 class="eb-category-title-container">
	            <a href="<?php echo $categoryLink; ?>" class="eb-category-link">
	                <?php echo $category->name; ?>
	            </a>
			    <?php
			    if ($this->config->show_number_events)
			    {
				?>
				    <span class="<?php echo $bootstrapHelper->getClassMapping('badge badge-info'); ?>"><?php echo $category->total_events ;?> <?php echo $category->total_events == 1 ? Text::_('EB_EVENT') :  Text::_('EB_EVENTS') ; ?></span>
				<?php
			    }
			    ?>
		    </h3>
		<?php
		if($category->description || $category->image)
		{
		?>
			<div class="eb-category-description <?php echo $clearfixClass; ?>">
				<?php
				if ($category->image && file_exists(JPATH_ROOT . '/images/com_eventbooking/categories/thumb/' . basename($category->image)))
				{
				?>
					<a href="<?php echo $categoryLink ?>">
						<img<?php if ($imgLoadingAttr && $itemDisplayIndex >= $lazyLoadingStartIndex) echo $imgLoadingAttr; ?> src="<?php echo $rootUri . '/images/com_eventbooking/categories/thumb/' . basename($category->image); ?>" alt="<?php echo $category->image_alt ?: $category->name; ?>" class="eb-category-thumb <?php echo $thumbClass; ?>" />
					</a>
				<?php
				}

				echo $category->description;
				?>
			</div>
		<?php
		}
		?>
		</div>
	<?php
		$itemDisplayIndex++;
	}
	?>
</div>