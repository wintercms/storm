<?php namespace Winter\Storm\Scaffold\Console;

use Winter\Storm\Scaffold\GeneratorCommand;
use Winter\Storm\Support\Str;

class CreateController extends GeneratorCommand
{
    /**
     * The default command name for lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'create:controller';

    /**
     * The name and signature of this command.
     *
     * @var string
     */
    protected $signature = 'create:controller
        {plugin : The name of the plugin. <info>(eg: Winter.Blog)</info>}
        {controller : The name of the controller to generate. <info>(eg: Posts)</info>}
        {--force : Overwrite existing files with generated files.}
        {--model= : Defines the model name to use. If not provided, the singular name of the controller is used.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new controller.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'controller/_list_toolbar.stub' => 'controllers/{{lower_name}}/_list_toolbar.htm',
        'controller/config_form.stub'   => 'controllers/{{lower_name}}/config_form.yaml',
        'controller/config_list.stub'   => 'controllers/{{lower_name}}/config_list.yaml',
        'controller/create.stub'        => 'controllers/{{lower_name}}/create.htm',
        'controller/index.stub'         => 'controllers/{{lower_name}}/index.htm',
        'controller/preview.stub'       => 'controllers/{{lower_name}}/preview.htm',
        'controller/update.stub'        => 'controllers/{{lower_name}}/update.htm',
        'controller/controller.stub'    => 'controllers/{{studly_name}}.php',
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

        $controller = $this->argument('controller');

        /*
         * Determine the model name to use,
         * either supplied or singular from the controller name.
         */
        $model = $this->option('model');
        if (!$model) {
            $model = Str::singular($controller);
        }

        return [
            'name' => $controller,
            'model' => $model,
            'author' => $author,
            'plugin' => $plugin
        ];
    }
}
