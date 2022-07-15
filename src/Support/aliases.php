<?php

/**
 * Alias Winter\Storm\Auth\AuthException
 * @since v1.1.7
 */
class_alias(\Winter\Storm\Auth\AuthenticationException::class, \Winter\Storm\Auth\AuthException::class);

/**
 * Alias October\Rain\Argon
 */
class_alias(\Winter\Storm\Argon\Argon::class, \October\Rain\Argon\Argon::class);
class_alias(\Winter\Storm\Argon\ArgonServiceProvider::class, \October\Rain\Argon\ArgonServiceProvider::class);

/**
 * Alias October\Rain\Assetic
 */
class_alias(\Assetic\Asset\AssetCache::class, \October\Rain\Assetic\Asset\AssetCache::class);
class_alias(\Assetic\Asset\AssetCollection::class, \October\Rain\Assetic\Asset\AssetCollection::class);
class_alias(\Assetic\Contracts\Asset\AssetCollectionInterface::class, \October\Rain\Assetic\Asset\AssetCollectionInterface::class);
class_alias(\Assetic\Contracts\Asset\AssetInterface::class, \October\Rain\Assetic\Asset\AssetInterface::class);
class_alias(\Assetic\Asset\AssetReference::class, \October\Rain\Assetic\Asset\AssetReference::class);
class_alias(\Assetic\Asset\BaseAsset::class, \October\Rain\Assetic\Asset\BaseAsset::class);
class_alias(\Assetic\Asset\FileAsset::class, \October\Rain\Assetic\Asset\FileAsset::class);
class_alias(\Assetic\Asset\GlobAsset::class, \October\Rain\Assetic\Asset\GlobAsset::class);
class_alias(\Assetic\Asset\HttpAsset::class, \October\Rain\Assetic\Asset\HttpAsset::class);
class_alias(\Assetic\Asset\Iterator\AssetCollectionFilterIterator::class, \October\Rain\Assetic\Asset\Iterator\AssetCollectionFilterIterator::class);
class_alias(\Assetic\Asset\Iterator\AssetCollectionIterator::class, \October\Rain\Assetic\Asset\Iterator\AssetCollectionIterator::class);
class_alias(\Assetic\Asset\StringAsset::class, \October\Rain\Assetic\Asset\StringAsset::class);
class_alias(\Assetic\AssetManager::class, \October\Rain\Assetic\AssetManager::class);
class_alias(\Assetic\AssetWriter::class, \October\Rain\Assetic\AssetWriter::class);
class_alias(\Assetic\Cache\ArrayCache::class, \October\Rain\Assetic\Cache\ArrayCache::class);
class_alias(\Assetic\Contracts\Cache\CacheInterface::class, \October\Rain\Assetic\Cache\CacheInterface::class);
class_alias(\Assetic\Cache\ConfigCache::class, \October\Rain\Assetic\Cache\ConfigCache::class);
class_alias(\Assetic\Cache\ExpiringCache::class, \October\Rain\Assetic\Cache\ExpiringCache::class);
class_alias(\Winter\Storm\Parse\Assetic\Cache\FilesystemCache::class, \October\Rain\Assetic\Cache\FilesystemCache::class);
class_alias(\Assetic\Contracts\Exception\Exception::class, \October\Rain\Assetic\Exception\Exception::class);
class_alias(\Assetic\Exception\FilterException::class, \October\Rain\Assetic\Exception\FilterException::class);
class_alias(\Assetic\Factory\AssetFactory::class, \October\Rain\Assetic\Factory\AssetFactory::class);
class_alias(\Assetic\Factory\LazyAssetManager::class, \October\Rain\Assetic\Factory\LazyAssetManager::class);
class_alias(\Assetic\Factory\Loader\BasePhpFormulaLoader::class, \October\Rain\Assetic\Factory\Loader\BasePhpFormulaLoader::class);
class_alias(\Assetic\Factory\Loader\CachedFormulaLoader::class, \October\Rain\Assetic\Factory\Loader\CachedFormulaLoader::class);
class_alias(\Assetic\Contracts\Factory\Loader\FormulaLoaderInterface::class, \October\Rain\Assetic\Factory\Loader\FormulaLoaderInterface::class);
class_alias(\Assetic\Factory\Loader\FunctionCallsFormulaLoader::class, \October\Rain\Assetic\Factory\Loader\FunctionCallsFormulaLoader::class);
class_alias(\Assetic\Factory\Resource\CoalescingDirectoryResource::class, \October\Rain\Assetic\Factory\Resource\CoalescingDirectoryResource::class);
class_alias(\Assetic\Factory\Resource\DirectoryResource::class, \October\Rain\Assetic\Factory\Resource\DirectoryResource::class);
class_alias(\Assetic\Factory\Resource\DirectoryResourceFilterIterator::class, \October\Rain\Assetic\Factory\Resource\DirectoryResourceFilterIterator::class);
class_alias(\Assetic\Factory\Resource\DirectoryResourceIterator::class, \October\Rain\Assetic\Factory\Resource\DirectoryResourceIterator::class);
class_alias(\Assetic\Factory\Resource\FileResource::class, \October\Rain\Assetic\Factory\Resource\FileResource::class);
class_alias(\Assetic\Contracts\Factory\Resource\IteratorResourceInterface::class, \October\Rain\Assetic\Factory\Resource\IteratorResourceInterface::class);
class_alias(\Assetic\Contracts\Factory\Resource\ResourceInterface::class, \October\Rain\Assetic\Factory\Resource\ResourceInterface::class);
class_alias(\Assetic\Factory\Worker\CacheBustingWorker::class, \October\Rain\Assetic\Factory\Worker\CacheBustingWorker::class);
class_alias(\Assetic\Factory\Worker\EnsureFilterWorker::class, \October\Rain\Assetic\Factory\Worker\EnsureFilterWorker::class);
class_alias(\Assetic\Contracts\Factory\Worker\WorkerInterface::class, \October\Rain\Assetic\Factory\Worker\WorkerInterface::class);
class_alias(\Assetic\Filter\BaseCssFilter::class, \October\Rain\Assetic\Filter\BaseCssFilter::class);
class_alias(\Assetic\Filter\CallablesFilter::class, \October\Rain\Assetic\Filter\CallablesFilter::class);
class_alias(\Assetic\Filter\CssCacheBustingFilter::class, \October\Rain\Assetic\Filter\CssCacheBustingFilter::class);
class_alias(\Assetic\Filter\CssImportFilter::class, \October\Rain\Assetic\Filter\CssImportFilter::class);
class_alias(\Assetic\Filter\CssRewriteFilter::class, \October\Rain\Assetic\Filter\CssRewriteFilter::class);
class_alias(\Assetic\Contracts\Filter\DependencyExtractorInterface::class, \October\Rain\Assetic\Filter\DependencyExtractorInterface::class);
class_alias(\Assetic\Filter\FilterCollection::class, \October\Rain\Assetic\Filter\FilterCollection::class);
class_alias(\Assetic\Contracts\Filter\FilterInterface::class, \October\Rain\Assetic\Filter\FilterInterface::class);
class_alias(\Assetic\Contracts\Filter\HashableInterface::class, \October\Rain\Assetic\Filter\HashableInterface::class);
class_alias(\Winter\Storm\Parse\Assetic\Filter\JavascriptImporter::class, \October\Rain\Assetic\Filter\JavascriptImporter::class);
class_alias(\Winter\Storm\Parse\Assetic\Filter\LessCompiler::class, \October\Rain\Assetic\Filter\LessCompiler::class);
class_alias(\Assetic\Filter\LessphpFilter::class, \October\Rain\Assetic\Filter\LessphpFilter::class);
class_alias(\Assetic\Filter\PackerFilter::class, \October\Rain\Assetic\Filter\PackerFilter::class);
class_alias(\Winter\Storm\Parse\Assetic\Filter\ScssCompiler::class, \October\Rain\Assetic\Filter\ScssCompiler::class);
class_alias(\Assetic\Filter\ScssphpFilter::class, \October\Rain\Assetic\Filter\ScssphpFilter::class);
class_alias(\Assetic\Filter\StylesheetMinifyFilter::class, \October\Rain\Assetic\Filter\StylesheetMinify::class);
class_alias(\Assetic\FilterManager::class, \October\Rain\Assetic\FilterManager::class);
class_alias(\Assetic\Util\CssUtils::class, \October\Rain\Assetic\Util\CssUtils::class);
class_alias(\Assetic\Util\FilesystemUtils::class, \October\Rain\Assetic\Util\FilesystemUtils::class);
class_alias(\Assetic\Util\LessUtils::class, \October\Rain\Assetic\Util\LessUtils::class);
class_alias(\Assetic\Util\SassUtils::class, \October\Rain\Assetic\Util\SassUtils::class);
class_alias(\Assetic\Util\TraversableString::class, \October\Rain\Assetic\Util\TraversableString::class);
class_alias(\Assetic\Util\VarUtils::class, \October\Rain\Assetic\Util\VarUtils::class);

