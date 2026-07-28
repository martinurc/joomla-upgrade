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
use Joomla\CMS\Uri\Uri;

/**
 * Layout variable
 *
 * @var array $images
 */

Factory::getApplication()->getDocument()->addScriptDeclaration("
    document.addEventListener('DOMContentLoaded', function () {
        baguetteBox.run('.gallery', {});
    });	
");
?>
<div class="gallery">
	<?php
	$rootUrl  = Uri::root(true);

	foreach ($images as $image)
	{
		$image->image = EventbookingHelperHtml::getCleanImagePath($image->image);
		$title        = $image->title;
		$filename     = basename($image->image);
		$thumbPath    = substr($image->image, 0, strlen($image->image) - strlen($filename));
		$thumb        = $rootUrl . '/' . $thumbPath . '/thumbs/' . $filename;
		$largeImage   = $rootUrl . '/' . $image->image;
		?>
		<a href="<?php echo $largeImage ?>" data-caption="<?php echo $title; ?>">
			<img loading="lazy" src="<?php echo $thumb; ?>" alt="<?php echo $title; ?>">
		</a>
		<?php
	}
	?>
</div>
