<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2021 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Application\Application;
use CBLib\Registry\GetterInterface;
use CB\Database\Table\PluginTable;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

/**
 * CB install
 *
 * Class cbpackageinstallerAdmin
 */
class cbpackageinstallerAdmin
{

	/**
	 * @param object $row
	 * @param string $option
	 * @param string $task
	 * @param int    $uid
	 * @param string $action
	 * @param string $element
	 * @param int    $mode
	 * @param object $pluginParams
	 */
	public function editPluginView( $row, $option, $task, $uid, $action, $element, $mode, $pluginParams )
	{
		$install	=	new packageinstallerInstall( 'cb' );

		echo $install->install();
	}
}

class packageinstallerInstall
{
	/** @var string  */
	private $location	=	'joomla';
	/** @var PluginTable  */
	private $plugin		=	null;

	/**
	 * cbpackageinstallerAdmin constructor.
	 *
	 * @param string $location
	 */
	public function __construct( $location = 'joomla' )
	{
		if ( $location ) {
			$this->location		=	$location;
		}

		if ( $this->location == 'cb' ) {
			static $plugin		=	null;

			if ( $plugin == null ) {
				$plugin			=	new PluginTable();

				$plugin->load( array( 'element' => 'cbpackageinstaller' ) );
			}

			$this->plugin		=	$plugin;
		}
	}

	/**
	 * @return string
	 */
	public function install()
	{
		ob_clean();
		$return		=	$this->prepare();
		ob_end_clean();

		return $return;
	}

