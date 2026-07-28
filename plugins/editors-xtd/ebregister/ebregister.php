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

class plgButtonEbregister extends CMSPlugin
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
            $link = 'index.php?option=com_eventbooking&amp;view=events&amp;layout=modal&amp;function=jSelectEbregister&amp;tmpl=component&amp;'
                . Session::getFormToken() . '=1&amp;editor=' . $name;

            $button = new CMSObject();

            $button->modal = true;
            $button->link  = $link;
            $button->text  = Text::_('EB Register');

            if (version_compare(JVERSION, '4.0.0-dev', 'ge')) {
                $button->name    = $this->_type . '_' . $this->_name;
                $button->icon    = 'person-plus';
                $button->iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16">
									  <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
									  <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
									</svg>';

                $button->options = [
                    'height'     => '300px',
                    'width'      => '800px',
                    'bodyHeight' => '70',
                    'modalWidth' => '80',
                ];
            } else {
                $button->class   = 'btn';
                $button->name    = 'user';
                $button->options = "{handler: 'iframe', size: {x: 800, y: 500}}";
            }

            return $button;
        }
    }
}