/**
 * Alias October\Rain\Auth
 */
class_alias(\Winter\Storm\Auth\AuthException::class, \October\Rain\Auth\AuthException::class);
class_alias(\Winter\Storm\Auth\Manager::class, \October\Rain\Auth\Manager::class);
class_alias(\Winter\Storm\Auth\Models\Group::class, \October\Rain\Auth\Models\Group::class);
class_alias(\Winter\Storm\Auth\Models\Preferences::class, \October\Rain\Auth\Models\Preferences::class);
class_alias(\Winter\Storm\Auth\Models\Role::class, \October\Rain\Auth\Models\Role::class);
class_alias(\Winter\Storm\Auth\Models\Throttle::class, \October\Rain\Auth\Models\Throttle::class);
class_alias(\Winter\Storm\Auth\Models\User::class, \October\Rain\Auth\Models\User::class);

/**
 * Alias October\Rain\Config
 */
class_alias(\Winter\Storm\Config\ConfigServiceProvider::class, \October\Rain\Config\ConfigServiceProvider::class);
class_alias(\Winter\Storm\Config\ConfigWriter::class, \October\Rain\Config\ConfigWriter::class);
class_alias(\Winter\Storm\Config\FileLoader::class, \October\Rain\Config\FileLoader::class);
class_alias(\Winter\Storm\Config\LoaderInterface::class, \October\Rain\Config\LoaderInterface::class);
class_alias(\Winter\Storm\Config\Repository::class, \October\Rain\Config\Repository::class);

