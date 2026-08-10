<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class DetailsShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('details', function(ShortcodeInterface $sc) {
            // Get summary/title
            $summary = $sc->getParameter('summary', $this->getBbCode($sc));
            // Escape the summary the same way the class attribute is escaped below. It is
            // written into the page as markup, so an unescaped `<` closes the <summary>
            // element and the rest is parsed as page HTML. The 6.2.2 attribute sweep
            // (GHSA-q5fw-vpqc-fgph) covered `class` and stopped one line short of this.
            $summaryHTML = $summary ? '<summary>' . self::escAttr($summary) . '</summary>' : '';

            // Get classes for details
            $class = $sc->getParameter('class', $this->getBbCode($sc));
            $classHTML = (isset($class) and $class !== $summary) ? 'class="' . self::escAttr($class) . '"' : '';

            // Get content
            $content = $sc->getContent();

            // Return the details/summary block
            return '<details ' . $classHTML . '>' . $summaryHTML . $content . '</details>';
        });
    }
}
