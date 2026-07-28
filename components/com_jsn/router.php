<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

function JsnBuildRoute(&$query)
{
    $segments = array();

    // Obtener instancias mediante API nativa de Joomla 5
    $app        = \Joomla\CMS\Factory::getApplication();
    $menu       = $app->getMenu();
    $params     = \Joomla\CMS\Component\ComponentHelper::getParams('com_jsn');
    $config     = \Joomla\CMS\Factory::getConfig();
    $sef_suffix = $config->get('sef_suffix', 0);

    if (isset($query['view'])) {
        switch ($query['view']) {
            case '':
            case 'profile':
                if (isset($query['Itemid']) && is_numeric($query['Itemid'])) {
                    $menuItem = $menu->getItem((int)$query['Itemid']);
                    // Validar si el Itemid existe sin hacer unset si el enlace difiere en parámetros secundarios
                    if (!$menuItem) {
                        unset($query['Itemid']);
                    }
                }

                if (!isset($query['Itemid'])) {
                    $active = $menu->getActive();
                    if (isset($active->link) && strpos($active->link, 'index.php?option=com_jsn&view=profile') !== false) {
                        $query['Itemid'] = $active->id;
                    } else {
                        $profileMenu = $menu->getItems('link', 'index.php?option=com_jsn&view=profile', true);
                        if (isset($profileMenu->id)) {
                            $query['Itemid'] = $profileMenu->id;
                        } else {
                            unset($query['Itemid']);
                        }
                    }
                }

                unset($query['view']);

                if (isset($query['id'])) {
                    static $username_cache = array();

                    if (!isset($username_cache[$query['id']])) {
                        if ($params->get('sef_with', 'username') === 'id') {
                            $segments[] = $query['id'];
                            $username_cache[$query['id']] = $query['id'];
                        } else {
                            $db      = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
                            $dbquery = $db->getQuery(true);
                            $dbquery->select('a.username')
                                    ->from($db->quoteName('#__users', 'a'))
                                    ->where($db->quoteName('a.id') . ' = ' . (int) $query['id']);

                            $db->setQuery($dbquery);
                            $username = $db->loadResult();

                            if ($username) {
                                if (!$sef_suffix && strpos(' ' . $username, '.') > 0) {
                                    $username .= '.html';
                                }
                                $segments[] = urlencode($username);
                                $username_cache[$query['id']] = $username;
                            } else {
                                $segments[] = $query['id'];
                                $username_cache[$query['id']] = $query['id'];
                            }
                        }
                    } else {
                        $segments[] = urlencode($username_cache[$query['id']]);
                    }

                    unset($query['id']);
                }
                break;

            case 'list':
            case 'search':
                unset($query['view']);
                break;

            case 'social':
                $active = $menu->getActive();
                if (isset($active->link) && strpos($active->link, 'index.php?option=com_jsn&view=social') !== false) {
                    $query['Itemid'] = $active->id;
                } else {
                    $profileMenu = $menu->getItems('link', 'index.php?option=com_jsn&view=social', true);
                    if (isset($profileMenu->id)) {
                        $query['Itemid'] = $profileMenu->id;
                    } else {
                        $defaultMenu = $menu->getDefault();
                        if ($defaultMenu) {
                            $query['Itemid'] = $defaultMenu->id;
                        }
                    }
                }

                unset($query['view']);

                if (isset($query['route'])) {
                    $segments[] = $query['route'];
                    unset($query['route']);
                }
                break;

            default:
                break;
        }
    }

    return $segments;
}