/**
 * Alias October\Rain\Cookie
 */
class_alias(\Winter\Storm\Cookie\Middleware\EncryptCookies::class, \October\Rain\Cookie\Middleware\EncryptCookies::class);

/**
 * Alias October\Rain\Database
 */
class_alias(\Winter\Storm\Database\Attach\BrokenImage::class, \October\Rain\Database\Attach\BrokenImage::class);
class_alias(\Winter\Storm\Database\Attach\File::class, \October\Rain\Database\Attach\File::class);
class_alias(\Winter\Storm\Database\Attach\FileException::class, \October\Rain\Database\Attach\FileException::class);
class_alias(\Winter\Storm\Database\Attach\Resizer::class, \October\Rain\Database\Attach\Resizer::class);
class_alias(\Winter\Storm\Database\Behaviors\Purgeable::class, \October\Rain\Database\Behaviors\Purgeable::class);
class_alias(\Winter\Storm\Database\Behaviors\Sortable::class, \October\Rain\Database\Behaviors\Sortable::class);
class_alias(\Winter\Storm\Database\Builder::class, \October\Rain\Database\Builder::class);
class_alias(\Winter\Storm\Database\Capsule\Manager::class, \October\Rain\Database\Capsule\Manager::class);
class_alias(\Winter\Storm\Database\Collection::class, \October\Rain\Database\Collection::class);
class_alias(\Winter\Storm\Database\Concerns\GuardsAttributes::class, \October\Rain\Database\Concerns\GuardsAttributes::class);
class_alias(\Winter\Storm\Database\Concerns\HasRelationships::class, \October\Rain\Database\Concerns\HasRelationships::class);
class_alias(\Winter\Storm\Database\Connections\Connection::class, \October\Rain\Database\Connections\Connection::class);
class_alias(\Winter\Storm\Database\Connections\MySqlConnection::class, \October\Rain\Database\Connections\MySqlConnection::class);
class_alias(\Winter\Storm\Database\Connections\PostgresConnection::class, \October\Rain\Database\Connections\PostgresConnection::class);
class_alias(\Winter\Storm\Database\Connections\SQLiteConnection::class, \October\Rain\Database\Connections\SQLiteConnection::class);
class_alias(\Winter\Storm\Database\Connections\SqlServerConnection::class, \October\Rain\Database\Connections\SqlServerConnection::class);
class_alias(\Winter\Storm\Database\Connectors\ConnectionFactory::class, \October\Rain\Database\Connectors\ConnectionFactory::class);
class_alias(\Winter\Storm\Database\DatabaseServiceProvider::class, \October\Rain\Database\DatabaseServiceProvider::class);
class_alias(\Winter\Storm\Database\DataFeed::class, \October\Rain\Database\DataFeed::class);
class_alias(\Winter\Storm\Database\Dongle::class, \October\Rain\Database\Dongle::class);
class_alias(\Winter\Storm\Database\MemoryCache::class, \October\Rain\Database\MemoryCache::class);
class_alias(\Winter\Storm\Database\Model::class, \October\Rain\Database\Model::class);
class_alias(\Winter\Storm\Database\ModelBehavior::class, \October\Rain\Database\ModelBehavior::class);
class_alias(\Winter\Storm\Database\ModelException::class, \October\Rain\Database\ModelException::class);
class_alias(\Winter\Storm\Database\Models\DeferredBinding::class, \October\Rain\Database\Models\DeferredBinding::class);
class_alias(\Winter\Storm\Database\Models\Revision::class, \October\Rain\Database\Models\Revision::class);
class_alias(\Winter\Storm\Database\NestedTreeScope::class, \October\Rain\Database\NestedTreeScope::class);
class_alias(\Winter\Storm\Database\Pivot::class, \October\Rain\Database\Pivot::class);
class_alias(\Winter\Storm\Database\Query\Grammars\Concerns\SelectConcatenations::class, \October\Rain\Database\Query\Grammars\Concerns\SelectConcatenations::class);
class_alias(\Winter\Storm\Database\Query\Grammars\MySqlGrammar::class, \October\Rain\Database\Query\Grammars\MySqlGrammar::class);
class_alias(\Winter\Storm\Database\Query\Grammars\PostgresGrammar::class, \October\Rain\Database\Query\Grammars\PostgresGrammar::class);
class_alias(\Winter\Storm\Database\Query\Grammars\SQLiteGrammar::class, \October\Rain\Database\Query\Grammars\SQLiteGrammar::class);
class_alias(\Winter\Storm\Database\Query\Grammars\SqlServerGrammar::class, \October\Rain\Database\Query\Grammars\SqlServerGrammar::class);
class_alias(\Winter\Storm\Database\QueryBuilder::class, \October\Rain\Database\QueryBuilder::class);
class_alias(\Winter\Storm\Database\Relations\AttachMany::class, \October\Rain\Database\Relations\AttachMany::class);
class_alias(\Winter\Storm\Database\Relations\AttachOne::class, \October\Rain\Database\Relations\AttachOne::class);
class_alias(\Winter\Storm\Database\Relations\Concerns\AttachOneOrMany::class, \October\Rain\Database\Relations\AttachOneOrMany::class);
class_alias(\Winter\Storm\Database\Relations\BelongsTo::class, \October\Rain\Database\Relations\BelongsTo::class);
class_alias(\Winter\Storm\Database\Relations\BelongsToMany::class, \October\Rain\Database\Relations\BelongsToMany::class);
class_alias(\Winter\Storm\Database\Relations\Concerns\DeferOneOrMany::class, \October\Rain\Database\Relations\DeferOneOrMany::class);
class_alias(\Winter\Storm\Database\Relations\Concerns\DefinedConstraints::class, \October\Rain\Database\Relations\DefinedConstraints::class);
class_alias(\Winter\Storm\Database\Relations\HasMany::class, \October\Rain\Database\Relations\HasMany::class);
class_alias(\Winter\Storm\Database\Relations\HasManyThrough::class, \October\Rain\Database\Relations\HasManyThrough::class);
class_alias(\Winter\Storm\Database\Relations\HasOne::class, \October\Rain\Database\Relations\HasOne::class);
class_alias(\Winter\Storm\Database\Relations\Concerns\HasOneOrMany::class, \October\Rain\Database\Relations\HasOneOrMany::class);
class_alias(\Winter\Storm\Database\Relations\HasOneThrough::class, \October\Rain\Database\Relations\HasOneThrough::class);
class_alias(\Winter\Storm\Database\Relations\MorphMany::class, \October\Rain\Database\Relations\MorphMany::class);
class_alias(\Winter\Storm\Database\Relations\MorphOne::class, \October\Rain\Database\Relations\MorphOne::class);
class_alias(\Winter\Storm\Database\Relations\Concerns\MorphOneOrMany::class, \October\Rain\Database\Relations\MorphOneOrMany::class);
class_alias(\Winter\Storm\Database\Relations\MorphTo::class, \October\Rain\Database\Relations\MorphTo::class);
class_alias(\Winter\Storm\Database\Relations\MorphToMany::class, \October\Rain\Database\Relations\MorphToMany::class);
class_alias(\Winter\Storm\Database\Relations\Relation::class, \October\Rain\Database\Relations\Relation::class);
class_alias(\Winter\Storm\Database\Schema\Blueprint::class, \October\Rain\Database\Schema\Blueprint::class);
class_alias(\Winter\Storm\Database\SortableScope::class, \October\Rain\Database\SortableScope::class);
class_alias(\Winter\Storm\Database\Traits\DeferredBinding::class, \October\Rain\Database\Traits\DeferredBinding::class);
class_alias(\Winter\Storm\Database\Traits\Encryptable::class, \October\Rain\Database\Traits\Encryptable::class);
class_alias(\Winter\Storm\Database\Traits\Hashable::class, \October\Rain\Database\Traits\Hashable::class);
class_alias(\Winter\Storm\Database\Traits\NestedTree::class, \October\Rain\Database\Traits\NestedTree::class);
class_alias(\Winter\Storm\Database\Traits\Nullable::class, \October\Rain\Database\Traits\Nullable::class);
class_alias(\Winter\Storm\Database\Traits\Purgeable::class, \October\Rain\Database\Traits\Purgeable::class);
class_alias(\Winter\Storm\Database\Traits\Revisionable::class, \October\Rain\Database\Traits\Revisionable::class);
class_alias(\Winter\Storm\Database\Traits\SimpleTree::class, \October\Rain\Database\Traits\SimpleTree::class);
class_alias(\Winter\Storm\Database\Traits\Sluggable::class, \October\Rain\Database\Traits\Sluggable::class);
class_alias(\Winter\Storm\Database\Traits\SoftDelete::class, \October\Rain\Database\Traits\SoftDelete::class);
class_alias(\Winter\Storm\Database\Traits\SoftDeleting::class, \October\Rain\Database\Traits\SoftDeleting::class);
class_alias(\Winter\Storm\Database\Traits\Sortable::class, \October\Rain\Database\Traits\Sortable::class);
class_alias(\Winter\Storm\Database\Traits\Validation::class, \October\Rain\Database\Traits\Validation::class);
class_alias(\Winter\Storm\Database\TreeCollection::class, \October\Rain\Database\TreeCollection::class);
class_alias(\Winter\Storm\Database\Updater::class, \October\Rain\Database\Updater::class);
class_alias(\Winter\Storm\Database\Updates\Migration::class, \October\Rain\Database\Updates\Migration::class);
class_alias(\Winter\Storm\Database\Updates\Seeder::class, \October\Rain\Database\Updates\Seeder::class);

