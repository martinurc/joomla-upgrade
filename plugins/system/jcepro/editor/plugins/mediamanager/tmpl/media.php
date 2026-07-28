<?php
/**
 * @package     JCE
 * @subpackage  Editor
 *
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

foreach (array('flash', 'audio', 'video', 'aggregator') as $tpl) {
    echo $this->loadTemplate($tpl);
}
