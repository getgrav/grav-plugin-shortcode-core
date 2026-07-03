<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ColumnsShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('columns', static function(ShortcodeInterface $sc) {
            $column_count = (int)$sc->getParameter('count', 2);
            $column_width = self::escAttr($sc->getParameter('width', 'auto'));
            $column_gap = self::escAttr($sc->getParameter('gap', 'normal'));
            $column_rule = $sc->getParameter('rule', false);
            $column_rule = $column_rule ? self::escAttr($column_rule) : $column_rule;

            $css_style = 'columns:' . $column_count . ' ' . $column_width . ';-moz-columns:' . $column_count . ' ' . $column_width . ';';
            $css_style .= 'column-gap:' . $column_gap . ';-moz-column-gap:' . $column_gap . ';';

            if ($column_rule) {
                $css_style .= 'column-rule:' . $column_rule . ';-moz-column-rule:' . $column_rule . ';';
            }

            return '<div class="sc-columns" style="' . $css_style . '">' . $sc->getContent() . '</div>';
        });

    }
}