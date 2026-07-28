<?php
/**
 * @package     Joomla.RAD
 * @subpackage  View
 * @author      Ossolution Team
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Layout variables
 *
 * @var stdClass $row
 */

$saveOrder = $this->state->filter_order == 'tbl.ordering';
$iconClass = $saveOrder ? '' : ' inactive tip-top hasTooltip';
?>
<span class="sortable-handler<?php echo $iconClass ?>"><i class="icon-menu"></i></span>
<?php if ($saveOrder) : ?>
	<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $row->ordering ?>" class="width-20 text-area-order "/>
<?php endif; ?>
