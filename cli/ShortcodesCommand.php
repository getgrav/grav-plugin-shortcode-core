<?php
namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Common\Twig\Twig;
use Grav\Console\ConsoleCommand;
use Grav\Plugin\CloudflarePlugin;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CloudflareIPsCommand
 *
 * @package Grav\Plugin\Console
 */
class ShortcodesCommand extends ConsoleCommand
{

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('display')
            ->setDescription('Display a list the available shortcodes that are registered');
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        $io = new SymfonyStyle($this->input, $this->output);
        $this->initializePlugins();
        $this->initializeThemes();

        $shortcodes = Grav::instance()['shortcode'];

        $io->title('Available Shortcodes');
        $io->section('Regular Handlers:');
        foreach ($shortcodes->getHandlers()->getNames() as $name) {
            $io->writeln($name);
        }
        $io->section('Raw Handlers:');
        foreach ($shortcodes->getRawHandlers()->getNames() as $name) {
            $io->writeln($name);
        }

        $io->newLine();

    }
}
