<?php
/**
 * @package     Joomla.RAD
 * @subpackage  View
 * @author      Ossolution Team
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

?>
<input type="hidden" name="task" value=""/>
<input type="hidden" name="boxchecked" value="0"/>
<input type="hidden" name="filter_order" value="<?php echo $this->state->filter_order; ?>"/>
<input type="hidden" name="filter_order_Dir" value="<?php echo $this->state->filter_order_Dir; ?>"/>
<input type="hidden" id="filter_full_ordering" name="filter_full_ordering" value=""/>
<?php
echo HTMLHelper::_('form.token'); ?>