/**
 * Alias October\Rain\Events
 */
class_alias(\Winter\Storm\Events\CallQueuedHandler::class, \October\Rain\Events\CallQueuedHandler::class);
class_alias(\Winter\Storm\Events\Dispatcher::class, \October\Rain\Events\Dispatcher::class);
class_alias(\Winter\Storm\Events\EventServiceProvider::class, \October\Rain\Events\EventServiceProvider::class);

/**
 * Alias October\Rain\Exception
 */
class_alias(\Winter\Storm\Exception\AjaxException::class, \October\Rain\Exception\AjaxException::class);
class_alias(\Winter\Storm\Exception\ApplicationException::class, \October\Rain\Exception\ApplicationException::class);
class_alias(\Winter\Storm\Exception\ErrorHandler::class, \October\Rain\Exception\ErrorHandler::class);
class_alias(\Winter\Storm\Exception\ExceptionBase::class, \October\Rain\Exception\ExceptionBase::class);
class_alias(\Winter\Storm\Exception\SystemException::class, \October\Rain\Exception\SystemException::class);
class_alias(\Winter\Storm\Exception\ValidationException::class, \October\Rain\Exception\ValidationException::class);

/**
 * Alias October\Rain\Extension
 */
class_alias(\Winter\Storm\Extension\Extendable::class, \October\Rain\Extension\Extendable::class);
class_alias(\Winter\Storm\Extension\ExtendableTrait::class, \October\Rain\Extension\ExtendableTrait::class);
class_alias(\Winter\Storm\Extension\ExtensionBase::class, \October\Rain\Extension\ExtensionBase::class);
class_alias(\Winter\Storm\Extension\ExtensionTrait::class, \October\Rain\Extension\ExtensionTrait::class);

