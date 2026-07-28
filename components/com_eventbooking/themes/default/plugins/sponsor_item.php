<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var stdClass $sponsor
 * @var string   $sponsorContainerClass
 */

$rootUri               = Uri::root(true);
$bootstrapHelper       = EventbookingHelperBootstrap::getInstance();
$imageCircleClass      = $bootstrapHelper->getClassMapping('img-circle');
$sponsorContainerClass = $sponsorContainerClass ?? 'eb-sponsor-container';
?>
<div class="<?php echo $sponsorContainerClass; ?>">
	<?php
	if ($sponsor->name)
	{
		if ($sponsor->website)
		{
		?>
			<h4 class="eb-speaker-name">
				<a href="<?php echo $sponsor->website; ?>" class="eb-speaker-url">
					<?php echo Text::_($sponsor->name); ?>
				</a>
			</h4>
		<?php
		}
		else
		{
		?>
			<h4 class="eb-speaker-name"><?php echo Text::_($sponsor->name); ?></h4>
		<?php
		}
	}

	if ($sponsor->logo)
	{
	?>
		<div class="eb-sponsor-logo">
			<?php
			if ($sponsor->website)
			{
			?>
				<a href="<?php echo $sponsor->website; ?>" class="eb-sponsor-url">
					<img src="<?php echo $rootUri . '/' . $sponsor->logo; ?>" class="<?php echo $imageCircleClass; ?>" />
				</a>
			<?php
			}
			else
			{
			?>
				<img src="<?php echo $rootUri . '/' . $sponsor->logo; ?>" class="<?php echo $imageCircleClass; ?>" />
			<?php
			}
			?>
		</div>
		<?php
	}
	?>
</div>