	/**
	 * @return string
	 */
	private function prepare()
	{
		global $_PLUGINS;

		if ( $this->plugin ) {
			$input										=	Application::Input();
			$view										=	$input->get( 'action', null, GetterInterface::COMMAND );
			$folder										=	$input->get( 'fld', null, GetterInterface::STRING );
			$package									=	$input->get( 'pkg', null, GetterInterface::STRING );
		} else {
			$jInput										=	JFactory::getApplication()->input;
			$view										=	$jInput->get( 'view', null, 'cmd' );

			if ( ! $view ) {
				$view									=	$jInput->get( 'task', null, 'cmd' );
			}

			$folder										=	$jInput->get( 'fld', null, 'string' );
			$package									=	$jInput->get( 'pkg', null, 'string' );

			jimport( 'joomla.application.component.model' );
			jimport( 'joomla.installer.installer' );
			jimport( 'joomla.installer.helper' );
		}

		if ( $view == 'installpkg' ) {
			if ( $folder && $package ) {
				if ( $this->plugin ) {
					$packageDir							=	$_PLUGINS->getPluginPath( $this->plugin ) . '/extensions/' . $folder . '/' . $package;
				} else {
					$packageDir							=	JPATH_ADMINISTRATOR . '/components/com_packageinstaller/extensions/' . $folder . '/' . $package;
				}

				if ( file_exists( $packageDir ) ) {
					if ( $folder == 'custom' ) {
						$packageDest					=	JPATH_SITE . '/images/' . $package;

						if ( @copy( $packageDir, $packageDest ) ) {
							return 'TRUE';
						} else {
							return 'FALSE';
						}
					} elseif ( $folder == 'scripts' ) {
						ob_clean();
						include_once( $packageDir );
						$return							=	ob_get_contents();
						ob_end_clean();

						if ( $return ) {
							if ( preg_match( '/^TRUE$|^FALSE$|^TRUE:|^FALSE:/', $return ) ) {
								return $return;
							} elseif ( $return == true ) {
								return 'TRUE';
							} elseif ( $return == false ) {
								return 'FALSE';
							}
						}

						return 'TRUE';
					} elseif ( $folder == 'queries' ) {
						$buffer							=	file_get_contents( $packageDir );

						if ( ! $buffer ) {
							return 'FALSE';
						}

						$database						=	JFactory::getDbo();
						$queries						=	$database->splitSql( $buffer );

						if ( ! $queries ) {
							return 'FALSE';
						}

						$errors							=	array();

						if ( $queries ) {
							foreach ( $queries as $query ) {
								$query					=	trim( $query );

								if ( ( $query != '' ) && ( $query[0] != '#' ) ) {
									$database->setQuery( $query );

									try {
										$database->execute();
									} catch ( Exception $e ) {
										$errors[]		=	array( 'type' => 'error', 'message' => $e->getMessage() );
									}
								}
							}
						}

						if ( ! $errors ) {
							return 'TRUE';
						} else {
							return 'FALSE' . ( $errors ? ':' . json_encode( $errors ) : null );
						}
					} elseif ( $folder == 'overrides' ) {
						$zip							=	new ZipArchive();
						$zipOpen						=	$zip->open( $packageDir );

						if ( $zipOpen === true ) {
							$zip->extractTo( JPATH_ROOT . '/' );
							$zip->close();

							return 'TRUE';
						} else {
							$zipError					=	null;

							switch ( $zipOpen ) {
								case 4:
									$zipError			=	'Seek error. Error Code: ZIPARCHIVE::ER_SEEK';
									break;
								case 5:
									$zipError			=	'Read error. Error Code: ZIPARCHIVE::ER_READ';
									break;
								case 9:
									$zipError			=	'No such file. Error Code: ZIPARCHIVE::ER_NOENT';
									break;
								case 10:
									$zipError			=	'File already exists. Error Code: ZIPARCHIVE::ER_EXISTS';
									break;
								case 11:
									$zipError			=	'Can not open file. Error Code: ZIPARCHIVE::ER_OPEN';
									break;
								case 14:
									$zipError			=	'Malloc failure. Error Code: ZIPARCHIVE::ER_MEMORY';
									break;
								case 18:
									$zipError			=	'Invalid argument. Error Code: ZIPARCHIVE::ER_INVAL';
									break;
								case 19:
									$zipError			=	'Not a zip archive. Error Code: ZIPARCHIVE::ER_NOZIP';
									break;
								case 21:
									$zipError			=	'Zip archive inconsistent. Error Code: ZIPARCHIVE::ER_INCONS';
									break;
							}

							return 'FALSE' . ( $zipError ? ':' . json_encode( array( array( 'type' => 'error', 'message' => $zipError ) ) ) : null );
						}
					} elseif ( $folder == 'cb_plugins' ) {
						if ( ! $this->plugin ) {
							if ( ( ! file_exists( JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php' ) ) || ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) ) {
								return 'FALSE:' . json_encode( array( array( 'type' => 'error', 'message' => 'CB not installed' ) ) );
							}

							include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );

							cbimport( 'cb.html' );
						}

						cbimport( 'cb.installer' );

						$installer						=	new cbInstallerPlugin();
						$installed						=	false;

						if ( $installer->upload( $packageDir ) ) {
							if ( $installer->preInstallCheck( null, $installer->elementType ) && $installer->install() ) {
								$installed				=	true;
							}

							$installer->cleanupInstall( null, $installer->unpackDir() );
						}

						$msg							=	$installer->getError();

						if ( $installed ) {
							return 'TRUE' . ( $msg ? ':' . json_encode( array( array( 'type' => 'success', 'message' => $msg ) ) ) : null );
						} else {
							return 'FALSE' . ( $msg ? ':' . json_encode( array( array( 'type' => 'error', 'message' => $msg ) ) ) : null );
						}
					} else {
						$extension						=	JInstallerHelper::unpack( $packageDir );
						$installed						=	false;
						$msg							=	null;

						if ( $extension ) {
							$installer					=	JInstaller::getInstance();

							$installer->setPath( 'source', $extension['dir'] );

							if ( $installer->setupInstall() && $installer->install( $extension['dir'] ) ) {
								$installed				=	true;
							}

							$msg						=	$installer->get( 'message' ) . $installer->get( 'extension_message' );

							JInstallerHelper::cleanupInstall( null, $extension['dir'] );
						}

						if ( $installed ) {
							return 'TRUE' . ( $msg ? ':' . json_encode( array( array( 'type' => 'success', 'message' => $msg ) ) ) : null );
						} else {
							$msgs						=	JFactory::getApplication()->getMessageQueue();

							return 'FALSE' . ( $msgs ? ':' . json_encode( $msgs ) : null );
						}
					}
				} else {
					return 'FALSE';
				}
			} else {
				return 'FALSE';
			}
		} elseif ( $view == 'uninstallself' ) {
			if ( $this->plugin ) {
				ob_start();
				$plgInstaller							=	new cbInstallerPlugin();
				$uninstalled							=	$plgInstaller->uninstall( $this->plugin->id, 'com_comprofiler' );
				ob_end_clean();

				if ( $uninstalled ) {
					return 'TRUE';
				} else {
					return 'FALSE' . ( $plgInstaller->getError() ? ':' . json_encode( array( array( 'type' => 'error', 'message' => $plgInstaller->getError() ) ) ) : null );
				}
			} else {
				$component								=	JComponentHelper::getComponent( 'com_packageinstaller' );
				$installer								=	JInstaller::getInstance();

				if ( $installer->uninstall( 'component', $component->id ) ) {
					return 'TRUE';
				} else {
					$msgs								=	JFactory::getApplication()->getMessageQueue();

					return 'FALSE' . ( $msgs ? ':' . json_encode( $msgs ) : null );
				}
			}
		}

		return 'FALSE';
	}
}