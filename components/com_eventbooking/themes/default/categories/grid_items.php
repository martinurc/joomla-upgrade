<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$clearfix        = $bootstrapHelper->getClassMapping('clearfix');

$numberColumns   = (int) $this->params->get('number_columns', 3) ?: 3;
$span            = 'span' . intval(12 / $numberColumns);
$span            = $bootstrapHelper->getClassMapping($span);

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
	<<?php echo $hTag; ?> class="eb-sub-categories-heading"><?php echo Text::_('EB_SUB_CATEGORIES'); ?></<?php echo $hTag; ?>>
<?php
}

$cssVariables = [];

if ($this->params->get('hover_bg_color'))
{
	$cssVariables[] = '--eb-category-box-hover-bg-color: '.$this->params->get('hover_bg_color');
}

if ($this->params->get('hover_color'))
{
	$cssVariables[] = '--eb-category-box-hover-color: '.$this->params->get('hover_color');
}

if (count($cssVariables))
{
	$inlineStyles = ' style="' . implode(';', $cssVariables) . '"';
}
else
{
	$inlineStyles = '';
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
?>
<div class="<?php echo $rowFluid . ' ' . $clearfix; ?> eb-categories-grid-items"<?php echo $inlineStyles; ?>>
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

	$rowCount = 0;

	foreach ($categories as $i => $item)
	{
		if ($i % $numberColumns == 0)
		{
			$rowCount++;
		}

		if ($item->category_detail_url)
		{
			$url = $item->category_detail_url;
		}
		elseif ($itemId = EventbookingHelperRoute::getCategoriesMenuId($item->id))
		{
			$url = Route::_('index.php?option=com_eventbooking&view=categories&id=' . $item->id . '&Itemid=' . $itemId);
		}
		else
		{
			$url = Route::_(EventbookingHelperRoute::getCategoryRoute($item->id, $this->Itemid));
		}

		$item->url = $url;

		$layoutData = [
			'item'   => $item,
			'params' => $this->params,
		];

		if ($i >= $lazyLoadingStartIndex && $imgLoadingAttr)
		{
			$layoutData['imgLoadingAttr'] = $imgLoadingAttr;
		}
		?>
		<div class="<?php echo $span; ?> eb-grid-row-<?php echo $rowCount; ?>">
			<?php
			echo EventbookingHelperHtml::loadSharedLayout(
				'categorygrid/' . $this->params->get('category_item_layout', 'default') . '.php',
				$layoutData
			);
			?>
		</div>
		<?php
	}
	?>
</div>

