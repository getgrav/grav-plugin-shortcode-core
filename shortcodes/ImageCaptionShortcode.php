<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ImageCaptionShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('imgcaption', function(ShortcodeInterface $sc) {

            // Get shortcode content and parameters
            $caption = $sc->getContent();
            $src = $sc->getParameter('src', false);
            $alt = $sc->getParameter('alt', false);
            $title = $sc->getParameter('title', false);
            $width = $sc->getParameter('width', false);
            $height = $sc->getParameter('height', false);

            // Build the output string
            $output = '<figure><img ';
            if ($src)
                $output .= 'src="' . $src . '" ';
            if ($alt)
                $output .= 'alt="' . $alt . '" ';
            if ($title)
                $output .= 'title="' . $title . '" ';
            if ($width)
                $output .= 'width="' . $width . '" ';
            if ($height)
                $output .= 'height="' . $height . '" ';
            $output .= '/><figcaption>' . $caption . '</figcaption></figure>';

            return $output;
        });
    }
}