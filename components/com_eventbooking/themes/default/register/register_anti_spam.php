<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die ;
?>
<input type="text" name="<?php echo $this->config->get('honeypot_fieldname', 'eb_my_own_website_name'); ?>" value="" autocomplete="off" class="<?php echo $this->config->get('honeypot_field_css_class', 'eb-invisible-to-visitors'); ?>" />
<input type="hidden" name="<?php echo EventbookingHelper::getHashedFieldName(); ?>" value="<?php echo time(); ?>" />
