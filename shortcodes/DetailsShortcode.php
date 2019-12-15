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
            $summaryHTML = $summary ? '<summary>'.$summary.'</summary>' : '';

            // Get classes for details
            $class = $sc->getParameter('class', $this->getBbCode($sc));
            $classHTML = (isset($class) and $class !== $summary) ? 'class="'.$class.'"' : '';

            // Get group for details
            $group = $sc->getParameter('group', $this->getBbCode($sc));
            $groupHTML = (isset($group) and $group !== $summary) ? 'data-group="'.$group.'"' : '';

            // Get open status for details
            $open = $sc->getParameter('open', $this->getBbCode($sc));
            $openHTML = (isset($open) and $open !== $summary) ? 'open' : '';

            // Get content
            $content = $sc->getContent();

            // Return the details/summary block
            return '<details '.$classHTML.' '.$groupHTML.' '.$openHTML.'>'.$summaryHTML.$content.'</details>';
        });
    }
}