/**
 * Alias October\Rain\Filesystem
 */
class_alias(\Winter\Storm\Filesystem\Definitions::class, \October\Rain\Filesystem\Definitions::class);
class_alias(\Winter\Storm\Filesystem\Filesystem::class, \October\Rain\Filesystem\Filesystem::class);
class_alias(\Illuminate\Filesystem\FilesystemAdapter::class, \October\Rain\Filesystem\FilesystemAdapter::class);
class_alias(\Winter\Storm\Filesystem\FilesystemManager::class, \October\Rain\Filesystem\FilesystemManager::class);
class_alias(\Winter\Storm\Filesystem\FilesystemServiceProvider::class, \October\Rain\Filesystem\FilesystemServiceProvider::class);
class_alias(\Winter\Storm\Filesystem\PathResolver::class, \October\Rain\Filesystem\PathResolver::class);
class_alias(\Winter\Storm\Filesystem\Zip::class, \October\Rain\Filesystem\Zip::class);

/**
 * Alias October\Rain\Flash
 */
class_alias(\Winter\Storm\Flash\FlashBag::class, \October\Rain\Flash\FlashBag::class);
class_alias(\Winter\Storm\Flash\FlashServiceProvider::class, \October\Rain\Flash\FlashServiceProvider::class);

/**
 * Alias October\Rain\Foundation
 */
