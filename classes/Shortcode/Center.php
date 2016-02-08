<?php

namespace Shortcode;

use GravShortcode\BaseShortcode;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * Class Center handles a shortcode that centers a selection of text between this shortcode
 *
 * @author Giansimon Diblas
 */
class Center extends BaseShortcode
{
    /**
     * {@inheritdoc}
     */
    public function shortcode()
    {
        return 'center';
    }

    /**
     * {@inheritdoc}
     */
    protected function template()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function renderOutput(ShortcodeInterface $shortcode)
    {
        return '<div style="text-align: center;">'.$shortcode->getContent().'</div>';
    }
}
