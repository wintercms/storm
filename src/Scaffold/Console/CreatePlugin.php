<?php namespace Winter\Storm\Scaffold\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreatePlugin extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'create:plugin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new plugin.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Plugin';

    /** @inheritDoc */
    public function handle()
    {
        if ($this->hasOption('with-translations')) {
            $currentLocale = config('app.locale');

            $this->stubs = [
                'plugin/lang.stub' => sprintf('lang/%s/lang.php', $currentLocale),
                'plugin/plugin-with-translations.stub' => 'Plugin.php',
                'plugin/version.stub' => 'updates/version.yaml'
            ];
        } else {
            $this->stubs = [
                'plugin/plugin.stub'  => 'Plugin.php',
                'plugin/version.stub' => 'updates/version.yaml',
            ];
        }

        parent::handle();
    }

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {
        /*
         * Extract the author and name from the plugin code
         */
        $pluginCode = $this->argument('plugin');
        $parts = explode('.', $pluginCode);

        if (count($parts) != 2) {
            $this->error('Invalid plugin name, either too many dots or not enough.');
            $this->error('Example name: AuthorName.PluginName');
            return;
        }


        $pluginName = array_pop($parts);
        $authorName = array_pop($parts);

        return [
            'name'   => $pluginName,
            'author' => $authorName,
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
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: Winter.Blog'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
            ['with-translations', null, InputOption::VALUE_NONE, 'Create lang folder and default locale file.'],
        ];
    }
}
