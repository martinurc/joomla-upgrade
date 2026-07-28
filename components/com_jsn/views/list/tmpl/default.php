<?php
/**
* @copyright    Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license        http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package        Easy Profile
* website        www.easy-profile.com
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Application\SiteApplication $app */
$app   = Factory::getApplication();
$input = $app->getInput();

// Disparar eventos con API de Joomla 5
$renderBeforeList = (array) $app->triggerEvent('renderBeforeList', [$this->items, $this->config]);
echo implode(' ', $renderBeforeList);
?>

<div class="jsn_list">
<?php if ($this->params->get('show_page_heading')) : ?>
    <div class="page-header">
        <h2><?php echo $this->params->get('page_heading'); ?></h2>
    </div>
<?php endif; ?>

<?php
if ($this->params->def('search_enabled', 0) && !($input->get('search', '0') && $this->params->def('search_hideform', 0))) {
    echo $this->loadTemplate('search');
}
?>

<div id="jsn_listresult">
<?php 
$renderBeforeResultList = (array) $app->triggerEvent('renderBeforeResultList', [$this->items, $this->config]);
echo implode(' ', $renderBeforeResultList);
?>

<?php
if ($this->items && count($this->items) > 0 && !($this->params->def('search_enabled', 0) && !$this->params->def('search_showuser', 0) && !$input->get('search', 0))) {
    if ($this->params->def('export', 0)) {
        $uri = Uri::getInstance();
        $uri->setVar('start', 0);
        $uri->setVar('limit', 0);
        $uri->setVar('layout', 'export');
        $uri->setVar('format', 'raw');
        ?>
        <div class="pull-right jsn-export">
            <a target="_blank" href="<?php echo $uri->toString(); ?>" class="btn">
                <i class="jsn-icon jsn-icon-share"></i> <?php echo Text::_('COM_JSN_EXPORT'); ?>
            </a>
        </div>
        <?php
    }
    if ($this->params->def('show_total', 1)) {
        echo '<div class="jsn-total"><span class="label label-warning">' . ($this->pagination->total ?? count($this->items)) . ' ' . Text::_('COM_JSN_MEMBERS') . '</span></div>';
    }
}

if (!isset($this->items) || !is_array($this->items) || empty($this->items)) {
    ?>
    <div class="alert alert-warning">
        <?php echo Text::_('COM_JSN_NORESULT'); ?>
    </div>
    <?php
}
?>

<?php if (($this->params->def('show_pagination', 1) == 2 || $this->params->def('show_pagination', 1) == 3) && isset($this->pagination) && $this->pagination->pagesTotal > 1) : ?>
    <div class="pagination" style="clear:both">
        <?php echo $this->pagination->getPagesLinks(); ?>
        <?php if ($this->params->def('show_pagination_results', 1)) : ?>
            <p class="counter"><?php echo $this->pagination->getPagesCounter(); ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="jsn-list">
<?php
$this->url_options = [];
$this->url_options['Itemid'] = $this->params->def('profile_menuid', '');
if ($this->params->def('profile_back', 1)) {
    $this->url_options['back'] = 1;
}

$cols = $this->params->def('num_columns', 1);
$cols = $cols > 0 ? $cols : 1;
$this->span = 12 / $cols;
$countUsers = 0;
global $JSNLIST_DISPLAYED_ID;

if (is_array($this->items)) {
    foreach ($this->items as $item) {
        $itemId = is_object($item) ? ($item->id ?? null) : $item;
        if (!$itemId) continue;

        $JSNLIST_DISPLAYED_ID = $itemId;
        
        // Asignación segura del objeto usuario para la plantilla secundaria
        $this->user = JsnHelper::getUser($itemId);

        if (($countUsers % $cols) == 0) echo '<div class="jsn-l-row">';
        
        echo $this->loadTemplate('user');
        
        if (($countUsers % $cols) == ($cols - 1)) echo '</div>';
        $countUsers++;
    }
}
if (($countUsers % $cols) != 0) echo '</div>';
$JSNLIST_DISPLAYED_ID = false;
?>
</div>

<?php if (($this->params->def('show_pagination', 1) == 1 || $this->params->def('show_pagination', 1) == 3) && isset($this->pagination) && $this->pagination->pagesTotal > 1) : ?>
    <div class="pagination" style="clear:both">
        <?php if ($this->params->def('show_pagination_results', 1)) : ?>
            <p class="counter"><?php echo $this->pagination->getPagesCounter(); ?></p>
        <?php endif; ?>
        <?php echo $this->pagination->getPagesLinks(); ?>
    </div>
<?php endif; ?>

<?php 
$renderAfterResultList = (array) $app->triggerEvent('renderAfterResultList', [$this->items, $this->config]);
echo implode(' ', $renderAfterResultList);
?>
</div>
</div>

<?php 
$renderAfterList = (array) $app->triggerEvent('renderAfterList', [$this->items, $this->config]);
echo implode(' ', $renderAfterList);
?>