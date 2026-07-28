<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$clearfix        = $bootstrapHelper->getClassMapping('clearfix');

$numberColumns = $this->params->get('number_columns') ?: (int) $this->config->get('number_events_per_row', 3);
$span          = 'span' . intval(12 / $numberColumns);
$span          = $bootstrapHelper->getClassMapping($span);

$cssVariables = [];

if ($this->params->get('category_bg_color'))
{
	$cssVariables[] = '--eb-grid-default-main-category-color: '.$this->params->get('category_bg_color');
}

if ($this->params->get('event_datetime_color'))
{
	$cssVariables[] = '--eb-grid-default-datetime-color: '.$this->params->get('event_datetime_color');
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
<div class="<?php echo $rowFluid . ' ' . $clearfix; ?> eb-events-grid-items"<?php echo $inlineStyles; ?>>
	<?php
	$rowCount = 0;

	foreach ($this->items as $i => $item)
	{
		if ($i % $numberColumns == 0)
		{
			$rowCount++;
		}

		$layoutData = [
			'item'   => $item,
			'params' => $this->params,
			'Itemid' => $this->Itemid,
		];

		if ($i >= $lazyLoadingStartIndex && $imgLoadingAttr)
		{
			$layoutData['imgLoadingAttr'] = $imgLoadingAttr;
		}
		?>
			<div class="<?php echo $span; ?> eb-events-grid-row-<?php echo $rowCount; ?>">
				<?php
				echo EventbookingHelperHtml::loadSharedLayout(
					'eventgrid/' . $this->params->get('event_item_layout', 'default') . '.php',
					$layoutData
				);
				?>
			</div>
		<?php
	}
	?>
</div>

