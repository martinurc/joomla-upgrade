<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var array $sponsors
 */

$rootUri               = Uri::root(true);
$config                = EventbookingHelper::getConfig();
$bootstrapHelper       = EventbookingHelperBootstrap::getInstance();
$numberColumns         = $config->get('number_sponsors_per_row', 4);
$numberSponsors        = count($sponsors);
$count                 = 0;
$span                  = 'span' . intval(12 / $numberColumns);
$span                  = $bootstrapHelper->getClassMapping($span);
$rowFluidClass         = $bootstrapHelper->getClassMapping('row-fluid');
$imageCircleClass      = $bootstrapHelper->getClassMapping('img-circle');
$sponsorContainerClass = $span . ' eb-sponsor-container';
?>
<div id="eb-sponsors-list" class="<?php echo $rowFluidClass; ?> clearfix">
	<?php
	for ($i = 0 , $n = count($sponsors) ;  $i < $n ; $i++)
	{
		$count++;
		$sponsor = $sponsors[$i] ;

		echo EventbookingHelperHtml::loadCommonLayout(
			'plugins/sponsor_item.php',
			['sponsor' => $sponsor, 'sponsorContainerClass' => $sponsorContainerClass]
		);

		if ($count % $numberColumns == 0 && $count < $numberSponsors)
		{
		?>
			</div>
			<div class="clearfix <?php echo $rowFluidClass; ?>">
		<?php
		}
	}
	?>
</div>