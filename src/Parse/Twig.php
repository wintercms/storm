<?php namespace Winter\Storm\Parse;

use Illuminate\Support\Facades\App;

/**
 * Twig helper class
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class Twig
{
    /**
     * Parses supplied Twig contents, with supplied variables.
     * @param string $contents Twig contents to parse.
     * @param array $vars Context variables.
     * @return string
     */
    public function parse($contents, $vars = [])
    {
        $twig = App::make('twig.environment');
        $template = $twig->createTemplate($contents);
        return $template->render($vars);
    }
}
