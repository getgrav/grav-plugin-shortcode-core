<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class SpanShortcode extends Shortcode
{
	public function init()
	{
		$this->shortcode->getHandlers()->add('span', function(ShortcodeInterface $sc) {
			$id = $sc->getParameter('id');
			$class = $sc->getParameter('class');

			$id_output = $id ? 'id="' . $id . '" ': '';
			$class_output = $class ? 'class="' . $class . '"' : '';
			return '<span ' . $id_output . ' ' . $class_output . '>'.$sc->getContent().'</span>';
		});
	}
}