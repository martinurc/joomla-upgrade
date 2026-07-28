<?php

/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;

class plgButtonEbevent extends CMSPlugin
{
    /**
     * Application object.
     *
     * @var    \Joomla\CMS\Application\CMSApplication
     */
    protected $app;

    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Display the button
     *
     * @return void|CMSObject The button options as CMSObject
     */
    public function onDisplay($name)
    {
        if ($this->app->isClient('site')) {
            return;
        }

        $user = Factory::getUser();

        if ($user->authorise('core.create', 'com_eventbooking')
            || $user->authorise('core.edit', 'com_eventbooking')
            || $user->authorise('core.edit.own', 'com_eventbooking')) {
            $link = 'index.php?option=com_eventbooking&amp;view=events&amp;layout=modal&amp;function=jSelectEbevent&amp;tmpl=component&amp;'
                . Session::getFormToken() . '=1&amp;editor=' . $name;

            $button = new CMSObject();

            $button->modal = true;
            $button->link  = $link;
            $button->text  = Text::_('EB Event');

            if (version_compare(JVERSION, '4.0.0-dev', 'ge')) {
                $button->name    = $this->_type . '_' . $this->_name;
                $button->icon    = 'calendar';
                $button->iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-calendar-date" viewBox="0 0 16 16">
									  <path d="M6.445 11.688V6.354h-.633A12.6 12.6 0 0 0 4.5 7.16v.695c.375-.257.969-.62 1.258-.777h.012v4.61h.675zm1.188-1.305c.047.64.594 1.406 1.703 1.406 1.258 0 2-1.066 2-2.871 0-1.934-.781-2.668-1.953-2.668-.926 0-1.797.672-1.797 1.809 0 1.16.824 1.77 1.676 1.77.746 0 1.23-.376 1.383-.79h.027c-.004 1.316-.461 2.164-1.305 2.164-.664 0-1.008-.45-1.05-.82h-.684zm2.953-2.317c0 .696-.559 1.18-1.184 1.18-.601 0-1.144-.383-1.144-1.2 0-.823.582-1.21 1.168-1.21.633 0 1.16.398 1.16 1.23z"/>
									  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
									</svg>';

                $button->options = [
                    'height'     => '300px',
                    'width'      => '800px',
                    'bodyHeight' => '70',
                    'modalWidth' => '80',
                ];
            } else {
                $button->class   = 'btn';
                $button->name    = 'calendar';
                $button->options = "{handler: 'iframe', size: {x: 800, y: 500}}";
            }

            return $button;
        }
    }
}
