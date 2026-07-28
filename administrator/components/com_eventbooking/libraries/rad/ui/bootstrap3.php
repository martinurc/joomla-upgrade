<?php
/**
 * @package     MPF
 * @subpackage  UI
 *
 * @copyright   Copyright (C) 2016 - 2018 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Base class for a Joomla Administrator Controller. It handles add, edit, delete, publish, unpublish records....
 *
 * @package       MPF
 * @subpackage    UI
 * @since         2.0
 */
class RADUiBootstrap3 extends RADUiAbstract implements RADUiInterface
{
	/**
	 * UIKit framework classes
	 *
	 * @var array
	 */
	protected $frameworkClasses = [
		'form-control',
	];

	/**
	 * Constructor
	 *
	 * @param   array  $classMaps
	 */
	public function __construct($classMaps = [])
	{
		if (empty($classMaps))
		{
			$classMaps = [
				// Grid
				'row-fluid'       => 'row',
				'span1'           => 'col-md-1',
				'span2'           => 'col-md-2',
				'span3'           => 'col-md-3',
				'span4'           => 'col-md-4',
				'span5'           => 'col-md-5',
				'span6'           => 'col-md-6',
				'span7'           => 'col-md-7',
				'span8'           => 'col-md-8',
				'span9'           => 'col-md-9',
				'span10'          => 'col-md-10',
				'span11'          => 'col-md-11',
				'span12'          => 'col-md-12',
				// Form classes
				'form'            => 'form',
				'form-horizontal' => 'form-horizontal',
				'control-group'   => 'form-group',
				'control-label'   => 'col-md-3 control-label',
				'controls'        => 'col-md-9 eb-form-control',
				// Button classes
				'btn'             => 'btn btn-default',
				'btn-primary'     => 'btn-primary',
				'btn-info'        => 'btn-info',
				'btn-success'     => 'btn-success',
				'btn-warning'     => 'btn-warning',
				'btn-danger'      => 'btn-danger',
				'btn-inverse'     => 'btn-primary',
				'btn-link'        => 'btn-link',
				// Image classes
				'thumbnail'       => 'img-thumbnail',
				'img-polaroid'    => 'img-thumbnail',
				'img-rounded'     => 'img-rounded',
				'img-circle'      => 'img-circle',
				'img-responsive'  => 'img-responsive',
				// Table classes
				'table'           => 'table',
				'table-striped'   => 'table-striped',
				'table-bordered'  => 'table-bordered',
				'table-condensed' => 'table-condensed',
				'table-hover'     => 'table-hover',
				// Badge classes
				'badge'           => 'badge',
				'badge-success'   => 'bg-success',
				'badge-warning'   => 'bg-warning',
				'badge-info'      => 'bg-info',
				'badge-danger'    => 'bg-danger',
				// Text classes
				'text-muted'      => 'text-muted',
				'text-warning'    => 'text-warning',
				'text-error'      => 'text-error',
				'text-info'       => 'text-info',
				'text-success'    => 'text-success',
				// Text Alignment
				'text-left'       => 'text-left',
				'text-center'     => 'text-center',
				'text-right'      => 'text-right',
				// Form input sizes
				'input-mini'      => 'input-mini',
				'input-small'     => 'input-small',
				'input-medium'    => 'input-medium',
				'input-large'     => 'input-large',
				'input-xlarge'    => 'input-xlarge',
				'input-xxlarge'   => 'input-xxlarge',
				// Button sizes
				'btn-mini'        => 'btn-xs',
				'btn-small'       => 'btn-sm',
				'btn-large'       => 'btn-lg',
				// Responsive utilities
				'visible-phone'   => 'visible-xs',
				'visible-tablet'  => 'visible-sm',
				'visible-desktop' => 'visible-md visible-lg',
				'hidden-phone'    => 'hidden-xs',
				'hidden-tablet'   => 'hidden-sm',
				'hidden-desktop'  => 'hidden-md hidden-lg',
				// Utility classes
				'pull-left'       => 'pull-left',
				'pull-right'      => 'pull-right',
				'clearfix'        => 'clearfix',
				'input-prepend'   => 'input-group',
				'input-append'    => 'input-group',
				'add-on'          => 'input-group-addon',
				'nav'             => 'nav',
				'nav-pills'       => 'nav-pills',
				'nav-stacked'     => 'nav-stacked',
				'nav-tabs'        => 'nav-tabs',
				// Background Color classes
				'bg-primary'      => 'bg-primary',
			];
		}

		$this->classMaps = $classMaps;
	}

	/**
	 * Method to render input with prepend add-on
	 *
	 * @param   string  $input
	 * @param   string  $addOn
	 *
	 * @return mixed
	 */
	public function getPrependAddon($input, $addOn)
	{
		$html   = [];
		$html[] = '<div class="input-group">';
		$html[] = '<div class="input-group-addon">' . $addOn . '</div>';
		$html[] = $input;
		$html[] = '</div>';

		return implode("\n", $html);
	}

	/**
	 * Method to render input with append add-on
	 *
	 * @param   string  $input
	 * @param   string  $addOn
	 *
	 * @return mixed
	 */
	public function getAppendAddon($input, $addOn)
	{
		$html   = [];
		$html[] = '<div class="input-group">';
		$html[] = $input;
		$html[] = '<div class="input-group-addon">' . $addOn . '</div>';
		$html[] = '</div>';

		return implode("\n", $html);
	}
}
