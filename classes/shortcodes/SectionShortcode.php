<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SectionShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('section', function(ShortcodeInterface $sc) {
            $name = $sc->getParameter('name');
            $page = $sc->getParameter('page');
            $content = $sc->getContent();

            if (empty($content) && isset($page)) {
                if ($target = $this->grav['pages']->find($page)) {
                    if ($shortcodeObject = $target->contentMeta()['shortcodeMeta']['shortcode'][$sc->getName()][$name] ?? false) {
                        return (string) $shortcodeObject;
                    }
                }
            }

            $object = new \Grav\Plugin\ShortcodeCore\ShortcodeObject($name, $sc->getContent());
            $this->shortcode->addObject($sc->getName(), $object);
        });

    }
}