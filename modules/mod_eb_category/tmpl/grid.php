<?php
/**
 * @package        Joomla
 * @subpackage     Event Booking
 * @author         Tuan Pham Ngoc
 * @copyright      Copyright (C) 2010 - 2024 Ossolution Team
 * @license        GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var array                    $rows
 * @var Joomla\Registry\Registry $params
 * @var RADConfig                $config
 * @var int                      $itemId
 */

Factory::getApplication()->getDocument()->addStyleSheet(
	Uri::root(true) . '/media/com_eventbooking/assets/css/categorygrid.min.css',
	['version' => EventbookingHelper::getInstalledVersion()]
);

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$clearfix        = $bootstrapHelper->getClassMapping('clearfix');

$numberColumns   = (int) $params->get('number_columns', 3) ?: 3;
$span            = 'span' . intval(12 / $numberColumns);
$span            = $bootstrapHelper->getClassMapping($span);

$cssVariables = [];

if ($params->get('hover_bg_color'))
{
	$cssVariables[] = '--eb-category-box-hover-bg-color: '.$params->get('hover_bg_color');
}

if ($params->get('hover_color'))
{
	$cssVariables[] = '--eb-category-box-hover-color: '.$params->get('hover_color');
}

if (count($cssVariables))
{
	$inlineStyles = ' style="' . implode(';', $cssVariables) . '"';
}
else
{
	$inlineStyles = '';
}
?>
<div class="<?php echo $rowFluid . ' ' . $clearfix; ?> eb-categories-grid-items"<?php echo $inlineStyles; ?>>
	<?php

	$rowCount = 0;

	foreach ($rows as $i => $item)
	{
		if ($i % $numberColumns == 0)
		{
			$rowCount++;
		}

		if ($item->category_detail_url)
		{
			$url = $item->category_detail_url;
		}
		elseif ($Itemid = EventbookingHelperRoute::getCategoriesMenuId($item->id))
		{
			$url = Route::_('index.php?option=com_eventbooking&view=categories&id=' . $item->id . '&Itemid=' . $Itemid);
		}
		else
		{
			$url = Route::_(EventbookingHelperRoute::getCategoryRoute($item->id, $itemId));
		}

		$item->url = $url;

		$layoutData = [
			'item'   => $item,
			'params' => $params,
		];
		?>
		<div class="<?php echo $span; ?> eb-grid-row-<?php echo $rowCount; ?>">
			<?php
			echo EventbookingHelperHtml::loadSharedLayout(
				'categorygrid/' . $params->get('category_item_layout', 'default') . '.php',
				$layoutData
			);
			?>
		</div>
		<?php
	}
	?>
</div>


