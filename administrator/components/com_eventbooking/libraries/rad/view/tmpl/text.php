<?php
/**
 * @package     Joomla.RAD
 * @subpackage  View
 * @author      Ossolution Team
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Layout variables
 *
 * @var string $name
 * @var string $title
 * @var string $description
 * @var string $class
 */

$type = $type ?? 'text';
$attributes = $attributes ?? [];
?>

<div class="control-group">
	<div class="control-label">
		<?php
			if (strlen($description) > 0)
			{
				echo EventbookingHelperHtml::getFieldLabel($name, Text::_($title), Text::_($description));
			}
			else
			{
				echo Text::_($title);
			}
		?>
	</div>
	<div class="controls">
		<input class="<?php echo $class; ?>" type="<?php echo $type; ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo $this->item->{$name};?>"<?php echo $this->getAttributesString($attributes); ?> />
	</div>
</div>