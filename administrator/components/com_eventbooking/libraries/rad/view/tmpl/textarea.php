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
 * @var int    $rows
 * @var int    $cols
 */
?>

<div class="control-group">
	<div class="control-label">
		<?php
		if (strlen($description ?? '') > 0)
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
		<textarea rows="<?php echo $rows; ?>" cols="<?php echo $cols; ?>" class="<?php echo $class ?? 'form-control'; ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>"><?php echo $this->item->{$name}; ?></textarea>
	</div>
</div>