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
        // Currently included in Winter
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
        'Up'                    => \Illuminate\Foundation\Console\UpCommand::class,
        'ViewClear'             => \Illuminate\Foundation\Console\ViewClearCommand::class,

        // Currently unsupported in Winter:
        // @TODO: Assess for inclusion
        // 'ClearResets' => ClearResetsCommand::class,
        // 'Db' => DbCommand::class,
        // 'DbPrune' => PruneCommand::class,
        // 'DbWipe' => WipeCommand::class,
        // 'EventCache' => EventCacheCommand::class,
        // 'EventClear' => EventClearCommand::class,
        // 'EventList' => EventListCommand::class,
        // 'OptimizeClear' => OptimizeClearCommand::class,
        // 'QueueClear' => QueueClearCommand::class,
        // 'SchemaDump' => DumpCommand::class,
        // 'ScheduleList' => ScheduleListCommand::class,
        // 'ScheduleClearCache' => ScheduleClearCacheCommand::class,
        // 'ScheduleTest' => ScheduleTestCommand::class,
        // 'ScheduleWork' => ScheduleWorkCommand::class,
        // 'ViewCache' => ViewCacheCommand::class,

        // Explicitly unsupported in Winter:
        // 'Seed' => \Illuminate\Database\Console\Seeds\SeedCommand::class, // Use `winter:up` instead
        // 'StorageLink' => \Illuminate\Foundation\Console\StorageLinkCommand::class, // Use `winter:mirror` instead.
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'Serve'             => \Illuminate\Foundation\Console\ServeCommand::class,
        'VendorPublish'     => \Illuminate\Foundation\Console\VendorPublishCommand::class,

        // Currently unsupported in Winter
        // @TODO: Assess for inclusion
        // 'CacheTable' => CacheTableCommand::class,
        // 'CastMake' => CastMakeCommand::class,
        // 'ChannelMake' => ChannelMakeCommand::class,
        // 'ComponentMake' => ComponentMakeCommand::class,



        // 'ControllerMake' => ControllerMakeCommand::class,
        // 'EventGenerate' => EventGenerateCommand::class,
        // 'EventMake' => EventMakeCommand::class,
        // 'ExceptionMake' => ExceptionMakeCommand::class,
        // 'FactoryMake' => FactoryMakeCommand::class,
        // 'JobMake' => JobMakeCommand::class,
        // 'ListenerMake' => ListenerMakeCommand::class,
        // 'MailMake' => MailMakeCommand::class,
        // 'MiddlewareMake' => MiddlewareMakeCommand::class,

        // 'ModelMake' => ModelMakeCommand::class,

        // MigrationServiceProvider
        // 'Migrate' => MigrateCommand::class,
        // 'MigrateFresh' => FreshCommand::class,
        // 'MigrateInstall' => InstallCommand::class,
        // 'MigrateRefresh' => RefreshCommand::class,
        // 'MigrateReset' => ResetCommand::class,
        // 'MigrateRollback' => RollbackCommand::class,
        // 'MigrateStatus' => StatusCommand::class,
        // 'MigrateMake' => MigrateMakeCommand::class,


        // 'NotificationMake' => NotificationMakeCommand::class,
        // 'NotificationTable' => NotificationTableCommand::class,
        // 'ObserverMake' => ObserverMakeCommand::class,
        // 'PolicyMake' => PolicyMakeCommand::class,
        // 'ProviderMake' => ProviderMakeCommand::class,
        // 'QueueFailedTable' => FailedTableCommand::class,
        // 'QueueTable' => TableCommand::class,
        // 'QueueBatchesTable' => BatchesTableCommand::class,
        // 'RequestMake' => RequestMakeCommand::class,
        // 'ResourceMake' => ResourceMakeCommand::class,
        // 'RuleMake' => RuleMakeCommand::class,
        // 'ScopeMake' => ScopeMakeCommand::class,
        // 'SeederMake' => SeederMakeCommand::class,
        // 'SessionTable' => SessionTableCommand::class,
        // 'StubPublish' => StubPublishCommand::class,
        // 'TestMake' => TestMakeCommand::class,
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
        $this->app->singleton(KeyGenerateCommand::class);
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
