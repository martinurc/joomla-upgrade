<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

/**
 * Layout variables
 *
 * @var array $attachments
 */

$config = EventbookingHelper::getConfig();
$attachmentRootLink = Uri::root(true) . '/' . ($config->attachments_path ?: 'media/com_eventbooking') . '/';

for ($i = 0, $n = count($attachments); $i < $n; $i++)
{
	$attachment = $attachments[$i];

	if ($i > 0)
	{
		echo '<br />';
	}
?>
	<a href="<?php echo $attachmentRootLink . $attachment; ?>" target="_blank"><?php echo $attachment; ?></a>
<?php
}