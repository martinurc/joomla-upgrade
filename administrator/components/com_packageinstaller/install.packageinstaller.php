<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2021 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CB\Database\Table\PluginTable;
use CBLib\Core\CBLib;
use Joomla\CMS\HTML\HTMLHelper;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

/**
 * CB installer
 *
 * @return bool
 */
function plug_cbpackageinstaller_install()
{
	$installer		=	new packageinstallerInstaller( 'cb' );

	return $installer->install();
}

/**
 * Joomla installer
 *
 * Class com_packageinstallerInstallerScript
 */
class com_packageinstallerInstallerScript
{

	/**
	 * @return bool
	 */
	public function preflight( $type, $parent )
	{
		// Delete the extensions folder if for some reason package installer is already installed
		if ( $type == 'update' ) {
			jimport( 'joomla.filesystem.folder' );

			$extensions	=	JPATH_ADMINISTRATOR . '/components/com_packageinstaller/extensions';

			if ( JFolder::exists( $extensions ) ) {
				JFolder::delete( $extensions );
			}
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function install()
	{
		$installer		=	new packageinstallerInstaller();

		return $installer->install();
	}

	/**
	 * @return bool
	 */
	public function discover_install()
	{
		return $this->install();
	}

	/**
	 * @return bool
	 */
	public function update()
	{
		return $this->install();
	}
}

class packageinstallerInstaller
{
	/** @var string  */
	private $location	=	'joomla';
	/** @var PluginTable  */
	private $plugin		=	null;

	/**
	 * com_packageinstallerInstallerScript constructor.
	 *
	 * @param string $location
	 */
	public function __construct( $location = 'joomla' )
	{
		if ( $location ) {
			$this->location		=	$location;
		}

		if ( $this->location == 'cb' ) {
			if ( ( ! file_exists( JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php' ) ) || ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) ) {
				return;
			}

			include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );

			cbimport( 'cb.html' );

			static $plugin		=	null;

			if ( $plugin == null ) {
				$plugin			=	new PluginTable();

				$plugin->load( array( 'element' => 'cbpackageinstaller' ) );
			}

			$this->plugin		=	$plugin;
		}
	}

	/**
	 * @return bool
	 */
	public function install()
	{
		global $_CB_framework, $_PLUGINS;

		$packages					=	array(	'packages'		=>	array(),
												'libraries'		=>	array(),
												'components'	=>	array(),
												'plugins'		=>	array(),
												'modules'		=>	array(),
												'languages'		=>	array(),
												'templates'		=>	array(),
												'cb_plugins'	=>	array(),
												'queries'		=>	array(),
												'scripts'		=>	array(),
												'overrides'		=>	array(),
												'custom'		=>	array()
											);

		$count						=	0;

		foreach ( array_keys( $packages ) as $type ) {
			$this->directory_files( $type, $packages, $count );
		}

		$js							=	null;
		$plugin						=	null;
		$return						=	'<div class="cb_template">';

		if ( $this->plugin ) {
			$_CB_framework->document->addHeadStyleSheet( $_PLUGINS->getPluginLivePath( $this->plugin ) . '/cbpackageinstaller.css' );
		} else {
			if ( $this->jVersion() >= 6 ) {
				HTMLHelper::_( 'jquery.framework' );
			} elseif ( $this->jVersion() == 5 ) {
				JHtml::_( 'jquery.framework' );
			}

			$return					.=		'<link type="text/css" href="' . JURI::base() . 'components/com_packageinstaller/packageinstaller.css" rel="stylesheet" />';

			if ( $this->jVersion() < 5 ) {
				$return				.=		'<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>'
									.		'<script type="text/javascript">jQuery.noConflict();</script>';
			}

			if ( $this->jVersion() >= 6 ) {
				$js					.=	"document.addEventListener('DOMContentLoaded', function () {";
			}

			$js						.=	"jQuery( document ).ready( function( $ ) {";
		}

		// Clear installer messages for the package installer:
		if ( $this->jVersion() >= 6 ) {
			$js						.=		"$( '#system-message-container > joomla-alert[type=\"success\"]' ).remove();";
		} else {
			$js						.=		"$( '#system-message-container > .alert.alert-success' ).remove();";
		}

		$return						.=		'<div class="mb-4 cbPkgInstaller cb_packageinstaller">'
									.			'<div class="cbPkgInstall">'
									.				'<div class="mb-4">'
									.					'<div class="m-0 progress cbPkgInstallProgress">'
									.						'<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">'
									.							'<span class="sr-only">0% Complete</span>'
									.						'</div>'
									.					'</div>'
									.				'</div>'
									.				'<div class="mb-4">'
									.					'<div class="m-n3 row no-gutters cbPkgInstallRows"></div>'
									.				'</div>'
									.			'</div>';

		$js							.=		"$( '.cbPkgInstaller' ).on( 'click', '.cbPkgInstallRow', function( e ) {"
									.			"if ( $( e.target ).is( 'a' ) || $( e.target ).is( 'button' ) ) {"
									.				"return;"
									.			"}"
									.			"if ( $( this ).hasClass( 'open' ) ) {"
									.				"$( this ).removeClass( 'open' );"
									.			"} else {"
									.				"$( this ).addClass( 'open' );"
									.				"$( this ).find( '.cbPkgInstallDetailCopyNote' ).addClass( 'hidden' );"
									.			"}"
									.		"});"
									.		"$( '.cbPkgInstaller' ).on( 'click', '.cbPkgInstallDetailCopyBtn', function() {"
									.			"$( '.cbPkgInstallDetailCopyNote' ).addClass( 'hidden' );"
									.			"$( this ).siblings( '.cbPkgInstallDetailCopyText' )[0].select();"
									.			"document.execCommand( 'copy' );"
									.			"$( this ).siblings( '.cbPkgInstallDetailCopyNote' ).removeClass( 'hidden' );"
									.		"});"
									.		"var packages = [];"
									.		"var packagesMessage = null;"
									.		"var packagesErrored = 0;";

		if ( $this->plugin ) {
			$uninstallUrl			=	$_CB_framework->backendViewUrl( 'editPlugin', false, array( 'action' => 'uninstallself', 'cid' => (int) $this->plugin->get( 'id' ) ), 'raw' );
			$cbVersion				=	CBLib::version();
		} else {
			$uninstallUrl			=	JRoute::_( 'index.php?option=com_packageinstaller&view=uninstallself&format=raw', false );
			$cbVersion				=	null;

			if ( class_exists( '\CBLib\Core\CBLib' ) ) {
				$cbVersion			=	CBLib::version();
			}
		}

		$step						=	0;
		$progress					=	0;

		if ( $count ) {
			$pkgProgress			=	round( 90 / $count );

			foreach ( $packages as $fld => $pkgs ) {
				if ( ! $pkgs ) {
					continue;
				}

				foreach ( $pkgs as $pkg ) {
					$progress		+=	$pkgProgress;

					if ( $this->plugin ) {
						$url		=	$_CB_framework->backendViewUrl( 'editPlugin', false, array( 'action' => 'installpkg', 'cid' => (int) $this->plugin->get( 'id' ), 'fld' => $pkg['type'], 'pkg' => $pkg['path'] ), 'raw' );
					} else {
						$url		=	JRoute::_( 'index.php?option=com_packageinstaller&view=installpkg&fld=' . urlencode( $pkg['type'] ) . '&pkg=' . urlencode( $pkg['path'] ) . '&format=raw', false );
					}

					$pkgRow			=	'<div class="p-3 col-12 col-md-6 col-lg-4 cbPkgInstallRow cbPkgInstallRow' . $step . '">'
									.		'<div class="card h-100 border-info">'
									.			'<div class="card-header p-2 text-large text-wrap text-center cbPkgInstallName">'
									.				'<strong>' . $pkg['details']['name'] . '</strong>'
									.			'</div>'
									.			'<div class="d-flex flex-column justify-content-end card-body p-0 cbPkgInstallDetails">';

						if ( $pkg['details']['description'] ) {
							$pkgRow	.=				'<div class="flex-grow-1 p-2 text-wrap cbPkgInstallDetailDescription">'
									.					'<div class="cbPkgInstallDescription">'
									.						$pkg['details']['description']
									.					'</div>'
									.				'</div>';
						}

						if ( $pkg['type'] ) {
							$pkgRow	.=				'<div class="row no-gutters cbPkgInstallDetailType">'
									.					'<div class="p-2 col-3 border-right">Type</div>'
									.					'<div class="p-2 col text-wrap">' . $this->getPackageType( $pkg['type'] ) . '</div>'
									.				'</div>';
						}

						if ( $pkg['details']['version'] ) {
							$pkgRow	.=				'<div class="row no-gutters cbPkgInstallDetailVersion">'
									.					'<div class="p-2 col-3 border-right">Version</div>'
									.					'<div class="p-2 col text-wrap">' . $pkg['details']['version'] . '</div>'
									.				'</div>';
						}

						if ( $pkg['details']['date'] ) {
							$pkgRow	.=				'<div class="row no-gutters cbPkgInstallDetailDate">'
									.					'<div class="p-2 col-3 border-right">Date</div>'
									.					'<div class="p-2 col text-wrap">' . $pkg['details']['date'] . '</div>'
									.				'</div>';
						}

						$pkgRow		.=				'<div class="row no-gutters cbPkgInstallDetailFile">'
									.					'<div class="p-2 col-3 border-right">File</div>'
									.					'<div class="p-2 col text-wrap">' . $pkg['file'] . '</div>'
									.				'</div>'
									.				'<div class="row no-gutters cbPkgInstallDetailCopy">'
									.					'<div class="p-2 col-3 border-right"></div>'
									.					'<div class="p-2 col text-wrap">'
									.						'<button type="button" class="btn btn-sm btn-primary cbPkgInstallDetailCopyBtn">Click to Copy Installation Details</button>'
									.						'<textarea class="cbPkgInstallDetailCopyText">'
									.							'System Information'
									.							"\n" . 'Joomla: ' . $this->jVersion( 'version' )
									.							( $cbVersion ? "\n" . 'Community Builder: ' . $cbVersion : '' )
									.							"\n" . 'PHP: ' . PHP_VERSION
									.							"\n" . 'Database: ' . JFactory::getDbo()->getServerType() . ' ' . JFactory::getDbo()->getVersion()
									.							"\n"
									.							"\n" . 'Package'
									.							"\n" . 'File: ' . $pkg['file']
									.							"\n" . 'Log:'
									.						'</textarea>'
									.						' <span class="text-small cbPkgInstallDetailCopyNote hidden">Copied to Clipboard!</span>'
									.					'</div>'
									.				'</div>'
									.			'</div>'
									.			'<div class="card-body p-2 border-top cbPkgInstallLog hidden">'
									.				'<div class="mb-2 text-center"><strong>Install Messages</strong></div>'
									.			'</div>'
									.			'<div class="card-footer p-0">'
									.				'<div class="m-0 w-100 p-1 bg-info text-white text-center cbPkgInstallState cbPkgInstallStateInstalling">'
									.					'<div class="d-flex align-items-center">'
									.						'<span class="spinner-border spinner-border-sm mr-auto" role="status"></span>'
									.						'<span class="d-inline-block mr-auto">Installing...</span>'
									.					'</div>'
									.				'</div>'
									.				'<div class="m-0 w-100 p-1 bg-success text-white text-center cbPkgInstallState cbPkgInstallStateInstalled hidden">'
									.					'<span class="cbPkgInstallStateText">Installed</span>'
									.					'<span class="cbPkgInstallStateInfo">Click for Installation Details</span>'
									.				'</div>'
									.				'<div class="m-0 w-100 p-1 bg-danger text-white text-center cbPkgInstallState cbPkgInstallStateFailed hidden">'
									.					'<span class="cbPkgInstallStateText">Failed</span>'
									.					'<span class="cbPkgInstallStateInfo">Click for Installation Details</span>'
									.				'</div>'
									.			'</div>'
									.		'</div>'
									.	'</div>';

					$js				.=		"packages[$step] = function() {"
									.			"$.ajax({"
									.				"url: " . json_encode( $url, JSON_HEX_TAG ) . ","
									.				"cache: false,"
									.				"type: 'GET',"
									.				"beforeSend: function( jqXHR, settings ) {"
									.					"$( '.cbPkgInstallRows' ).prepend( " . json_encode( $pkgRow, JSON_HEX_TAG ) . " );"
									.					"$( '.cbPkgInstaller' ).triggerHandler( 'cbpackagebuilder.install.begin', [$step, " . json_encode( $pkg, JSON_HEX_TAG ) . "] );"
									.				"}"
									.			"}).done( function( data, textStatus, jqXHR ) {"
									.				"if ( data.match( /^TRUE/i ) ) {"
									.					"reportInstallSuccess( " . json_encode( $pkg, JSON_HEX_TAG ) . ", $step, $progress, data.replace( /^TRUE:|TRUE/i, '' ) );"
									.				"} else {"
									.					"reportInstallFailed( " . json_encode( $pkg, JSON_HEX_TAG ) . ", $step, $progress, data.replace( /^FALSE:|FALSE/i, '' ) );"
									.				"}"
									.			"}).fail( function( jqXHR, textStatus, errorThrown ) {"
									.				"reportInstallFailed( " . json_encode( $pkg, JSON_HEX_TAG ) . ", $step, $progress, jqXHR.status + ' - ' + errorThrown );"
									.			"});"
									.		"};";

					$step			+=	1;
				}
			}
		}

		$uninstallAlert				=	'<div class="mt-4 mb-4 alert alert-warning">';

		if ( $this->plugin ) {
			$uninstallAlert			.=		'The CB Package Installer failed to remove it self. Please navigate to <a href="index.php?option=com_comprofiler&view=showPlugins">Plugin Management</a> and uninstall <strong>CB Package Installer</strong> manually.';
		} else {
			$uninstallAlert			.=		'The Package Installer failed to remove it self. Please navigate to <a href="index.php?option=com_installer&view=manage">Extension Management</a> and uninstall <strong>Package Installer</strong> manually.';
		}

		$uninstallAlert				.=	'</div>';

		$js							.=		"packages[$step] = function() {"
									.			"$.ajax({"
									.				"url: '" . addslashes( $uninstallUrl ) . "',"
									.				"cache: false,"
									.				"type: 'GET',"
									.				"beforeSend: function( jqXHR, settings ) {"
									.					"$( '.cbPkgInstaller .progress-bar' ).attr( 'aria-valuenow', 90 ).css( 'width', '90%' );"
									.					"$( '.cbPkgInstaller .progress-bar .sr-only' ).html( '90% Complete' );"
									.					"$( '.cbPkgInstaller' ).triggerHandler( 'cbpackagebuilder.cleanup.begin' );"
									.				"}"
									.			"}).always( function() {"
									.				"$( '.cbPkgInstaller .progress-bar' ).attr( 'aria-valuenow', 100 ).css( 'width', '100%' );"
									.				"$( '.cbPkgInstaller .progress-bar .sr-only' ).html( '100% Complete' );"
									.				"$( '.cbPkgInstaller .progress-bar' ).removeClass( 'progress-bar-striped progress-bar-animated' );"
									.				"$( '.cbPkgInstaller' ).triggerHandler( 'cbpackagebuilder.install.done' );"
									.			"}).done( function( data, textStatus, jqXHR ) {"
									.				"var msg = data.replace( /^TRUE|TRUE:|FALSE|FALSE:/i, '' );"
									.				"var messages = '';"
									.				"if ( ( typeof msg != 'undefined' ) && ( msg != '' ) ) {"
									.					"try {"
									.						"$.each( $.parseJSON( msg ), function() {"
									.							"if ( this.type == 'notice' ) {"
									.								"this.type = 'info';"
									.							"} else if ( this.type == 'message' ) {"
									.								"this.type = 'sccess';"
									.							"}"
									.							"messages += '<div class=\"alert alert-' + this.type + '\">' + this.message + '</div>';"
									.						"});"
									.					"} catch ( e ) {"
									.						"messages = msg;"
									.					"}"
									.				"}"
									.				"if ( data.match( /^TRUE/i ) ) {"
									.					"if ( packagesMessage && ( ! packagesErrored ) ) {"
									.						"$( '.cbPkgInstaller' ).hide();"
									.						"$( '.cbPkgInstall' ).replaceWith( '<div class=\"cbPkgInstallMessage\"><strong>' + $( packagesMessage ).html() + '</strong></div>' );"
									.						"$( '.cbPkgInstaller' ).fadeIn( 'slow' );"
									.					"} else {"
									.						"if ( packagesErrored ) {"
									.							"$( '.cbPkgInstaller .progress-bar' ).addClass( 'bg-danger' );"
									.						"} else {"
									.							"$( '.cbPkgInstaller .progress-bar' ).addClass( 'bg-success' );"
									.						"}"
									.					"}"
									.					"$( '.cbPkgInstaller' ).triggerHandler( 'cbpackagebuilder.cleanup.success' );"
									.				"} else {"
									.					"if ( packagesErrored ) {"
									.						"$( '.cbPkgInstaller .progress-bar' ).addClass( 'bg-danger' );"
									.					"} else {"
									.						"$( '.cbPkgInstaller .progress-bar' ).addClass( 'bg-warning' );"
									.					"}"
									.					"$( '.cbPkgInstallProgress' ).after( " . json_encode( $uninstallAlert, JSON_HEX_TAG ) . " );"
									.					"$( '.cbPkgInstaller' ).triggerHandler( 'cbpackagebuilder.cleanup.failed' );"
									.				"}"
									.			"}).fail( function( jqXHR, textStatus, errorThrown ) {"
									.				"if ( packagesErrored ) {"
									.					"$( '.cbPkgInstaller .progress-bar' ).addClass( 'bg-danger' );"
									.				"} else {"
									.					"$( '.cbPkgInstaller .progress-bar' ).addClass( 'bg-warning' );"
									.				"}"
									.				"$( '.cbPkgInstallProgress' ).after( " . json_encode( $uninstallAlert, JSON_HEX_TAG ) . " );"
									.				"$( '.cbPkgInstaller' ).triggerHandler( 'cbpackagebuilder.cleanup.failed' );"
									.			"});"
									.		"};"
									.		"function reportInstallSuccess( pkg, step, progress, msg ) {"
									.			"var messages = '';"
									.			"var rawMessages = '';"
									.			"if ( ( typeof msg != 'undefined' ) && ( msg != '' ) ) {"
									.				"try {"
									.					"$.each( $.parseJSON( msg ), function() {"
									.						"if ( this.type == 'notice' ) {"
									.							"this.type = 'info';"
									.						"} else if ( this.type == 'message' ) {"
									.							"this.type = 'sccess';"
									.						"}"
									.						"messages += '<div class=\"alert alert-' + this.type + '\">' + this.message + '</div>';"
									.						"rawMessages += '\\n' + this.message;"
									.					"});"
									.				"} catch ( e ) {"
									.					"messages = msg;"
									.					"rawMessages = '\\n' + msg;"
									.				"}"
									.			"}"
									.			( $count == 1 ? "packagesMessage = messages;" : null )
									.			"$( '.cbPkgInstallRow' + step ).addClass( 'cbPkgInstallRowInstalled' );"
									.			"$( '.cbPkgInstallRow' + step + ' .cbPkgInstallState' ).addClass( 'hidden' );"
									.			"$( '.cbPkgInstallRow' + step + ' .cbPkgInstallStateInstalled' ).removeClass( 'hidden' );"
									.			"$( '.cbPkgInstallRow' + step + '  .card' ).removeClass( 'border-info' ).addClass( 'border-success' );"
									.			"if ( messages ) {"
									.				"$( '.cbPkgInstallRow' + step + ' .cbPkgInstallLog' ).removeClass( 'hidden' ).append( messages );"
									.				"$( '.cbPkgInstallRow' + step + ' .cbPkgInstallDetailCopyText' ).val( $( '.cbPkgInstallRow' + step + ' .cbPkgInstallDetailCopyText' ).val() + '\\n[code]' + rawMessages + '\\n[/code]' );"
									.			"}"
									.			"$( '.cbPkgInstaller .progress-bar' ).attr( 'aria-valuenow', progress ).css( 'width', progress + '%' );"
									.			"$( '.cbPkgInstaller .progress-bar .sr-only' ).html( progress + '% Complete' );"
									.			"$( '.cbPkgInstaller' ).triggerHandler( 'cbpackagebuilder.install.success', [step, pkg] );"
									.			"beginInstallStep( ( step + 1 ) );"
									.		"};"
									.		"function reportInstallFailed( pkg, step, progress, error ) {"
									.			"packagesErrored++;"
									.			"var messages = '';"
									.			"var rawMessages = '';"
									.			"if ( ( typeof error != 'undefined' ) && ( error != '' ) ) {"
									.				"try {"
									.					"$.each( $.parseJSON( error ), function() {"
									.						"if ( this.type == 'notice' ) {"
									.							"this.type = 'info';"
									.						"} else if ( this.type == 'message' ) {"
									.							"this.type = 'sccess';"
									.						"}"
									.						"messages += '<div class=\"alert alert-' + this.type + '\">' + this.message + '</div>';"
									.						"rawMessages += '\\n' + this.message;"
									.					"});"
									.				"} catch ( e ) {"
									.					"messages = error;"
									.					"rawMessages = '\\n' + error;"
									.				"}"
									.			"}"
									.			"$( '.cbPkgInstallRow' + step ).addClass( 'cbPkgInstallRowFailed' );"
									.			"$( '.cbPkgInstallRow' + step + ' .cbPkgInstallState' ).addClass( 'hidden' );"
									.			"$( '.cbPkgInstallRow' + step + ' .cbPkgInstallStateFailed' ).removeClass( 'hidden' );"
									.			"$( '.cbPkgInstallRow' + step + '  .card' ).removeClass( 'border-info' ).addClass( 'border-danger' );"
									.			"if ( messages ) {"
									.				"$( '.cbPkgInstallRow' + step + ' .cbPkgInstallLog' ).removeClass( 'hidden' ).append( messages );"
									.				"$( '.cbPkgInstallRow' + step + ' .cbPkgInstallDetailCopyText' ).val( $( '.cbPkgInstallRow' + step + ' .cbPkgInstallDetailCopyText' ).val() + '\\n[code]' + rawMessages + '\\n[/code]' );"
									.			"}"
									.			"$( '.cbPkgInstaller .progress-bar' ).attr( 'aria-valuenow', progress ).css( 'width', progress + '%' );"
									.			"$( '.cbPkgInstaller .progress-bar .sr-only' ).html( progress + '% Complete' );"
									.			"$( '.cbPkgInstaller' ).triggerHandler( 'cbpackagebuilder.install.failed', [step, pkg] );"
									.			"beginInstallStep( ( step + 1 ) );"
									.		"};"
									.		"function beginInstallStep( step ) {"
									.			"if ( step in packages ) {"
									.				"packages[step]();"
									.			"}"
									.		"};"
									.		"beginInstallStep(0);";

		$return						.=		'</div>';

		if ( $this->plugin ) {
			$_CB_framework->outputCbJQuery( $js );
		} else {
			$js						.=	"});";

			if ( $this->jVersion() >= 6 ) {
				$js					.=	"});";
			}

			$return					.=		'<script type="text/javascript">' . $js . '</script>';
		}

		$return						.=	'</div>';

		echo $return;

		return true;
	}

	/**
	 * Parses for package type files with Joomla version dependency check
	 *
	 * @param string $type
	 * @param array  $packages
	 * @param int    $count
	 * @param null   $subDir
	 */
	private function directory_files( $type, &$packages, &$count = 0, $subDir = null )
	{
		global $_PLUGINS;

		if ( $this->plugin ) {
			$extensionDir								=	$_PLUGINS->getPluginPath( $this->plugin ) . '/extensions/' . $type . '/' . $subDir;
		} else {
			$extensionDir								=	JPATH_ADMINISTRATOR . '/components/com_packageinstaller/extensions/' . $type . '/' . $subDir;
		}

		if ( is_dir( $extensionDir ) ) {
			$files										=	scandir( $extensionDir );

			if ( $files ) {
				foreach ( $files as $file ) {
					if ( ( $file != '.' ) && ( $file != '..' ) && ( $file != 'index.html' ) ) {
						if ( is_dir( $extensionDir . $file ) ) {
							$this->directory_files( $type, $packages, $count, $subDir . $file . '/' );
						} else {
							$package					=	null;

							switch ( $type ) {
								case 'scripts':
									if ( preg_match( '/^([\w+.-]+)\.php$/', $file ) ) {
										$package		=	$file;
									}
									break;
								case 'queries':
									if ( preg_match( '/^([\w+.-]+)\.(sql|txt)$/', $file ) ) {
										$package		=	$file;
									}
									break;
								case 'custom': // We'll filter to some safe types here, but technically custom can be anything:
									if ( preg_match( '/^([\w+.-]+)\.(zip|rar|doc|pdf|txt|xls|jpg|jpeg|gif|png)$/', $file ) ) {
										$package		=	$file;
									}
									break;
								default:
									if ( preg_match( '/^([\w+.-]+)\.zip$/', $file ) ) {
										$package		=	$file;
									}
									break;
							}

							if ( $package ) {
								$include				=	true;
								$jVersion				=	$this->jVersion();

								if ( $subDir ) {
									$folderCMS			=	preg_match( '%^j((\d)\.?(\d))/%', $subDir, $matches );

									if ( $folderCMS ) {
										$folderVersion	=	$this->jVersion( $matches[2] . '.' . $matches[3] );

										if ( $jVersion < $folderVersion ) {
											$include	=	false;
										}
									}
								}

								if ( $include ) {
									$packageCMS			=	preg_match( '/j((\d)\.?(\d))/', $package, $matches );

									if ( $packageCMS ) {
										$packageVersion	=	$this->jVersion( $matches[2] . '.' . $matches[3] );

										if ( $jVersion < $packageVersion ) {
											$include	=	false;
										}
									}
								}

								if ( $include ) {
									$count				+=	1;
									$packages[$type][]	=	array(	'path'		=>	$subDir . $package,
																	'type'		=>	$type,
																	'file'		=>	$package,
																	'details'	=>	$this->getPackageDetails( $extensionDir . $file, $package )
																);
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Converts folder to package type
	 *
	 * @param string $folder
	 * @return string
	 */
	private function getPackageType( $folder )
	{
		switch ( $folder ) {
			case 'cb_plugins':
				return 'Community Builder Plugin';
			case 'components':
				return 'Joomla Component';
			case 'custom':
				return 'Custom';
			case 'languages':
				return 'Joomla Language';
			case 'libraries':
				return 'Joomla Library';
			case 'modules':
				return 'Joomla Module';
			case 'overrides':
				return 'Override';
			case 'packages':
				return 'Joomla Package';
			case 'plugins':
				return 'Joomla Plugin';
			case 'queries':
				return 'Query';
			case 'scripts':
				return 'Script';
			case 'templates':
				return 'Joomla Template';
			default:
				return $folder;
		}
	}

	/**
	 * @param string $file
	 * @param string $name
	 * @return array
	 */
	private function getPackageDetails( $file, $name )
	{
		$details	=	array(	'name'			=>	$name,
								'description'	=>	null,
								'version'		=>	null,
								'date'			=>	null
							);

		// Try to extract the details from generic filename structure to establish defaults:
		if ( preg_match( '/^(?:\d+(?:_|-|\.))?([a-zA-Z-_]+)(?:(?:_|-|\.)([.\d]+))(?:\+(?:build(?:_|-|\.))?(\d{4}(?:_|-|\.)\d{2}(?:_|-|\.)\d{2}))/', $name, $pkgDetails ) ) {
			if ( isset( $pkgDetails[1] ) && $pkgDetails[1] ) {
				$details['name']	=	$pkgDetails[1];
			}

			if ( isset( $pkgDetails[2] ) && $pkgDetails[2] ) {
				$details['version']	=	$pkgDetails[2];
			}

			if ( isset( $pkgDetails[3] ) && $pkgDetails[3] ) {
				$details['date']	=	$pkgDetails[3];
			}
		}

		if ( strpos( $name, '.zip' ) === false ) {
			return $details;
		}

		// Now lets see if we can find the XML file and pull the details from it so we can have a more informed display:
		try {
			$zip					=	new ZipArchive();

			if ( $zip->open( $file ) === true ) {
				for ( $i = 0; $i < $zip->numFiles; $i++ ) {
					$zipFile		=	$zip->getNameIndex( $i );

					// Use the first xml file found:
					if ( strpos( $zipFile, '.xml' ) !== false ) {
						$xml						=	new SimpleXMLElement( $zip->getFromIndex( $i ) );
						$name						=	(string) $xml->name;

						if ( $name ) {
							$details['name']		=	$name;
						}

						$description				=	(string) $xml->description;

						if ( $description ) {
							$details['description']	=	$description;
						}

						$version					=	(string) $xml->version;

						if ( $version ) {
							$details['version']		=	$version;
						}

						$release					=	(string) $xml->release;

						if ( $release ) {
							$details['version']		=	$release;
						}

						$date						=	(string) $xml->creationDate;

						if ( $date ) {
							$details['date']		=	$date;
						}

						break;
					}
				}
			}

			$zip->close();
		} catch ( Exception $e ) {}

		return $details;
	}

	/**
	 * Returns Joomla version
	 *
	 * @param string $v
	 * @return int
	 */
	private function jVersion( $v = null )
	{
		static $cache			=	array();

		if ( ! $v ) {
			$v					=	'joomla';
		}

		if ( ! isset( $cache[$v] ) ) {
			$version			=	'';
			$release			=	'';

			if ( ! in_array( $v, array( 'joomla', 'version', 'release' ) ) ) {
				$release		=	substr( $v, 0, 3 );
			} else {
				if ( class_exists( '\Joomla\CMS\Version' ) ) {
					$jVersion	=	new \Joomla\CMS\Version();
					$version	=	$jVersion->getShortVersion();
					$release	=	substr( $jVersion->getShortVersion(), 0, 3 );
				} elseif ( class_exists( 'JVersion' ) ) {
					$jVersion	=	new JVersion();
					$version	=	$jVersion->RELEASE;
					$release	=	substr( $jVersion->RELEASE, 0, 3 );
				}
			}

			if ( $v == 'version' ) {
				$cache[$v]		=	$version;
			} elseif ( $v == 'release' ) {
				$cache[$v]		=	$release;
			} elseif ( strcasecmp( $release, '4.0' ) >= 0 ) {
				$cache[$v]		=	6;
			} elseif ( strcasecmp( $release, '3.0' ) >= 0 ) {
				$cache[$v]		=	5;
			} elseif ( strcasecmp( $release, '2.5' ) >= 0 ) {
				$cache[$v]		=	4;
			} elseif ( strcasecmp( $release, '1.7' ) >= 0 ) {
				$cache[$v]		=	3;
			} elseif ( strcasecmp( $release, '1.6' ) >= 0 ) {
				$cache[$v]		=	2;
			} else {
				$cache[$v]		=	1;
			}
		}

		return $cache[$v];
	}
}