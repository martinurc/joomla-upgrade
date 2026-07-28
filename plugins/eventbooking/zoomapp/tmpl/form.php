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

/**
 * Layout variables
 *
 * @var Joomla\Registry\Registry $params
 */
?>
<div class="control-group">
	<label class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel('zoom_meeting_id', Text::_('EB_MEETING_ID'), Text::_('EB_MEETING_ID_EXPLAIN')); ?>
	</label>
	<div class="controls">
		<input class="input-large form-control" type="text" name="zoom_meeting_id" id="zoom_meeting_id"
		       value="<?php
		       echo $params->get('zoom_meeting_id'); ?>"/>
	</div>
</div>
<div class="control-group">
	<label class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel('zoom_webinar_id', Text::_('EB_WEBINAR_ID'), Text::_('EB_WEBINAR_ID_EXPLAIN')); ?>
	</label>
	<div class="controls">
		<input class="input-large form-control" type="text" name="zoom_webinar_id" id="zoom_webinar_id"
		       value="<?php
		       echo $params->get('zoom_webinar_id'); ?>"/>
	</div>
</div>
