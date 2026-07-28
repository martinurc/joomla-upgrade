<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var array                     $rows
 * @var RADConfig                 $config
 * @var \Joomla\Registry\Registry $params
 * @var int                       $numberEventPerRow
 * @var int                       $itemId
 *
 */

Factory::getApplication()->getDocument()->addStyleSheet(
	Uri::root(true) . '/media/com_eventbooking/assets/css/eventgrid.min.css',
	['version' => EventbookingHelper::getInstalledVersion()]
);

$span = 'span' . intval(12 / $numberEventPerRow);

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$clearfix        = $bootstrapHelper->getClassMapping('clearfix');
$span            = $bootstrapHelper->getClassMapping($span);

if ($params->get('show_register_buttons', 1) && $config->multiple_booking)
{
	$deviceType = EventbookingHelper::getDeviceType();

	if ($deviceType == 'mobile')
	{
		EventbookingHelperJquery::colorbox('eb-colorbox-addcart', '100%', '450px', 'false', 'false');
	}
	else
	{
		EventbookingHelperJquery::colorbox('eb-colorbox-addcart', '800px', 'false', 'false', 'false', 'false');
	}

	$baseAjaxUrl = Uri::root(true) . '/index.php?option=com_eventbooking' . EventbookingHelper::getLangLink() . '&time=' . time();
	Factory::getApplication()->getDocument()->addScriptDeclaration('var EBBaseAjaxUrl = "' . $baseAjaxUrl . '";');
}

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

if (count($rows) > 0)
{
	// Prepare display data
	EventbookingHelper::callOverridableHelperMethod('Data', 'prepareDisplayData', [$rows, 0, $config, $itemId]);

	if (EventbookingHelper::isValidMessage($params->get('pre_text')))
	{
		echo $params->get('pre_text');
	}
?>
	<div class="<?php echo $rowFluid . ' ' . $clearfix; ?> eb-events-grid-items"<?php echo $inlineStyles; ?>>
		<?php
		$rowCount = 0;

		foreach ($rows as $i => $item)
		{
			if ($i % $numberEventPerRow == 0)
			{
				$rowCount++;
			}

			$layoutData = [
				'item'   => $item,
				'params' => $params,
				'Itemid' => $itemId,
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
<?php
}
else
{
?>
    <div class="eb_empty"><?php echo Text::_('EB_NO_UPCOMING_EVENTS') ?></div>
<?php
}