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
 * @var string   $type
 * @var ?string  $value
 * @var string   $attributes
 */
?>
<input type="<?php echo $type; ?>"
	   name="<?php echo $name; ?>" id="<?php echo $name; ?>"
	   value="<?php echo htmlspecialchars((string) $value, ENT_COMPAT, 'UTF-8'); ?>"<?php echo $attributes; ?> />
