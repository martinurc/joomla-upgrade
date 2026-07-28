<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * Layout variables
 *
 * @var EventbookingTableEvent $row
 * @var Registry               $params
 */
?>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('http_enable', Text::_('EB_HTTP_ENABLE'), Text::_('EB_HTTP_ENABLE_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<?php echo EventbookingHelperHtml::getBooleanInput('http_enable', $params->get('http_enable', 0)); ?>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('http_url', Text::_('EB_HTTP_URL'), Text::_('EB_HTTP_URL_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<input type="url" name="http_url" value="<?php echo $params->get('http_url', ''); ?>" class="form-control input-xxlarge">
	</div>
</div>

