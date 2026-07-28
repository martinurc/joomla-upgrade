<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filter\InputFilter;
use Joomla\String\StringHelper;

trait EventbookingModelFilter
{
	/**
	 * Method to allow filtering form data
	 *
	 * @param   array  $rowFields
	 * @param   array  $data
	 *
	 * @return  array
	 */
	public function filterFormData($rowFields, $data)
	{
		$inputFilter = InputFilter::getInstance();

		foreach ($rowFields as $rowField)
		{
			if (!$rowField->filter || !isset($data[$rowField->name]))
			{
				continue;
			}

			switch ($rowField->filter)
			{
				case 'UPPERCASE':
					$data[$rowField->name] = $inputFilter->clean($data[$rowField->name], 'STRING');
					$data[$rowField->name] = StringHelper::strtoupper($data[$rowField->name]);
					break;
				case 'LOWERCASE':
					$data[$rowField->name] = $inputFilter->clean($data[$rowField->name], 'STRING');
					$data[$rowField->name] = StringHelper::strtolower($data[$rowField->name]);
					break;
				case 'TRIM':
					$data[$rowField->name] = $inputFilter->clean($data[$rowField->name], 'STRING');
					$data[$rowField->name] = StringHelper::rtrim($data[$rowField->name]);
					break;
				case 'LTRIM':
					$data[$rowField->name] = $inputFilter->clean($data[$rowField->name], 'STRING');
					$data[$rowField->name] = StringHelper::ltrim($data[$rowField->name]);
					break;
				case 'RTRIM':
					$data[$rowField->name] = $inputFilter->clean($data[$rowField->name], 'STRING');
					$data[$rowField->name] = StringHelper::trim($data[$rowField->name]);
					break;
				case 'UCFIRST':
					$data[$rowField->name] = $inputFilter->clean($data[$rowField->name], 'STRING');
					$data[$rowField->name] = StringHelper::ucfirst($data[$rowField->name]);
					break;
				case 'UCWORDS':
					$data[$rowField->name] = $inputFilter->clean($data[$rowField->name], 'STRING');
					$data[$rowField->name] = StringHelper::ucwords($data[$rowField->name]);
					break;
				default:
					$data[$rowField->name] = $inputFilter->clean($data[$rowField->name], $rowField->name);
					break;
			}
		}

		return $data;
	}
}
