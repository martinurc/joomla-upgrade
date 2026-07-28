<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

/**
 * Layout variables
 *
 * @var stdClass $row
 * @var string   $name
 * @var ?string  $value
 * @var string   $attributes
 */

?>
<textarea name="<?php echo $name; ?>"
		  id="<?php echo $name; ?>"<?php echo $attributes; ?>><?php echo htmlspecialchars($value ?? '', ENT_COMPAT, 'UTF-8'); ?></textarea>
