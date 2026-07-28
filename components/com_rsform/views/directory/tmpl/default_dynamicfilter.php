<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
?>
<div id="rsfp-directory-dynamic-filter-container-<?php echo $this->fieldId; ?>" class="rsfp-directory-dynamic-filter-container">
	<label for="<?php echo $this->fieldId; ?>"><?php echo $this->escape($this->fieldLabel); ?></label>
	<?php echo HTMLHelper::_('select.genericlist', $this->fieldValues, 'filter_dynamicfilter[' . $this->fieldName . ']', array('data-directory-change' => 'submit', 'class' => 'form-select'), 'value', 'text', $this->selectedValue, $this->fieldId); ?>
</div>