function JsnBuildRouteDeprecated(&$query)
{
	$segments = array();

	// get a menu item based on Itemid or currently active
	$app = JFactory::getApplication();
	$menu = $app->getMenu();
	$params = JComponentHelper::getParams('com_jsn');
	$advanced = $params->get('sef_advanced_link', 0);
	$sef_suffix=JFactory::getConfig()->get('sef_suffix',0);

	if(isset($query['view']))
		switch($query['view'])
		{
			case '':
			case 'profile':
				if(isset($query['Itemid']) && is_int($query['Itemid'])) {
					if($menu->getItem($query['Itemid'])->link!='index.php?option=com_jsn&view=profile') unset($query['Itemid']);
				}
				if(!isset($query['Itemid'])) {
					$active=$menu->getActive();
					if(isset($active->link) && $active->link=='index.php?option=com_jsn&view=profile')
					{
						$query['Itemid']=$active->id;
					}
					else
					{
						$profileMenu=$menu->getItems('link','index.php?option=com_jsn&view=profile',true);
						if(isset($profileMenu->id))
						{
							$query['Itemid']=$profileMenu->id;
						}
						else{
							unset($query['Itemid']);
						}
					}
				}
				unset($query['view']);
				
				if(isset($query['id']))
				{
					static $username_cache;
					if(!isset($username_cache[$query['id']]))
					{
						if($params->get('sef_with','username') == 'id') {
							$segments[]=$query['id'];
							$username_cache[$query['id']]=$query['id'];
						}
						else {
							$db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
							$dbquery = $db->getQuery(true);
							$dbquery->select('a.username')->from('#__users AS a')->where('a.id = '. (int) $query['id']);
							$db->setQuery( $dbquery );
							if($username=$db->loadResult())
							{
								if(!$sef_suffix && strpos(' '.$username,'.')>0) $username.='.html';
								$segments[]=urlencode($username);
								$username_cache[$query['id']]=$username;
							}
						}
					}
					else
					{
						$segments[]=urlencode($username_cache[$query['id']]);
					}
					
					unset($query['id']);
				}
			break;
			case 'list':
			case 'search':
				unset($query['view']);
			break;
			case 'social':
				$active=$menu->getActive();
				if(isset($active->link) && $active->link=='index.php?option=com_jsn&view=social')
				{
					$query['Itemid']=$active->id;
				}
				else
				{
					$profileMenu=$menu->getItems('link','index.php?option=com_jsn&view=social',true);
					if(isset($profileMenu->id))
					{
						$query['Itemid']=$profileMenu->id;
					}
					else{
						$query['Itemid']=$menu->getDefault()->id;
					}
				}
				unset($query['view']);
				if(isset($query['route']))
				{
					$segments[]=$query['route'];
					unset($query['route']);
				}
			break;
			default:
			break;
		}
	
	return $segments;
}

function JsnParseRoute($segments)
{
    $vars = array();

    // En Joomla 5 obtenemos la aplicación y el componente mediante Factory y ComponentHelper nativos
    $app      = \Joomla\CMS\Factory::getApplication();
    $menu     = $app->getMenu();
    $item     = $menu->getActive();
    $params   = \Joomla\CMS\Component\ComponentHelper::getParams('com_jsn');
    $advanced = $params->get('sef_advanced_link', 0);

    // Contar segmentos de la ruta
    $count = count($segments);

    if ($count === 0) {
        return $vars;
    }

    if (!isset($item) || ($segments[0] === 'profile' && isset($item->query['view']) && $item->query['view'] !== 'social')) {
        $vars['view'] = 'profile';
    } else {
        $vars['view'] = $item->query['view'] ?? 'profile';
    }

    switch ($vars['view']) {
        case 'profile':
            // Limpieza del último segmento (extracción de .html o caracteres codificados)
            $rawSegment = $segments[$count - 1];
            $cleanSegment = str_replace('.html', '', $rawSegment);

            if ($params->get('sef_with', 'username') === 'id') {
                $vars['id'] = (int) $cleanSegment;
            } else {
                $db      = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
                $dbquery = $db->getQuery(true);

                $altSegment = str_replace(':', '-', $cleanSegment);

                $dbquery->select($db->quoteName('a.id'))
                    ->from($db->quoteName('#__users', 'a'))
                    ->where($db->quoteName('a.username') . ' = ' . $db->quote($cleanSegment) . ' OR ' . $db->quoteName('a.username') . ' = ' . $db->quote($altSegment));

                $db->setQuery($dbquery);
                $userId = $db->loadResult();

                if ($userId) {
                    $vars['id'] = (int) $userId;
                }
            }
            break;

        case 'social':
            $vars['route'] = '/' . implode('/', $segments);
            break;
    }

    return $vars;
}
