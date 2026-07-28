<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/prices.php';
require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fields/fielditem.php';
require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/fieldmultiple.php';

class RSFormProFieldSurveyTable extends RSFormProFieldMultiple
{
	public $processing;

	// backend preview
	public function getPreviewInput()
	{
		return $this->getFormInput();
	}

	public function getName()
	{
		return $this->namespace.'['.$this->name.']';
	}
	
	// functions used for rendering in front view
	public function getFormInput()
	{
		$isAdmin       = Factory::getApplication()->isClient('administrator');
		$parsedAnswers = array();
		$attr		= $this->getAttributes();
		$additional = '';

		// Parse Additional Attributes
		if ($attr) {
			foreach ($attr as $key => $values) {
				$additional .= $this->attributeToHtml($key, $values);
			}
		}

		$html = '<div class="rsfp-surveytable-table-responsive">';
		$html .= '<fieldset class="rsfp-fieldset"><legend class="rsfp-visually-hidden">' . $this->escape($this->getProperty('CAPTION'))  . '</legend>';
		$html .= '<table class="' . implode(' ', $this->getTableClasses()) . '">';

		$data = array(
			'id' 			=> $this->getId(),
			'additional' 	=> $additional
		);

		$prices = RSFormProPrices::getInstance($this->formId);

		if ($answers = $this->getItems('ANSWERS'))
		{
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<td></td>';
			foreach ($answers as $answer)
			{
				$parsedAnswers[] = $answer = new RSFormProFieldItem($answer);

				if (!$isAdmin && $answer->flags['price'] !== false)
				{
					$prices->addPrice($data['id'], $answer->value, $answer->flags['price']);
				}

				$html .= '<th class="rsfp-surveytable-center">' . $this->escape($answer->label) . '</th>';
			}
			$html .= '</tr>';
			$html .= '</thead>';
		}
		if ($questions = $this->getItems('QUESTIONS'))
		{
			$data['count'] = count($questions);
			$html .= '<tbody>';
			$answerIndex = 0;
			$questionIndex = 0;
			foreach ($questions as $question)
			{
				$this->processing = $questionIndex;
				$question = new RSFormProFieldItem($question);

				if (!$isAdmin)
				{
					$data['name']   = $this->getName() . '[' . $questionIndex . ']';
				}
				$data['i'] 		= $answerIndex;
				$firstIndex     = $answerIndex;
				$data['item'] 	= $question;

				$html .= '<tr>';
				$html .= '<td>' . $this->buildLabel($data) . '</td>';
				if ($parsedAnswers)
				{
					foreach ($parsedAnswers as $answer)
					{
						$data['firstIndex'] = $firstIndex;
						$data['value'] 	= $this->getItemValue($answer);
						$data['i'] 		= $answerIndex;
						$data['item'] 	= $answer;

						$html .= '<td class="rsfp-surveytable-center" data-th="' . $this->escape($answer->label) . '">' . $this->buildInput($data) . '</td>';

						$answerIndex++;
					}
				}
				$html .= '</tr>';

				$questionIndex++;
			}
			$html .= '</tbody>';
		}

		$html .= '</table></legend></div>';

		return $html;
	}

	protected function getTableClasses()
	{
		return array('rsfp-surveytable-table');
	}

	protected function buildInput($data)
	{
		// For convenience
		extract($data);

		$html = '<input type="radio" data-rsfpsurveytable-answer="1" data-rsfpsurveytable-questions="' .  (int) $data['count'] . '" aria-labelledby="' . $this->escape($id) . $firstIndex . '-lbl"';

		// Disabled
		if ($item->flags['disabled']) {
			$html .= ' disabled="disabled"';
		}

		// Checked
		if ($item->value === $value) {
			$html .= ' checked="checked"';
		}

		// Name
		if (isset($name) && strlen($name)) {
			$html .= ' name="'.$this->escape($name).'"';
		}

		// Value
		$html .= ' value="'.$this->escape($item->value).'"';

		// Id
		$html .= ' id="'.$this->escape($id).$i.'"';

		// Additional HTML
		if (!empty($additional)) {
			$html .= $additional;
		}

		$html .= ' />';

		return $html;
	}

	protected function buildLabel($data)
	{
		// For convenience
		extract($data);

		return '<label id="'.$this->escape($id).$i.'-lbl" for="'.$this->escape($id).$i.'">'.$item->label.'</label>';
	}

	public function processValidation($validationType = 'form', $submissionId = 0)
	{
		$required = $this->isRequired();

		if ($validationType === 'form')
		{
			$values = $this->getValue();

			// Field is required but nothing is selected
			if ($required && !$values)
			{
				return false;
			}

			$questions = $this->getItems('QUESTIONS');

			if (($required || $values) && count($values) !== count($questions))
			{
				return false;
			}
		}
		else
		{
			if ($required && empty($this->value[$this->name]))
			{
				return false;
			}
		}

		return true;
	}

	public function processBeforeStore($submissionId, &$post, &$files, $addToDb = false)
	{
		if (!isset($post[$this->name]))
		{
			return false;
		}

		if ($questions = $this->getItems('QUESTIONS'))
		{
			$template = $this->getProperty('SURVEYTEMPLATE', '{question}: {answer}');
			$replace = array('{question}', '{answer}');
			$newValues = array();
			$originalValues = isset($post[$this->name]) ? $post[$this->name] : array();
			$questionIndex = 0;
			foreach ($questions as $question)
			{
				$with = array($question);
				if (isset($post[$this->name][$questionIndex]))
				{
					$with[] = $post[$this->name][$questionIndex];
				}
				else
				{
					$with[] = '';
				}
				$newValues[] = str_replace($replace, $with, $template);

				$questionIndex++;
			}
			$post[$this->name] = $newValues;
			$post['_JSON_' . $this->name] = json_encode($originalValues);
			if ($addToDb)
			{
				$db = Factory::getDbo();
				$object = (object) array(
					'SubmissionId'  => $submissionId,
					'FormId'        => $this->formId,
					'FieldName'     => '_JSON_' . $this->name,
					'FieldValue'    => $post['_JSON_' . $this->name]
				);
				$query = $db->getQuery(true)
					->delete('#__rsform_submission_values')
					->where($db->qn('SubmissionId') . ' = ' . $db->q($submissionId))
					->where($db->qn('FormId') . ' = ' . $db->q($this->formId))
					->where($db->qn('FieldName') . ' = ' . $db->q('_JSON_' . $this->name));
				$db->setQuery($query)->execute();
				$db->insertObject('#__rsform_submission_values', $object);
			}
		}
	}

	public function getValue()
	{
		// Actual value is set, return it
		return isset($this->value[$this->name]) && is_array($this->value[$this->name]) ? $this->value[$this->name] : array();
	}

	public function getItemValue($item)
	{
		// Default value processing
		if (empty($item))
		{
			return null;
		}

		// Value does not exist in request.
		if (!isset($this->value[$this->name]))
		{
			// Grab default [c]hecked value if no request present
			if ($item->flags['checked'] && (empty($this->value) || empty($this->value['formId'])))
			{
				return $item->value;
			}
		}
		else
		{
			// Value exists, grab it from request.
			if (isset($this->value[$this->name][$this->processing]))
			{
				$value = $this->value[$this->name][$this->processing];
				// Found a value
				if (in_array($item->value, (array) $value))
				{
					return $item->value;
				}
			}
		}

		return null;
	}
}