class_alias(\Winter\Storm\Foundation\Application::class, \October\Rain\Foundation\Application::class);
class_alias(\Winter\Storm\Foundation\Bootstrap\LoadConfiguration::class, \October\Rain\Foundation\Bootstrap\LoadConfiguration::class);
class_alias(\Winter\Storm\Foundation\Bootstrap\LoadEnvironmentVariables::class, \October\Rain\Foundation\Bootstrap\LoadEnvironmentVariables::class);
class_alias(\Winter\Storm\Foundation\Bootstrap\LoadTranslation::class, \October\Rain\Foundation\Bootstrap\LoadTranslation::class);
class_alias(\Winter\Storm\Foundation\Bootstrap\RegisterClassLoader::class, \October\Rain\Foundation\Bootstrap\RegisterClassLoader::class);
class_alias(\Winter\Storm\Foundation\Bootstrap\RegisterWinter::class, \October\Rain\Foundation\Bootstrap\RegisterWinter::class);
class_alias(\Winter\Storm\Foundation\Console\ClearCompiledCommand::class, \October\Rain\Foundation\Console\ClearCompiledCommand::class);
class_alias(\Winter\Storm\Foundation\Console\Kernel::class, \October\Rain\Foundation\Console\Kernel::class);
class_alias(\Winter\Storm\Foundation\Console\KeyGenerateCommand::class, \October\Rain\Foundation\Console\KeyGenerateCommand::class);
class_alias(\Winter\Storm\Foundation\Exception\Handler::class, \October\Rain\Foundation\Exception\Handler::class);
class_alias(\Winter\Storm\Foundation\Http\Kernel::class, \October\Rain\Foundation\Http\Kernel::class);
class_alias(\Winter\Storm\Foundation\Http\Middleware\CheckForMaintenanceMode::class, \October\Rain\Foundation\Http\Middleware\CheckForMaintenanceMode::class);
class_alias(\Winter\Storm\Foundation\Http\Middleware\CheckForTrustedHost::class, \October\Rain\Foundation\Http\Middleware\CheckForTrustedHost::class);
class_alias(\Winter\Storm\Foundation\Maker::class, \October\Rain\Foundation\Maker::class);
class_alias(\Winter\Storm\Foundation\Providers\ArtisanServiceProvider::class, \October\Rain\Foundation\Providers\ArtisanServiceProvider::class);
class_alias(\Winter\Storm\Foundation\Providers\ConsoleSupportServiceProvider::class, \October\Rain\Foundation\Providers\ConsoleSupportServiceProvider::class);
class_alias(\Winter\Storm\Foundation\Providers\ExecutionContextProvider::class, \October\Rain\Foundation\Providers\ExecutionContextProvider::class);
class_alias(\Winter\Storm\Foundation\Providers\LogServiceProvider::class, \October\Rain\Foundation\Providers\LogServiceProvider::class);
class_alias(\Winter\Storm\Foundation\Providers\MakerServiceProvider::class, \October\Rain\Foundation\Providers\MakerServiceProvider::class);

/**
 * Alias October\Rain\Halcyon
 */
class_alias(\Winter\Storm\Halcyon\Builder::class, \October\Rain\Halcyon\Builder::class);
class_alias(\Winter\Storm\Halcyon\Collection::class, \October\Rain\Halcyon\Collection::class);
class_alias(\Winter\Storm\Halcyon\Datasource\Datasource::class, \October\Rain\Halcyon\Datasource\Datasource::class);
class_alias(\Winter\Storm\Halcyon\Datasource\DatasourceInterface::class, \October\Rain\Halcyon\Datasource\DatasourceInterface::class);
class_alias(\Winter\Storm\Halcyon\Datasource\DbDatasource::class, \October\Rain\Halcyon\Datasource\DbDatasource::class);
class_alias(\Winter\Storm\Halcyon\Datasource\FileDatasource::class, \October\Rain\Halcyon\Datasource\FileDatasource::class);
class_alias(\Winter\Storm\Halcyon\Datasource\Resolver::class, \October\Rain\Halcyon\Datasource\Resolver::class);
class_alias(\Winter\Storm\Halcyon\Datasource\ResolverInterface::class, \October\Rain\Halcyon\Datasource\ResolverInterface::class);
class_alias(\Winter\Storm\Halcyon\Exception\CreateDirectoryException::class, \October\Rain\Halcyon\Exception\CreateDirectoryException::class);
class_alias(\Winter\Storm\Halcyon\Exception\CreateFileException::class, \October\Rain\Halcyon\Exception\CreateFileException::class);
class_alias(\Winter\Storm\Halcyon\Exception\DeleteFileException::class, \October\Rain\Halcyon\Exception\DeleteFileException::class);
class_alias(\Winter\Storm\Halcyon\Exception\FileExistsException::class, \October\Rain\Halcyon\Exception\FileExistsException::class);
class_alias(\Winter\Storm\Halcyon\Exception\InvalidExtensionException::class, \October\Rain\Halcyon\Exception\InvalidExtensionException::class);
class_alias(\Winter\Storm\Halcyon\Exception\InvalidFileNameException::class, \October\Rain\Halcyon\Exception\InvalidFileNameException::class);
class_alias(\Winter\Storm\Halcyon\Exception\MissingFileNameException::class, \October\Rain\Halcyon\Exception\MissingFileNameException::class);
class_alias(\Winter\Storm\Halcyon\Exception\ModelException::class, \October\Rain\Halcyon\Exception\ModelException::class);
class_alias(\Winter\Storm\Halcyon\HalcyonServiceProvider::class, \October\Rain\Halcyon\HalcyonServiceProvider::class);
class_alias(\Winter\Storm\Halcyon\MemoryCacheManager::class, \October\Rain\Halcyon\MemoryCacheManager::class);
class_alias(\Winter\Storm\Halcyon\MemoryRepository::class, \October\Rain\Halcyon\MemoryRepository::class);
class_alias(\Winter\Storm\Halcyon\Model::class, \October\Rain\Halcyon\Model::class);
class_alias(\Winter\Storm\Halcyon\Processors\Processor::class, \October\Rain\Halcyon\Processors\Processor::class);
class_alias(\Winter\Storm\Halcyon\Processors\SectionParser::class, \October\Rain\Halcyon\Processors\SectionParser::class);
class_alias(\Winter\Storm\Halcyon\Traits\Validation::class, \October\Rain\Halcyon\Traits\Validation::class);

