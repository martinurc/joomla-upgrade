<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR.'/components/com_rsform/helpers/field.php';

class RSFormProFieldPhonenumber extends RSFormProField
{
	// backend preview
	public function getPreviewInput()
	{
		$value 		 = (string) $this->getProperty('DEFAULTVALUE', '');
		$size 		 = $this->getProperty('SIZE', 0);
		$placeholder = $this->getProperty('PLACEHOLDER', '');
		$codeIcon 	 = '';
		
		if ($this->hasCode($value)) {
			$value 		= Text::_('RSFP_PHP_CODE_PLACEHOLDER');
			$codeIcon	= RSFormProHelper::getIcon('php');
		}

		return $codeIcon . '<span class="rsficon rsficon-phone1"></span> <input type="text" value="'.$this->escape($value).'" size="'.(int) $size.'" '.(!empty($placeholder) ? 'placeholder="'.$this->escape($placeholder).'"' : '').'/>';
	}
	
	// functions used for rendering in front view
	public function getFormInput()
    {
		$this->addCommonTranslations();
        $this->addStyleSheet(HTMLHelper::_('stylesheet', 'com_rsform/intlTelInput.css', array('pathOnly' => true, 'relative' => true)));
        $this->addScript(HTMLHelper::_('script', 'com_rsform/intl-tel-input/intlTelInputWithUtils.js', array('pathOnly' => true, 'relative' => true)));
        $this->addScript(HTMLHelper::_('script', 'com_rsform/phonenumber.script.js', array('pathOnly' => true, 'relative' => true)));

		$value 			= (string) $this->getValue();
		$name 			= $this->getName();
		$id 			= $this->getId();
		$size 			= $this->getProperty('SIZE', 0);
		$placeholder 	= $this->getProperty('PLACEHOLDER', '');
		$attr 			= $this->getAttributes();
        $countries      = $this->getProperty('ONLYCOUNTRIES', '');
		$additional 	= '';
	    $initialCountry = $this->getProperty('INITIALCOUNTRY', 'AUTODETECTCOUNTRY');
		if ($initialCountry === 'AUTODETECTCOUNTRY')
		{
			$initialCountry = 'auto';
		}
		else
		{
			$initialCountry = trim(strtolower($this->getProperty('MANUALCOUNTRY', 'US')));
		}

        $options = array(
            'validation'    => $this->getProperty('VALIDATIONPHONE') ? 'precise' : 'simple',
            'showFlags'     => $this->getProperty('HIDEFLAGS') ? false : true,
            'allowDropdown' => $this->getProperty('HIDECOUNTRYDROPDOWN') ? false : true,
            'onlyCountries' => !empty($countries) ? RSFormProHelper::explode($this->getProperty('ONLYCOUNTRIES', '')) : array(),
            'initialCountry' => $initialCountry,
	        'i18n'           => array()
        );

		$lang = Factory::getLanguage();
		$translations = array('AF','AX','AL','DZ','AS','AD','AO','AI','AQ','AG','AR','AM','AW','AU','AT','AZ','BS','BH','BD','BB','BY','BE','BZ','BJ','BM','BT','BO','BA','BW','BV','BR','IO','VG','BN','BG','BF','BI','KH','CM','CA','CV','BQ','KY','CF','TD','CL','CN','CX','CC','CO','KM','CG','CD','CK','CR','CI', 'HR','CU','CW','CY','CZ','DK','DJ','DM','DO','EC','EG','SV','GQ','ER','EE','SZ','ET','FK','FO','FJ','FI','FR','GF','PF','TF','GA','GM','GE','DE','GH','GI','GR','GL','GD','GP','GU','GT','GG','GN','GW','GY','HT','HM','HN','HK','HU','IS','IN','ID','IR','IQ','IE','IM','IL','IT','JM','JP','JE','JO','KZ','KE','KI','KW','KG','LA','LV','LB','LS','LR','LY','LI','LT','LU','MO','MG','MW','MY','MV','ML','MT','MH','MQ','MR','MU','YT','MX','FM','MD','MC','MN','ME','MS','MA','MZ','MM','NA','NR','NP','NL','NC','NZ','NI','NE','NG','NU','NF','KP','MK','MP','NO','OM','PK','PW','PS','PA','PG','PY','PE','PH','PN','PL','PT','PR','QA','RE','RO','RU','RW','WS','SM','ST','SA','SN','RS','SC','SL','SG','SX','SK','SI','SB','SO','ZA','GS','KR','SS','ES','LK','BL','SH','KN','LC','MF','PM','VC','SD','SR','SJ','SE','CH','SY','TW','TJ','TZ','TH','TL','TG','TK','TO','TT','TN','TR','TM','TC','TV','UM','VI','UG','UA','AE','GB','US','UY','UZ','VU','VA','VE','VN','WF','EH','YE','ZM','ZW');
		foreach ($translations as $translation)
		{
			if ($lang->hasKey('COM_RSFORM_PHONENUMBER_COUNTRY_' . $translation))
			{
				$options['i18n'][strtolower($translation)] = $lang->_('COM_RSFORM_PHONENUMBER_COUNTRY_' . $translation);
			}
		}
		
		$html = '<input data-rsfp-phonenumber="' . $this->escape(json_encode($options)) . '"';
		if (Factory::getDocument()->direction === 'rtl')
		{
			$html .= ' dir="rtl"';
		}
		if ($attr) {
			foreach ($attr as $key => $values) {
				// @new feature - Some HTML attributes (type, size, maxlength) can be overwritten
				// directly from the Additional Attributes area
				if (($key == 'type' || $key == 'size' || $key == 'maxlength') && strlen($values)) {
					${$key} = $values;
					continue;
				}
				$additional .= $this->attributeToHtml($key, $values);
			}
		}
		// Set the type & value
		$html .= ' type="tel"'.
				 ' value="'.$this->escape($value).'"';
		// Size
		if ($size) {
			$html .= ' size="'.(int) $size.'"';
		}
		
		// Placeholder
		if (!empty($placeholder)) {
			$html .= ' placeholder="'.$this->escape($placeholder).'"';
		}
		
		// Name & id
		$html .= ' name="'.$this->escape($name).'"'.
				 ' id="'.$this->escape($id).'"';
		// Additional HTML
		$html .= $additional;
		// Close the tag
		$html .= ' />';
		
		return $html;
	}
	
