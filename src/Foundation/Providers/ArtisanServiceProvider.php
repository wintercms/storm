<?php namespace Winter\Storm\Foundation\Providers;

use Winter\Storm\Foundation\Console\KeyGenerateCommand;
use Winter\Storm\Foundation\Console\ClearCompiledCommand;
use Illuminate\Foundation\Providers\ArtisanServiceProvider as ArtisanServiceProviderBase;

class ArtisanServiceProvider extends ArtisanServiceProviderBase
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'CacheClear'            => \Illuminate\Cache\Console\ClearCommand::class,
        'CacheForget'           => \Illuminate\Cache\Console\ForgetCommand::class,
        'ClearCompiled'         => \Winter\Storm\Foundation\Console\ClearCompiledCommand::class,
        'ConfigCache'           => \Illuminate\Foundation\Console\ConfigCacheCommand::class,
        'ConfigClear'           => \Illuminate\Foundation\Console\ConfigClearCommand::class,
        'Down'                  => \Illuminate\Foundation\Console\DownCommand::class,
        'Environment'           => \Illuminate\Foundation\Console\EnvironmentCommand::class,
        'KeyGenerate'           => \Winter\Storm\Foundation\Console\KeyGenerateCommand::class,
        'Optimize'              => \Illuminate\Foundation\Console\OptimizeCommand::class,
        'PackageDiscover'       => \Illuminate\Foundation\Console\PackageDiscoverCommand::class,
        'QueueFailed'           => \Illuminate\Queue\Console\ListFailedCommand::class,
        'QueueFlush'            => \Illuminate\Queue\Console\FlushFailedCommand::class,
        'QueueForget'           => \Illuminate\Queue\Console\ForgetFailedCommand::class,
        'QueueListen'           => \Illuminate\Queue\Console\ListenCommand::class,
        'QueueMonitor'          => \Illuminate\Queue\Console\MonitorCommand::class,
        'QueuePruneBatches'     => \Illuminate\Queue\Console\PruneBatchesCommand::class,
        'QueuePruneFailedJobs'  => \Illuminate\Queue\Console\PruneFailedJobsCommand::class,
        'QueueRestart'          => \Illuminate\Queue\Console\RestartCommand::class,
        'QueueRetry'            => \Illuminate\Queue\Console\RetryCommand::class,
        'QueueRetryBatch'       => \Illuminate\Queue\Console\RetryBatchCommand::class,
        'QueueWork'             => \Illuminate\Queue\Console\WorkCommand::class,
        'RouteCache'            => \Illuminate\Foundation\Console\RouteCacheCommand::class,
        'RouteClear'            => \Illuminate\Foundation\Console\RouteClearCommand::class,
        'RouteList'             => \Illuminate\Foundation\Console\RouteListCommand::class,
        'ScheduleFinish'        => \Illuminate\Console\Scheduling\ScheduleFinishCommand::class,
        'ScheduleRun'           => \Illuminate\Console\Scheduling\ScheduleRunCommand::class,
        'Seed'                  => \Illuminate\Database\Console\Seeds\SeedCommand::class,
        'StorageLink'           => \Illuminate\Foundation\Console\StorageLinkCommand::class,
        'Up'                    => \Illuminate\Foundation\Console\UpCommand::class,
        'ViewClear'             => \Illuminate\Foundation\Console\ViewClearCommand::class,
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'Serve'             => \Illuminate\Foundation\Console\ServeCommand::class,
        'VendorPublish'     => \Illuminate\Foundation\Console\VendorPublishCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerKeyGenerateCommand()
    {
        $this->app->singleton(KeyGenerateCommand::class, function ($app) {
            return new KeyGenerateCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerClearCompiledCommand()
    {
        $this->app->singleton(ClearCompiledCommand::class);
    }
}