/**
 * Alias October\Rain\Html
 */
class_alias(\Winter\Storm\Html\BlockBuilder::class, \October\Rain\Html\BlockBuilder::class);
class_alias(\Winter\Storm\Html\FormBuilder::class, \October\Rain\Html\FormBuilder::class);
class_alias(\Winter\Storm\Html\Helper::class, \October\Rain\Html\Helper::class);
class_alias(\Winter\Storm\Html\HtmlBuilder::class, \October\Rain\Html\HtmlBuilder::class);
class_alias(\Winter\Storm\Html\HtmlServiceProvider::class, \October\Rain\Html\HtmlServiceProvider::class);
class_alias(\Winter\Storm\Html\UrlServiceProvider::class, \October\Rain\Html\UrlServiceProvider::class);

/**
 * Alias October\Rain\Http
 */
class_alias(\Winter\Storm\Http\Middleware\TrustHosts::class, \October\Rain\Http\Middleware\TrustHosts::class);

/**
 * Alias October\Rain\Mail
 */
class_alias(\Winter\Storm\Mail\Mailable::class, \October\Rain\Mail\Mailable::class);
class_alias(\Winter\Storm\Mail\Mailer::class, \October\Rain\Mail\Mailer::class);
class_alias(\Winter\Storm\Mail\MailParser::class, \October\Rain\Mail\MailParser::class);
class_alias(\Winter\Storm\Mail\MailServiceProvider::class, \October\Rain\Mail\MailServiceProvider::class);

/**
 * Alias October\Rain\Network
 */
class_alias(\Winter\Storm\Network\Http::class, \October\Rain\Network\Http::class);
class_alias(\Winter\Storm\Network\NetworkServiceProvider::class, \October\Rain\Network\NetworkServiceProvider::class);

/**
 * Alias October\Rain\Parse
 */
class_alias(\Winter\Storm\Parse\Bracket::class, \October\Rain\Parse\Bracket::class);
class_alias(\Winter\Storm\Parse\Ini::class, \October\Rain\Parse\Ini::class);
class_alias(\Winter\Storm\Parse\Markdown::class, \October\Rain\Parse\Markdown::class);
class_alias(\Winter\Storm\Parse\MarkdownData::class, \October\Rain\Parse\MarkdownData::class);
class_alias(\Winter\Storm\Parse\Parsedown\Parsedown::class, \October\Rain\Parse\Parsedown\Parsedown::class);
class_alias(\Winter\Storm\Parse\ParseServiceProvider::class, \October\Rain\Parse\ParseServiceProvider::class);
class_alias(\Winter\Storm\Parse\Syntax\FieldParser::class, \October\Rain\Parse\Syntax\FieldParser::class);
class_alias(\Winter\Storm\Parse\Syntax\Parser::class, \October\Rain\Parse\Syntax\Parser::class);
class_alias(\Winter\Storm\Parse\Syntax\SyntaxModelTrait::class, \October\Rain\Parse\Syntax\SyntaxModelTrait::class);
class_alias(\Winter\Storm\Parse\Twig::class, \October\Rain\Parse\Twig::class);
class_alias(\Winter\Storm\Parse\Yaml::class, \October\Rain\Parse\Yaml::class);

/**
 * Alias October\Rain\Redis
 */
class_alias(\Winter\Storm\Redis\RedisServiceProvider::class, \October\Rain\Redis\RedisServiceProvider::class);

/**
 * Alias October\Rain\Router
 */
class_alias(\Winter\Storm\Router\CoreRouter::class, \October\Rain\Router\CoreRouter::class);
class_alias(\Winter\Storm\Router\Helper::class, \October\Rain\Router\Helper::class);
class_alias(\Winter\Storm\Router\Router::class, \October\Rain\Router\Router::class);
class_alias(\Winter\Storm\Router\RoutingServiceProvider::class, \October\Rain\Router\RoutingServiceProvider::class);
class_alias(\Winter\Storm\Router\Rule::class, \October\Rain\Router\Rule::class);
class_alias(\Winter\Storm\Router\UrlGenerator::class, \October\Rain\Router\UrlGenerator::class);

/**
 * Alias October\Rain\Scaffold
 */
class_alias(\Winter\Storm\Scaffold\GeneratorCommand::class, \October\Rain\Scaffold\GeneratorCommand::class);

/**
 * Alias October\Rain\Support
 */
