<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$document = Factory::getApplication()->getDocument();
$rootUri  = Uri::root(true);

$document->addScript($rootUri . '/media/com_eventbooking/assets/js/responsive-auto-height.min.js', [], ['defer' => true]);

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper   = EventbookingHelperBootstrap::getInstance();
$rowFluidClass     = $bootstrapHelper->getClassMapping('row-fluid');
$clearfixClass     = $bootstrapHelper->getClassMapping('clearfix');

$numberColumns    = (int) $this->params->get('number_columns', 3) ?: 3;
$span             = 'span' . intval(12 / $numberColumns);
$span             = $bootstrapHelper->getClassMapping($span);
$numberCategories = count($this->items);

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
	<div id="eb-events" class="<?php echo $rowFluidClass . ' ' . $clearfixClass; ?> eb-columns-layout-container">
		<?php
		$rowCount = 0;

		for ($i = 0 ; $i < $numberCategories ; $i++)
		{
			$category = $this->items[$i];

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

			if ($i % $numberColumns == 0)
			{
				$rowCount++;
				$newRowClass = ' eb-first-child-of-new-row';
			}
			else
			{
				$newRowClass = '';
			}

			$cssClasses = ['eb-category-wrapper', $clearfixClass];
			?>
			<div class="<?php echo $span . $newRowClass; ?> eb-row-<?php echo $rowCount; ?>">
                <div class="<?php echo implode(' ', $cssClasses); ?>">
                    <div class="eb-box-heading">
                        <h2 class="eb-category-title">
                            <a href="<?php echo $categoryLink; ?>" class="eb-category-title-link">
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
                        </h2>
                    </div>
                    <?php
					if($category->description || $category->image)
					{
					?>
                        <div class="eb-description <?php echo $clearfixClass; ?>">
                            <?php
							if ($category->image && file_exists(JPATH_ROOT . '/images/com_eventbooking/categories/thumb/' . basename($category->image)))
							{
							?>
                                <a href="<?php echo $categoryLink ?>"><img<?php if ($imgLoadingAttr && $i >= $lazyLoadingStartIndex) echo $imgLoadingAttr; ?> src="<?php echo $rootUri . '/images/com_eventbooking/categories/thumb/' . basename($category->image); ?>" alt="<?php echo $category->image_alt ?: $category->name; ?>" class="eb-thumb-left" /></a>
                            <?php
							}

							echo $category->description;
							?>
                        </div>
                    <?php
					}
					?>
                </div>
			</div>
			<?php
		}
		?>
	</div>
<?php
$equalHeightScript[] = 'window.addEventListener("load", function() {';

for ($i = 1; $i <= $rowCount; $i++)
{
	$equalHeightScript[] = 'new ResponsiveAutoHeight(".eb-row-' . $i . ' .eb-description");';
}

$equalHeightScript[] = '});';

$document->addScriptDeclaration(implode("\r\n", $equalHeightScript));
