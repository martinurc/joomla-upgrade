<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

/**
 * @var \Joomla\Registry\Registry $params
 */

require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/libraries/rad/bootstrap.php';

JLoader::register('EventbookingModelRegistrants', JPATH_ADMINISTRATOR . '/components/com_eventbooking/model/registrants.php');
$model = RADModel::getInstance('Registrants', 'EventbookingModel', ['ignore_request' => true, 'remember_states' => false]);

$model->setState('limitstart', 0)
    ->setState('limit', $params->get('count', 5))
    ->setState('filter_order', 'tbl.id')
    ->setState('filter_order_Dir', 'DESC');

/* @var EventbookingModelRegistrants $model */
$rows  = $model->getData();
$count = (int)$params->get('count', 1);

require ModuleHelper::getLayoutPath('mod_eb_registrants');
