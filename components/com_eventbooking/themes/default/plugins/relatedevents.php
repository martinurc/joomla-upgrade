<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

Factory::getApplication()->getDocument()->addStyleSheet(
	Uri::root(true) . '/media/com_eventbooking/assets/css/eventgrid.min.css',
	['version' => EventbookingHelper::getInstalledVersion()]
);

/**
 * Layout variables
 *
 * @var array                     $events
 * @var \Joomla\Registry\Registry $params
 */

$config          = EventbookingHelper::getConfig();
$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$clearfix        = $bootstrapHelper->getClassMapping('clearfix');

$numberColumns = $params->get('number_columns') ?: (int) $config->get('number_events_per_row', 3);
$span          = 'span' . intval(12 / $numberColumns);
$span          = $bootstrapHelper->getClassMapping($span);

$cssVariables = [];

if ($params->get('category_bg_color'))
{
	$cssVariables[] = '--eb-grid-default-main-category-color: '.$params->get('category_bg_color');
}

if ($params->get('event_datetime_color'))
{
	$cssVariables[] = '--eb-grid-default-datetime-color: '.$params->get('event_datetime_color');
}

if (count($cssVariables))
{
	$inlineStyles = ' style="' . implode(';', $cssVariables) . '"';
}
else
{
	$inlineStyles = '';
}

$Itemid = EventbookingHelper::getItemid();
?>
<div class="<?php echo $rowFluid . ' ' . $clearfix; ?> eb-events-grid-items"<?php echo $inlineStyles; ?>>
	<?php
	$rowCount = 0;

	foreach ($events as $i => $item)
	{
		if ($i % $numberColumns == 0)
		{
			$rowCount++;
		}

		$layoutData = [
			'item'   => $item,
			'params' => $params,
			'Itemid' => $Itemid,
		];
		?>
		<div class="<?php echo $span; ?> eb-events-grid-row-<?php echo $rowCount; ?>">
			<?php
			echo EventbookingHelperHtml::loadSharedLayout(
				'eventgrid/' . $params->get('event_item_layout', 'default') . '.php',
				$layoutData
			);
			?>
		</div>
		<?php
	}
	?>
</div>