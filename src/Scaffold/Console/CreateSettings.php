<?php namespace Winter\Storm\Scaffold\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateSettings extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:settings {plugin} {settings=Settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new settings model.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Settings Model';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'settings/model.stub' => 'models/{{studly_name}}.php',
        'settings/fields.stub'   => 'models/{{lower_name}}/fields.yaml'
    ];

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);
        $settings = $this->argument('settings') ?? 'Settings';

        return [
            'name' => $settings,
            'author' => $author,
            'plugin' => $plugin
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin. Eg: Winter.Blog'],
            ['settings', InputArgument::OPTIONAL, 'The name of the settings model. Eg: Settings'],
        ];
    }
}
