<?php

JLoader::registerNamespace('Michelf', WF_EDITOR_PRO_PLUGINS . '/textpattern/vendor/php-markdown/Michelf', false, false, 'psr4');

class WfeMarkdownParser extends \Michelf\MarkdownExtra {
    
    public function __construct() {
        // Parent constructor will do the sorting.
		$this->document_gamut += array(
			"doAdmonitionBlocks" => 5
		);

		$this->block_gamut += array(
			"doAdmonitionBlocks" => 5
		);
        
        parent::__construct();
    }
    
    protected function doAdmonitionBlocks($text) {
        // Mapping of GFM alerts to Bootstrap alert classes
        $alertMapping = [
            'note'      => 'alert-info',
            'tip'       => 'alert-success',
            'important' => 'alert-primary',
            'warning'   => 'alert-warning',
            'caution'   => 'alert-danger'
        ];
        
        // Match ::: admonition blocks
        $text = preg_replace_callback(
            '/^:::\s*(\w+)\s*\n([\s\S]+?)\n:::\s*$/m',
            function ($matches) {                
                $type = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
                $content = parent::runBlockGamut($matches[2]); // Process nested Markdown

                // Minimize whitespace between tags
                $content = preg_replace('/>\s+</', '><', $content);
                
                return "<div class=\"alert alert-$type\" role=\"alert\">$content</div>"; // use Bootstrap "alert"
            },
            $text
        );

        // Match GitHub Flavored Markdown (GFM) alerts and convert them
        $text = preg_replace_callback(
            '/^>\s*\[!(\w+)\]\s*\n((?:>.*(?:\n|$))*)/m',
            function ($matches) use ($alertMapping) {
                $type = strtolower($matches[1]); // Convert type to lowercase for class naming
                $bootstrapClass = $alertMapping[$type] ?? 'alert-secondary'; // Default to secondary if not found

                $content = preg_replace('/^>\s?/m', '', trim($matches[2])); // Strip leading "> " from each line

                $content = parent::runBlockGamut($content); // Process nested Markdown
                
                // Minimize whitespace between tags
                $content = preg_replace('/>\s+</', '><', $content);

                return "<div class=\"alert $bootstrapClass\" role=\"alert\">$content</div>";
            },
            $text
        );

        return $text;
    }
}