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
 * @var stdClass $speaker
 * @var string   $speakerContainerClass
 */

$rootUri               = Uri::root(true);
$bootstrapHelper       = EventbookingHelperBootstrap::getInstance();
$imageCircleClass      = $bootstrapHelper->getClassMapping('img-circle');
$speakerContainerClass = $speakerContainerClass ?? 'eb-speaker-container';
?>
<div class="<?php echo $speakerContainerClass; ?>">
	<?php
	if ($speaker->avatar)
	{
	?>
		<div class="eb-speaker-avatar">
			<?php
			if ($speaker->url)
			{
				?>
				<a href="<?php echo $speaker->url; ?>" class="eb-speaker-url">
					<img src="<?php echo $rootUri . '/' . $speaker->avatar; ?>" class="<?php echo $imageCircleClass; ?>" />
				</a>
				<?php
			}
			else
			{
			?>
				<img src="<?php echo $rootUri . '/' . $speaker->avatar; ?>" class="<?php echo $imageCircleClass; ?>" />
			<?php
			}
			?>
		</div>
	<?php
	}

	if ($speaker->url)
	{
	?>
		<h4 class="eb-speaker-name">
			<a href="<?php echo $speaker->url; ?>" class="eb-speaker-url">
				<?php echo Text::_($speaker->name); ?>
			</a>
		</h4>
	<?php
	}
	else
	{
	?>
		<h4 class="eb-speaker-name"><?php echo Text::_($speaker->name); ?></h4>
	<?php
	}

	if ($speaker->title)
	{
	?>
		<h5 class="eb-speaker-title"><?php echo Text::_($speaker->title); ?></h5>
	<?php
	}
	?>
	<p class="eb-speaker-description">
		<?php echo $speaker->description; ?>
	</p>
</div>
