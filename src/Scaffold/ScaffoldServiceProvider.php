<?php namespace Winter\Storm\Scaffold;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class ScaffoldServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        'command.create.theme' => \Winter\Storm\Scaffold\Console\CreateTheme::class,
        'command.create.plugin' => \Winter\Storm\Scaffold\Console\CreatePlugin::class,
        'command.create.model' => \Winter\Storm\Scaffold\Console\CreateModel::class,
        'command.create.settings' => \Winter\Storm\Scaffold\Console\CreateSettings::class,
        'command.create.controller' => \Winter\Storm\Scaffold\Console\CreateController::class,
        'command.create.component' => \Winter\Storm\Scaffold\Console\CreateComponent::class,
        'command.create.formwidget' => \Winter\Storm\Scaffold\Console\CreateFormWidget::class,
        'command.create.reportwidget' => \Winter\Storm\Scaffold\Console\CreateReportWidget::class,
        'command.create.command' => \Winter\Storm\Scaffold\Console\CreateCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    'command.create.theme',
                    'command.create.plugin',
                    'command.create.model',
                    'command.create.settings',
                    'command.create.controller',
                    'command.create.component',
                    'command.create.formwidget',
                    'command.create.reportwidget',
                    'command.create.command',
                ]
            );
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.create.theme',
            'command.create.plugin',
            'command.create.model',
            'command.create.settings',
            'command.create.controller',
            'command.create.component',
            'command.create.formwidget',
            'command.create.reportwidget',
            'command.create.command',
        ];
    }
}
