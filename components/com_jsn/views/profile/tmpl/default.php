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
use Joomla\CMS\Router\Route;

/** @var \Joomla\CMS\Application\SiteApplication $app */
$app  = Factory::getApplication();
$user = Factory::getUser();

// Instanciar usuario de JSN
$userId     = $this->data->id ?? 0;
$this->user = JsnHelper::getUser($userId);

// Set Title & Pathway
$formatName = JsnHelper::getFormatName($this->data);
if (!empty($formatName)) {
    $this->document->setTitle($this->document->title . ' - ' . $formatName);
    $app->getPathway()->addItem($formatName);
}

// Avatar field
$avatar = method_exists($this->form, 'getField') ? $this->form->getField('avatar') : null;
?>

<!-- Main Container -->
<div class="jsn-p">

    <?php 
        $beforeProfile = (array) $app->triggerEvent('renderBeforeProfile', [$this->data, $this->config]);
        echo implode(' ', $beforeProfile);
    ?>

    <div class="jsn-p-opt">
        <?php if ($app->getInput()->get('back') === '1') : ?>
            <a class="btn btn-xs btn-default" href="#" onclick="window.history.back();return false;">
                <i class="jsn-icon jsn-icon-share"></i> <?php echo Text::_('COM_JSN_BACK'); ?>
            </a>
        <?php endif; ?>

        <?php if ($user->id == $this->data->id || $user->authorise('core.edit', 'com_users')) : ?>
            <?php $other_id = ($user->id == $this->data->id) ? '' : '&user_id=' . (int) $this->data->id; ?> 
            <a class="btn btn-xs btn-default" href="<?php echo Route::_('index.php?option=com_users&view=profile&layout=edit' . $other_id, false); ?>">
                <i class="jsn-icon jsn-icon-cog"></i> <?php echo Text::_('COM_USERS_EDIT_PROFILE'); ?>
            </a>
        <?php endif; ?>

        <?php if (!empty($this->config) && $this->config->get('profile_contact_btn', 1) && $user->id != $this->data->id) :
            $db    = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__contact_details'))
                ->where($db->quoteName('user_id') . ' = ' . (int) $this->data->id)
                ->where($db->quoteName('published') . ' = 1');
            $db->setQuery($query);
            
            $contactMenu = $app->getMenu()->getItems('link', 'index.php?option=com_contact&view=featured', true);
            $cItemid     = !empty($contactMenu->id) ? $contactMenu->id : '';
            
            if ($contact = $db->loadResult()) : ?>
                <a class="btn btn-xs btn-default" href="<?php echo Route::_('index.php?option=com_contact&view=contact&Itemid=' . $cItemid . '&id=' . $contact, false); ?>">
                    <i class="jsn-icon jsn-icon-paper-plane"></i> <?php echo Text::_('JGLOBAL_EMAIL'); ?>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php 
            $profileButtons = (array) $app->triggerEvent('renderProfileButtons', [$this->data, $this->config]);
            echo implode(' ', $profileButtons);
        ?>
    </div>

    <!-- Top Container -->
    <div class="jsn-p-top <?php echo ($avatar ? 'jsn-p-top-a' : ''); ?>">

        <!-- Avatar Container -->
        <?php if ($avatar) : ?> 
            <div class="jsn-p-avatar">
                <?php
                    if (is_object($this->user) && method_exists($this->user, 'getField')) {
                        echo $this->user->getField('avatar');
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- Title Container -->
        <div class="jsn-p-title">
            <h3>
                <?php 
                    if (is_object($this->user) && method_exists($this->user, 'getField')) {
                        echo $this->user->getField('formatname');
                    } else {
                        echo htmlspecialchars($formatName ?? '', ENT_QUOTES, 'UTF-8');
                    }
                ?>
            </h3>

            <?php if (!empty($this->config) && $this->config->get('status', 1) && is_object($this->user) && method_exists($this->user, 'getField')) : ?>    
                <?php echo $this->user->getField('status'); ?>
            <?php endif; ?>
        </div>

        <!-- Before Fields Container -->
        <div class="jsn-p-before-fields">
            <?php 
                $registerdate  = method_exists($this->form, 'getField') ? $this->form->getField('registerdate') : null;
                $lastvisitdate = method_exists($this->form, 'getField') ? $this->form->getField('lastvisitdate') : null;
                
                if ($registerdate || $lastvisitdate) : ?>
                    <div class="jsn-p-dates">
                        <?php if ($registerdate && is_object($this->user) && method_exists($this->user, 'getField')) : ?>
                            <div class="jsn-p-date-reg">
                                <b><?php echo Text::_('COM_JSN_MEMBER_SINCE'); ?></b> <?php echo $this->user->getField('registerdate'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($lastvisitdate && is_object($this->user) && method_exists($this->user, 'getField')) : ?>
                            <div class="jsn-p-date-last">
                                <b><?php echo Text::_('COM_JSN_LASTVISITDATE'); ?></b> <?php echo $this->user->getField('lastvisitdate'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php 
                $beforeFields = (array) $app->triggerEvent('renderBeforeFields', [$this->data, $this->config]);
                echo implode(' ', $beforeFields);
            ?>
        </div>        
    </div>

    <!-- Fields Container -->
    <div class="jsn-p-fields">
    <?php 
        $tabs = (array) $app->triggerEvent('renderTabs', [$this->data, $this->config]); 
        
        $fields_output  = implode(' ', (array) $app->triggerEvent('renderTabBeforeFields', [$this->data, $this->config]));
        $fields_output .= $this->loadTemplate('fields');
        $fields_output .= $this->loadTemplate('params');
        $fields_output .= implode(' ', (array) $app->triggerEvent('renderTabAfterFields', [$this->data, $this->config]));
        
        if (!empty($this->config) && $this->config->get('profile_fg_tabs', 1)) {
            echo $fields_output;
        } else {
            echo '<fieldset><legend>' . Text::_('COM_JSN_PROFILE_INFO') . '</legend><div>' . $fields_output . '</div></fieldset>';
        }

        $contents = [];

        foreach ($tabs as $tab) {
            if (empty($tab)) continue;

            if (is_array($tab) && isset($tab[0]) && is_object($tab[0])) {
                foreach ($tab as $tabobject) {
                    $contents[] = '<fieldset><legend>' . ($tabobject->title ?? '') . '</legend>' . ($tabobject->content ?? '') . '</fieldset>';
                }
            } elseif (is_array($tab) && isset($tab[0], $tab[1])) {
                $contents[] = '<fieldset><legend>' . $tab[0] . '</legend>' . $tab[1] . '</fieldset>';
            }
        }

        echo implode(' ', $contents);
    ?>
    </div>

    <!-- Bottom Container -->
    <div class="jsn-p-bottom">
        <!-- After Fields Container -->
        <div class="jsn-p-after-fields">
            <?php 
                $afterFields = (array) $app->triggerEvent('renderAfterFields', [$this->data, $this->config]);
                echo implode(' ', $afterFields);
            ?>
        </div>
    </div>
</div>

<?php 
    $afterProfile = (array) $app->triggerEvent('renderAfterProfile', [$this->data, $this->config]);
    echo implode(' ', $afterProfile);
?>