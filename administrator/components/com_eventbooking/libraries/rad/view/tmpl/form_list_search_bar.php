<?php
/**
 * @package     Joomla.RAD
 * @subpackage  View
 * @author      Ossolution Team
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Layout variables
 *
 * @var string $filterSearchLabel
 * @var string $filterSearchDescription
 */

$isJoomla4 = version_compare(JVERSION, '4.0.0', '>=');
?>
<div class="filter-search btn-group pull-left">
	<label for="filter_search" class="element-invisible"><?php echo Text::_($filterSearchLabel);?></label>
	<input type="text" name="filter_search" id="filter_search" inputmode="search" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->filter_search); ?>" class="hasTooltip form-control" title="<?php echo HTMLHelper::tooltipText($filterSearchDescription); ?>" />
</div>
<div class="btn-group pull-left">
	<button type="submit" class="btn<?php if ($isJoomla4) echo ' btn-primary'; ?> hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
	<button type="button" class="btn<?php if ($isJoomla4) echo ' btn-primary'; ?> hasTooltip" title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();"><span class="icon-remove"></span></button>
</div>