<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SafeEmailShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('safe-email', function(ShortcodeInterface $sc) {
            // Load assets if required
            if ($this->config->get('plugins.shortcode-core.fontawesome.load', false)) {
                $this->shortcode->addAssets('css', $this->config->get('plugins.shortcode-core.fontawesome.url'));
            }

            // Get shortcode content and parameters
            $addr_str = $sc->getContent();
            $icon = $sc->getParameter('icon', false);
            $icon_base = "fa fa-";
            $autolink = $sc->getParameter('autolink', false);
            $subject = $sc->getParameter('subject', false);

            // Add subject, if any, to the link target.
            $link_str = $addr_str;
            if ($subject) {
              $subject = html_entity_decode($subject);
              $link_str .= '?subject=' . rawurlencode($subject);
            }

            // Encode display text and link target
            $email_disp = static::encodeText($addr_str);
            $email_link = static::encodeText($link_str);

            // Handle autolinking
            if ($autolink) {
                $output = '<a href="mailto:' . $email_link . '">' . $email_disp . '</a>';
            } else {
                $output = $email_disp;
            }

            // Handle icon option
            if ($icon) {
                if ($this->config->get('plugins.shortcode-core.fontawesome.v5', false)) {
                    if (preg_match("/^(?P<weight>fa[srlbd]) fa-(?<icon>.+)/", $icon, $icon_parts)) {
                        $icon_base = $icon_parts["weight"] . " fa-";
                        $icon = $icon_parts["icon"];
                    }
                }

                $output = '<i class="'. $icon_base . $icon . '"></i> ' . $output;
            }

            return $output;
        });
    }

    /**
     * encodes text as numeric HTML entities
     * @param string $text the text to encode
     * @return string the encoded text
     */
    private static function encodeText($text)
    {
      $encoded = '';
      $str_len = strlen($text);

      for ($i = 0; $i < $str_len; $i++) {
        $encoded .= '&#' . ord($text[$i]). ';';
      }

      return $encoded;
    }
}
