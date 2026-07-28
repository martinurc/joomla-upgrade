<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;

FormHelper::loadFieldClass('checkboxes');

/**
 * Form Field class for Easy Profile / Joomla.
 */
class JFormFieldCheckboxlist extends CheckboxesField
{
    public $type = 'Checkboxlist';

    public $isNested = null;
    
    public $table = null;

    protected $comParams = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Load com_jsn config
        $this->comParams = ComponentHelper::getParams('com_jsn');
    }

    protected function getInput()
    {
        $inline = (isset($this->element['optioninline']) && (string)$this->element['optioninline'] === '1') ? 'inline' : '';
        $html   = [];

        $from = ['class="checkbox"'];
        $to   = ['class="checkbox ' . $inline . '"'];

        $inputHtml = parent::getInput();
        if ($inputHtml !== null) {
            $html[] = str_replace($from, $to, $inputHtml);
        }

        if (isset($this->element['readonly']) && (string)$this->element['readonly'] === 'true') {
            $html[] = '<input type="hidden" value="1" name="' . htmlspecialchars($this->name ?? '', ENT_QUOTES, 'UTF-8') . '"/>';
        }

        return implode('', $html);
    }

    public function getOptions()
    {
        $table = (string)($this->element['dbopttable'] ?? '');
        $value = (string)($this->element['dboptvalue'] ?? '');
        $text  = (string)($this->element['dbopttext'] ?? '');

        if (!empty($table) && !empty($value) && !empty($text)) {    
            $db    = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->getQuery(true);
            $query->select($db->quoteName($value) . ' AS value, ' . $db->quoteName($text) . ' AS text')
                  ->from($db->quoteName($table));

            $dboptwhere = (string)($this->element['dboptwhere'] ?? '');

            if (!empty($dboptwhere)) {
                PluginHelper::importPlugin('content');
                $where = HTMLHelper::_('content.prepare', $dboptwhere, 'customwhere', 'com_finder.indexer');

                if (empty($this->value)) {
                    $query->where($where);
                } elseif (is_array($this->value)) {
                    $valArray = $this->value;
                    foreach ($valArray as &$val) {
                        $val = $db->quote($val);
                    }
                    $valueString = implode(',', $valArray);
                    $query->where('(' . $where . ' OR ' . $db->quoteName($value) . ' IN (' . $valueString . '))');
                } else {
                    $query->where('(' . $where . ' OR ' . $db->quoteName($value) . ' = ' . $db->quote((string)$this->value) . ')');
                }
            }
            
            $query->order('text');
            $db->setQuery($query);

            try {
                $options = (array) $db->loadObjectList();
            } catch (\Throwable $e) {
                return parent::getOptions();
            }
            
            foreach ($options as &$option) {
                $option->text    = Text::_($option->text);
                $option->checked = null;
            }

            // Merge any additional options in the fields params
            $parentOptions = parent::getOptions();
            return array_merge($parentOptions, $options);
        }

        return parent::getOptions();
    }

    protected function getLayoutData()
    {
        $hasValue = (isset($this->value) && !empty($this->value));
        if ($hasValue && is_object($this->value)) {
            $this->value = (array) $this->value;
        }
        return parent::getLayoutData();
    }
}