	public function getValue()
    {
        if (isset($this->value[$this->name]))
        {
            $input = Factory::getApplication()->input;
            if ($collection = $input->get('hidden_phone', array(), 'array'))
            {
                if (isset($collection[$this->name]))
                {
                    $this->value[$this->name] = $collection[$this->name];
                }
            }
        }
		
		return parent::getValue();
	}
	
	public function getAttributes() {
		$attr = parent::getAttributes();
		if (strlen($attr['class'])) {
			$attr['class'] .= ' ';
		}
		$attr['class'] .= 'rsform-input-box';
		
		return $attr;
	}

    public function processBeforeStore($submissionId, &$post, &$files)
    {
        if (!isset($post[$this->name]))
        {
            return false;
        }

        $input = Factory::getApplication()->input;
        if ($collection = $input->get('hidden_phone', array(), 'array'))
        {
            if (isset($collection[$this->name]))
            {
                $post[$this->name] = $collection[$this->name];
            }
        }
    }

	private function addCommonTranslations()
	{
		static $done;

		if (!$done)
		{
			$done = true;
			$messages = array();
			foreach (array('COM_RSFORM_PHONENUMBER_SELECTEDCOUNTRYARIALABEL', 'COM_RSFORM_PHONENUMBER_NOCOUNTRYSELECTED', 'COM_RSFORM_PHONENUMBER_COUNTRYLISTARIALABEL', 'COM_RSFORM_PHONENUMBER_SEARCHPLACEHOLDER', 'COM_RSFORM_PHONENUMBER_ZEROSEARCHRESULTS', 'COM_RSFORM_PHONENUMBER_ONESEARCHRESULT', 'COM_RSFORM_PHONENUMBER_MULTIPLESEARCHRESULTS') as $key)
			{
				$messages[] = array($key, Text::_($key));
			}

			$this->addScriptDeclaration('RSFormPro.Translations.addCommonTranslations(' . json_encode($messages) . ');');
		}
	}
}