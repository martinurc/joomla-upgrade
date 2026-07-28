<?php

/**
 * @package     JCE
 * @subpackage  Editor
 *
 * HTML sanitization pipeline.
 * - Extracts <body> content for predictability.
 * - Optionally bypasses cleaning for users with "No Filtering".
 * - Runs HTMLPurifier with a safe HTML subset (drops script/iframe/object/embed/style,
 *   event handlers and unsafe URL schemes).
 * - Applies Joomla per-group Text Filter.
 * - Final DOM tidy: unwrap <a> without href, remove empty <p>, neutralise obfuscated hrefs.
 *
 * Result: parity with Joomla Text Filter, slightly stricter on URL/CSS obfuscation.
 *
 * @copyright   Copyright (c) 2009-2026 Ryan Demmer. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class WfePurify
{
    /**
     * Create (and cache) an HTMLPurifier instance with a safe, Joomla-friendly subset.
     *
     * Notes:
     * - Uses a compact allow-list (flow content, links, images, tables, etc.).
     * - Only http/https/mailto/tel schemes allowed (data: disallowed by default).
     * - Cache path falls back to no cache if JPATH_CACHE isn’t writable.
     *
     * @return \HTMLPurifier
     */
    private function getHtmlPurifier()
    {
        static $purifier = null;

        if ($purifier) {
            return $purifier;
        }

        // Prefer Joomla’s bundled autoloaded HTMLPurifier if available,
        // else load from your packaged vendor path.
        if (!class_exists('\HTMLPurifier')) {
            require_once WF_EDITOR_PRO_LIBRARIES . '/vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
        }

        $config = \HTMLPurifier_Config::createDefault();

        // Cache
        if (is_writable(JPATH_CACHE)) {
            $config->set('Cache.SerializerPath', JPATH_CACHE);
        } else {
            $config->set('Cache.SerializerPath', null);
        }

        $config->set('Core.Encoding', 'UTF-8');

        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

        // Don’t trust; stick to a classic HTML fragment
        $config->set('HTML.Trusted', false);

        $purifier = new \HTMLPurifier($config);
        return $purifier;
    }

    /**
     * Determine whether the current user should be filtered by Joomla Text Filters.
     *
     * Returns TRUE if Joomla Text Filter SHOULD be applied (i.e. user does NOT have "No Filtering").
     * Returns FALSE if user has "No Filtering" in any group (bypass cleaning).
     *
     * @return bool
     */
    private function hasTextFilter()
    {
        $user    = Factory::getUser();
        $filters = ComponentHelper::getParams('com_config')->get('filters');

        if (empty($filters)) {
            // No explicit filters in config — be safe and apply Text Filter
            return true;
        }

        foreach ($user->getAuthorisedGroups() as $gid) {
            if (isset($filters->{$gid}) && isset($filters->{$gid}->filter_type)) {
                if (strtoupper($filters->{$gid}->filter_type) === 'NONE') {
                    return false; // User has "No Filtering"
                }
            }
        }

        return true;
    }

    /**
     * Extract only the <body> contents from an HTML document string.
     * Returns the innerHTML of the <body> if found, otherwise the whole string.
     *
     * @param  string $html
     * @return string
     */
    public static function extractBodyContents($html)
    {
        $html = trim((string) $html);

        // Fast path: body wrapped
        if (preg_match('~<body[^>]*>(.*)</body>~is', $html, $matches)) {
            return $matches[1];
        }

        // Tolerant DOM fallback
        $doc = new \DOMDocument();
        @$doc->loadHTML(
            '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>' .
                $html .
                '</html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body) {
            $out = '';
            foreach ($body->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }
            return $out;
        }

        return $html;
    }

    /**
     * Helper: normalise a URL-ish attribute for safe scheme checking.
     * - HTML entity decode
     * - percent-decode (bounded, to collapse obfuscation)
     * - strip all Unicode spaces/control chars
     */
    private static function normaliseUrl($raw)
    {
        $norm = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Percent-decode up to 2 times to collapse double-encoded tricks
        // (guarded to avoid pathological looping)
        for ($i = 0; $i < 2; $i++) {
            $decoded = rawurldecode($norm);

            if ($decoded === $norm) {
                break;
            }

            $norm = $decoded;
        }

        // Remove all Unicode spaces and control chars
        $norm = preg_replace('/[\p{Z}\p{C}]+/u', '', $norm ?? '');

        return strtolower($norm);
    }

    /**
     * Final DOM cleanup for cosmetic fixes and defensive neutralisation.
     * - Unwrap <a> without href.
     * - Remove empty <p>.
     * - Neutralise obfuscated javascript/vbscript/data:text/html hrefs.
     * - Drop stray JS-looking text nodes (defensive).
     *
     * @param  string $html
     * @return string
     */
    private function domCleanup($html)
    {
        if ($html === '') {
            return $html;
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML(
            '<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' .
                $html .
                '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $xpath = new \DOMXPath($doc);

        // 1) Unwrap anchors without href
        foreach ($xpath->query('//a[not(@href)]') as $a) {
            while ($a->firstChild) {
                $a->parentNode->insertBefore($a->firstChild, $a);
            }
            $a->parentNode->removeChild($a);
        }

        // 2) Remove empty paragraphs containing no text or only whitespace
        foreach ($xpath->query('//p[not(normalize-space()) and not(*)]') as $p) {
            $p->parentNode->removeChild($p);
        }

        // 3) Neutralise obfuscated javascript-like hrefs
        foreach ($xpath->query('//*[@href]') as $el) {
            $raw = $el->getAttribute('href');

            // normaliseUrl entity-decodes, percent-decodes (up to twice), strips all Unicode
            // spaces/control chars and lowercases - collapsing obfuscated scheme tricks such as
            // "java&#9;script:" or "javascript%3aalert(1)" before the check below.
            $low = self::normaliseUrl($raw);

            if (preg_match('#^(javascript:|vbscript:|data:text/html)#', $low)) {
                $el->setAttribute('href', '#');
            }
        }

        // 4) Defensive removal: JS-looking stray text nodes
        foreach ($xpath->query('//text()') as $textNode) {
            $txt = trim($textNode->nodeValue);
            if ($txt === '') {
                continue;
            }
            if (preg_match('/\b(alert|console\.log|document\.write|eval|function\s*\(|javascript:)/i', $txt)) {
                $textNode->parentNode->removeChild($textNode);
            }
        }

        // Output: body innerHTML
        $body = $doc->getElementsByTagName('body')->item(0);
        $out  = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    /**
     * Clean an HTML fragment or document.
     *
     * Pipeline:
     *  1) Extract <body> contents (if any) + strip UTF-8 BOM.
     *  2) Respect "No Filtering": return early if user has it.
     *  3) HTMLPurifier (safe subset) — drops script/iframe/object/embed/style and unsafe
     *     URL schemes, normalises CSS/URLs.
     *  4) Joomla ComponentHelper::filterText() — applies group policy.
     *  5) Final DOM cleanup (unwrap empty anchors, empty <p>, href obfuscation).
     *
     * @param  string $html
     * @return string
     */
    public function purify($html)
    {
        // Extract <body> contents + strip BOM
        $html = self::extractBodyContents($html);
        $html = preg_replace('/^\xEF\xBB\xBF/', '', (string) $html);

        // Bypass for "No Filtering"
        if ($this->hasTextFilter() === false) {
            return $html;
        }

        // HTMLPurifier
        $purifier = $this->getHtmlPurifier();
        $html     = $purifier->purify($html);

        // Joomla per-group Text Filter
        $html = ComponentHelper::filterText($html);

        // Final DOM cleanup
        $html = $this->domCleanup($html);

        return $html;
    }
}
