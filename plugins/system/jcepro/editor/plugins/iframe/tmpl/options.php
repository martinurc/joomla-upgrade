<?php
/**
 * @package     JCE
 * @subpackage  Editor
 *
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

echo '<div class="options_description">' . Text::sprintf('WF_IFRAME_OPTIONS_DESC', implode(', ', $this->plugin->getAggregatorOptions())) . '</div>';
echo $this->plugin->getAggregatorTemplate();
