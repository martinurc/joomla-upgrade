<?php

/**
 * @package     JCE
 * @subpackage  Editors.Jce
 *
 * @copyright   Copyright (C) 2005 - 2023 Open Source Matters, Inc. All rights reserved.
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\JcePro\PluginTraits;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Filter\InputFilter;

use WFApplication;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

trait CustomQueryTrait
{
    /**
     * Get the query variables from the request.
     *
     * @return array Associative array of query variables.
     */

    protected function getQueryVarsFromRequest()
    {
        $app   = Factory::getApplication();
        $input = $app->input;

        $option = $input->getCmd('option', '');

        // Use the known-safe, typed fetch for this one
        if ($option === 'com_jce') {
            $query = $input->get('profile_custom', [], 'array');
        } else {
            // Avoid Input::getArray() (Joomla 6.x bug trigger)
            $query = array_merge($_GET ?? [], $_POST ?? []);

            // get wf_catid from input API
            $catid = $input->getInt('wf_catid', 0);

            if ($catid > 0) {
                $query['catid'] = $catid;
            }
        }

        $filter = InputFilter::getInstance();
        $vars   = [];

        $ignore = ['option', 'csrf.token', 'token'];

        foreach ($query as $key => $value) {
            // Normalise key
            if (!is_string($key)) {
                continue;
            }

            // Ignore keys that are in the ignore list
            if (in_array($key, $ignore, true)) {
                continue;
            }

            // Keep keys "safe": allow only typical query-string names
            $key = preg_replace('#[^a-zA-Z0-9_\-]#', '', $key);

            if ($key === '') {
                continue;
            }

            // Clean value:
            // - arrays are kept (and recursively cleaned as strings)
            // - scalars are cast to string and cleaned
            $vars[$key] = $this->cleanQueryValue($filter, $value);
        }

        return $vars;
    }

    protected function cleanQueryValue(InputFilter $filter, $value)
    {
        if (is_array($value)) {
            $out = [];

            foreach ($value as $k => $v) {
                // keep numeric indexes; clean string indexes similarly to above if you need
                $out[$k] = $this->cleanQueryValue($filter, $v);
            }

            return $out;
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        // Cast to string and clean
        return $filter->clean((string) $value, 'STRING');
    }

    private function checkValue($actual, $expected)
    {
        $negated = false;

        // check if this is a negated value
        if (substr($expected, 0, 1) === '!') {
            $negated  = true;
            $expected = substr($expected, 1);
        }

        if ($negated) {
            // must not match the expected value
            if ($actual == $expected) {
                return false;
            }
        } else {
            // must match the expected value
            if ($actual != $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the provided query array matches custom conditions.
     *
     * @param array $custom The custom conditions array with keys and expected values.
     * @return bool Returns true if conditions in the custom array are met by the query array, false otherwise.
     */
    private function checkCustomQueryVars($custom)
    {
        $query = $this->getQueryVarsFromRequest();

        foreach ($custom as $key => $expected) {
            $actual = isset($query[$key]) ? $query[$key] : '';

            if (!is_array($expected)) {
                if (!$this->checkValue($actual, $expected)) {
                    return false;
                }
            } else {
                $matched = false;

                foreach ($expected as $expectedValue) {
                    if ($this->checkValue($actual, $expectedValue)) {
                        $matched = true;
                        break;
                    }
                }

                if (!$matched) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Converts an array of name-value pairs into an associative array.
     *
     * This function takes an array of associative arrays with 'name' and 'value' keys
     * and re-maps them into a single associative array. Values are aggregated into
     * arrays under their respective names, ensuring unique values are kept and empty
     * values are ignored.
     *
     * @param array $values An array of associative arrays with 'name' and 'value' keys.
     * @return array The resulting associative array.
     */
    private function convertNameValuePairsToAssociativeArray($values)
    {
        $associativeArray = [];

        // Re-map name|value pairs to associative array
        foreach ($values as $value) {
            if (array_key_exists('name', $value) && array_key_exists('value', $value)) {
                // Empty values are ignored
                if ($value['value'] == '') {
                    continue;
                }

                // Initialize the array for this name if not already set
                if (!isset($associativeArray[$value['name']])) {
                    $associativeArray[$value['name']] = [];
                }

                // Append the value, ensuring unique values only
                if (!in_array($value['value'], $associativeArray[$value['name']])) {
                    $associativeArray[$value['name']][] = $value['value'];
                }
            }
        }

        return $associativeArray;
    }

    /**
     * Filters query keys extracted from the request by custom values stored in the profile.
     *
     * @param array $inputArray The input array of expected values.
     * @param array $outputArray The array to populate with matching values.
     */
    private function filterQueryVarsByCustomValues($inputArray, &$outputArray)
    {
        $query = $this->getQueryVarsFromRequest();

        foreach ($inputArray as $key => $expectedValue) {
            if (!isset($query[$key])) {
                continue;
            }

            $outputArray[$key] = $expectedValue;
        }
    }

    /**
     * Update the Media Field URL with custom query values.
     *
     * @param array  $data Media Field data array containing the URL.
     * @param object $profile Editor Profile object.
     * @return void
     */
    public function onWfMediaFieldGetOptions(&$data, $profile = null)
    {
        if (empty($data)) {
            return;
        }

        if (empty($data['url'])) {
            return;
        }

        if (!is_object($profile)) {
            return;
        }

        $app = Factory::getApplication();

        $params = new Registry($profile->params);

        // get custom query variables from parameters if set
        $customParams = $params->get('setup.custom', []);

        // no custom query variables to process
        if (empty($customParams)) {
            return;
        }

        // process to array
        $custom = (new Registry($customParams))->toArray();

        // invalid custom query variables
        if (empty($custom)) {
            return;
        }

        $vars = $this->convertNameValuePairsToAssociativeArray($custom);

        if (empty($vars)) {
            return;
        }

        // assign custom query values
        $options = [
            'profile_custom' => $app->input->get('profile_custom', [], 'ARRAY'),
        ];

        // process custom query values to remove invalid values
        $this->filterQueryVarsByCustomValues($vars, $options['profile_custom']);

        // update the URL with the custom query values
        $data['url'] .= '&' . http_build_query($options);
    }

    /**
     * Pass the custom query variables to the editor profile options.
     *
     * @param array $options Editor Profile options array.
     * @return void
     */
    public function onWfEditorProfileOptions(&$options)
    {
        $options['custom'] = $this->getQueryVarsFromRequest();
    }

    public function onWfBeforeEditorProfileItem(&$item)
    {
        $app = Factory::getApplication();

        // check custom query variables
        if (!empty($item->params)) {
            $params = new Registry($item->params);

            // get custom query variables from parameters if set
            $customParams = $params->get('setup.custom', []);

            // no custom query variables to process
            if (empty($customParams)) {
                return;
            }

            // process to array
            $custom = (new Registry($customParams))->toArray();

            // invalid custom query variables
            if (empty($custom)) {
                return;
            }

            $vars = $this->convertNameValuePairsToAssociativeArray($custom);

            if (empty($vars)) {
                return;
            }

            // no valid custom query variables
            if ($this->checkCustomQueryVars($vars) === false) {
                $item = false;
            }
        }
    }

    public function onBeforeWfEditorSettings(&$settings)
    {
        $values = $this->getQueryVarsFromRequest();

        if (empty($values)) {
            return;
        }

        // get an editor instance
        $wf = WFApplication::getInstance();

        // get custom query variables from parameters if set
        $custom = $wf->getParam('setup.custom', []);

        if (empty($custom)) {
            return;
        }

        $vars = $this->convertNameValuePairsToAssociativeArray($custom);

        // no custom query variables to process
        if (empty($vars)) {
            return;
        }

        $custom = [];

        $this->filterQueryVarsByCustomValues($vars, $custom);

        $settings['query'] = array_merge($settings['query'], ['profile_custom' => $custom]);
    }
}
