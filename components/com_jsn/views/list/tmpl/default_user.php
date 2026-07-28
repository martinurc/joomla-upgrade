<?php
/**
* @copyright    Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license        http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package        Easy Profile
* website        www.easy-profile.com
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// 1. Obtener la referencia al objeto usuario de forma segura
$userObj  = $this->user ?? null;
$username = is_object($userObj) ? ($userObj->username ?? ($userObj->name ?? 'user')) : 'user';

// 2. Extraer el nombre de la agencia con cascada de fallbacks
$formatName = '';
if (is_object($userObj) && method_exists($userObj, 'getField')) {
    $candidates = ['nombre_comercial', 'empresa_agencia', 'razon_social', 'nombre_comercial_ma', 'razon_social_ma'];
    foreach ($candidates as $candidate) {
        $val = trim((string) strip_tags($userObj->getField($candidate, true)));
        if (!empty($val) && $val !== '-' && strpos($val, 'COM_USERS_PROFILE_VALUE_NOT_FOUND') === false) {
            $formatName = $val;
            break;
        }
    }
}

if (empty($formatName) && is_object($userObj)) {
    $formatName = !empty($userObj->name) ? $userObj->name : '';
}

// 3. Generar enlace seguro y compatible con Joomla 5 usando el ID numérico real
$profileLink = '#';
if (is_object($userObj)) {
    // Asegurar que obtenemos el ID numérico de la propiedad del usuario, no un alias/CIF
    $userId = (int) ($userObj->id ?? 0);
    if ($userId > 0) {
        // Opción A: Enlace No-SEF directo (Garantiza que Joomla cargue el perfil)
        $profileLink = \Joomla\CMS\Uri\Uri::base() . 'index.php?option=com_jsn&view=profile&id=' . $userId;
    }
    /*if ($userId > 0) {
        $backParam   = !empty($this->url_options['back']) ? '&back=1' : '';
        $itemIdParam = !empty($this->url_options['Itemid']) ? '&Itemid=' . (int) $this->url_options['Itemid'] : '';
        
        // Al usar com_users o la vista de perfil nativa/SEF con id numérico:
        $profileLink = Route::_('index.php?option=com_jsn&view=profile&id=' . $userId . $itemIdParam . $backParam);
    }*/
}
?>

<div class="jsn-l-w<?php echo $this->span ?? 12; ?> jsn-l profile<?php echo substr(md5($username), 0, 10); ?>">

    <!-- Top Container -->
    <div class="jsn-l-top <?php echo (!empty($this->config) && $this->config->get('avatar', 1) ? 'jsn-l-top-a' : ''); ?>">

        <!-- Avatar Container -->
        <?php if (!empty($this->config) && $this->config->get('avatar', 1)) : ?> 
            <div class="jsn-l-avatar">
                <a href="<?php echo $profileLink; ?>">
                <?php
                    if (is_object($userObj) && method_exists($userObj, 'getField')) {
                        echo $userObj->getField('avatar_mini', true);
                    }
                ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Title Container -->
        <div class="jsn-l-title">
            <h3>
                <a href="<?php echo $profileLink; ?>">
                    <?php echo htmlspecialchars($formatName, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </h3>

            <?php if (!empty($this->config) && $this->config->get('status', 1) && is_object($userObj) && method_exists($userObj, 'getField')) : ?>    
                <?php echo $userObj->getField('status'); ?>
            <?php endif; ?>
        </div>

        <div class="jsn-l-fields">
            <?php 
            $fields = isset($this->params) ? $this->params->def('list_fields', []) : [];
            if (is_array($fields) && is_object($userObj) && method_exists($userObj, 'getField')) :
                foreach ($fields as $field) : 
                    $value = $userObj->getField($field, true); 
                    if (!empty($value)) : ?>
                        <div class="<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (isset($this->params) && $this->params->def('show_titles', 0)) : ?>
                                <span class="jsn-l-field-title">
                                    <?php 
                                        $fieldKey = $this->fields_title[$field]['title'] ?? $field;
                                        echo Text::_($fieldKey); 
                                    ?>: 
                                </span>
                            <?php endif; ?>
                            <span class="jsn-l-field-value"><?php echo $value; ?></span>
                        </div>
                    <?php endif; 
                endforeach; 
            endif; ?>
        </div>    
    </div>
</div>