class_alias(\Winter\Storm\Support\Arr::class, \October\Rain\Support\Arr::class);
class_alias(\Winter\Storm\Support\ClassLoader::class, \October\Rain\Support\ClassLoader::class);
class_alias(\Winter\Storm\Support\Collection::class, \October\Rain\Support\Collection::class);
class_alias(\Winter\Storm\Support\Facade::class, \October\Rain\Support\Facade::class);
class_alias(\Winter\Storm\Support\Facades\Block::class, \October\Rain\Support\Facades\Block::class);
class_alias(\Winter\Storm\Support\Facades\Config::class, \October\Rain\Support\Facades\Config::class);
class_alias(\Winter\Storm\Support\Facades\DbDongle::class, \October\Rain\Support\Facades\DbDongle::class);
class_alias(\Winter\Storm\Support\Facades\Event::class, \October\Rain\Support\Facades\Event::class);
class_alias(\Winter\Storm\Support\Facades\File::class, \October\Rain\Support\Facades\File::class);
class_alias(\Winter\Storm\Support\Facades\Flash::class, \October\Rain\Support\Facades\Flash::class);
class_alias(\Winter\Storm\Support\Facades\Form::class, \October\Rain\Support\Facades\Form::class);
class_alias(\Winter\Storm\Support\Facades\Html::class, \October\Rain\Support\Facades\Html::class);
class_alias(\Winter\Storm\Support\Facades\Http::class, \October\Rain\Support\Facades\Http::class);
class_alias(\Winter\Storm\Support\Facades\Ini::class, \October\Rain\Support\Facades\Ini::class);
class_alias(\Winter\Storm\Support\Facades\Input::class, \October\Rain\Support\Facades\Input::class);
class_alias(\Winter\Storm\Support\Facades\Mail::class, \October\Rain\Support\Facades\Mail::class);
class_alias(\Winter\Storm\Support\Facades\Markdown::class, \October\Rain\Support\Facades\Markdown::class);
class_alias(\Winter\Storm\Support\Facades\Schema::class, \October\Rain\Support\Facades\Schema::class);
class_alias(\Winter\Storm\Support\Str::class, \October\Rain\Support\Facades\Str::class);
class_alias(\Winter\Storm\Support\Facades\Twig::class, \October\Rain\Support\Facades\Twig::class);
class_alias(\Winter\Storm\Support\Facades\Url::class, \October\Rain\Support\Facades\Url::class);
class_alias(\Winter\Storm\Support\Facades\Validator::class, \October\Rain\Support\Facades\Validator::class);
class_alias(\Winter\Storm\Support\Facades\Yaml::class, \October\Rain\Support\Facades\Yaml::class);
class_alias(\Winter\Storm\Support\ModuleServiceProvider::class, \October\Rain\Support\ModuleServiceProvider::class);
class_alias(\Winter\Storm\Support\ServiceProvider::class, \October\Rain\Support\ServiceProvider::class);
class_alias(\Winter\Storm\Support\Singleton::class, \October\Rain\Support\Singleton::class);
class_alias(\Winter\Storm\Support\Str::class, \October\Rain\Support\Str::class);
class_alias(\Winter\Storm\Support\Testing\Fakes\EventFake::class, \October\Rain\Support\Testing\Fakes\EventFake::class);
class_alias(\Winter\Storm\Support\Testing\Fakes\MailFake::class, \October\Rain\Support\Testing\Fakes\MailFake::class);
class_alias(\Winter\Storm\Support\Traits\Emitter::class, \October\Rain\Support\Traits\Emitter::class);
class_alias(\Winter\Storm\Support\Traits\KeyParser::class, \October\Rain\Support\Traits\KeyParser::class);
class_alias(\Winter\Storm\Support\Traits\Singleton::class, \October\Rain\Support\Traits\Singleton::class);

/**
 * Alias October\Rain\Translation
 */
class_alias(\Winter\Storm\Translation\FileLoader::class, \October\Rain\Translation\FileLoader::class);
class_alias(\Winter\Storm\Translation\TranslationServiceProvider::class, \October\Rain\Translation\TranslationServiceProvider::class);
class_alias(\Winter\Storm\Translation\Translator::class, \October\Rain\Translation\Translator::class);

/**
 * Alias October\Rain\Validation
 */
class_alias(\Winter\Storm\Validation\Concerns\FormatsMessages::class, \October\Rain\Validation\Concerns\FormatsMessages::class);
class_alias(\Winter\Storm\Validation\Concerns\ValidatesEmail::class, \October\Rain\Validation\Concerns\ValidatesEmail::class);
class_alias(\Winter\Storm\Validation\Factory::class, \October\Rain\Validation\Factory::class);
class_alias(\Winter\Storm\Validation\Rule::class, \October\Rain\Validation\Rule::class);
class_alias(\Winter\Storm\Validation\ValidationServiceProvider::class, \October\Rain\Validation\ValidationServiceProvider::class);
class_alias(\Winter\Storm\Validation\Validator::class, \October\Rain\Validation\Validator::class);
