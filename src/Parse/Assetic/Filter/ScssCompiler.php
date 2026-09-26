<?php namespace Winter\Storm\Parse\Assetic\Filter;

use Assetic\Filter\ScssphpFilter;
use Assetic\Factory\AssetFactory;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\HashableInterface;
use Assetic\Contracts\Filter\DependencyExtractorInterface;
use Winter\Storm\Filesystem\PathResolver;
use Winter\Storm\Support\Facades\Event;

/**
 * SCSS Compiler Filter
 * Class used to compile SCSS files into CSS
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class ScssCompiler extends ScssphpFilter implements HashableInterface, DependencyExtractorInterface
{
    use HasAllowedImportRoots;

    protected $currentFiles = [];

    protected $variables = [];

    protected $lastHash;

    /**
     * Import paths configured on this filter, mirrored from the parent so that they
     * can be treated as allowed import roots. The parent stores them privately.
     *
     * @var array<int, string|callable>
     */
    protected $configuredImportPaths = [];

    /**
     * Directory of the asset currently being compiled. Always an allowed import root,
     * so same-tree `@import "partial"` keeps working without configuration.
     *
     * @var string|null
     */
    protected $sourceDirectory = null;

    /**
     * Whether getChildren() is already running. The parent recurses through the
     * override for each child, and only the outermost call may set the root.
     *
     * @var bool
     */
    protected $resolvingChildren = false;

    public function __construct()
    {
        Event::listen('cms.combiner.beforePrepare', function ($compiler, $assets) {
            foreach ($assets as $asset) {
                if (pathinfo($asset)['extension'] == 'scss') {
                    $this->currentFiles[] = $asset;
                }
            }
        });

        // Confine `@import` resolution to the compiled asset's own directory subtree
        // plus any caller-configured roots, matching the LESS and JavaScript
        // compilers. Without it, scssphp resolves imports against the importing
        // file's own directory with `..` traversal allowed, so resolution is not
        // bounded to the asset tree.
        $this->setImportValidator([$this, 'isImportAllowed']);
    }

    public function setPresets(array $presets)
    {
        $this->variables = array_merge($this->variables, $presets);
    }

    public function setVariables(array $variables)
    {
        $this->variables = array_merge($this->variables, $variables);
    }

    public function addVariable($variable)
    {
        $this->variables[] = $variable;
    }

    public function setImportPaths(array $paths)
    {
        $this->configuredImportPaths = $paths;

        parent::setImportPaths($paths);
    }

    public function addImportPath($path)
    {
        $this->configuredImportPaths[] = $path;

        parent::addImportPath($path);
    }

    /**
     * Determines whether scssphp may inline the file it resolved an `@import` to.
     *
     * Passed to {@see ScssphpFilter::setImportValidator()} and called with the
     * resolved filesystem path of every candidate import.
     */
    public function isImportAllowed(string $path): bool
    {
        $resolved = PathResolver::resolve($path);

        if ($resolved === false) {
            return false;
        }

        // withinAny() skips non-string entries, so callable import paths (which
        // scssphp also accepts) are simply not treated as roots.
        return PathResolver::withinAny($resolved, array_merge(
            [$this->sourceDirectory],
            $this->configuredImportPaths,
            $this->allowedImportRoots
        ));
    }

    public function filterLoad(AssetInterface $asset)
    {
        $this->sourceDirectory = $asset->getSourceDirectory();

        parent::setVariables($this->variables);
        parent::filterLoad($asset);
    }

    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        // Nested calls keep the entry asset's directory as the root, as filterLoad() does.
        if ($this->resolvingChildren) {
            return parent::getChildren($factory, $content, $loadPath);
        }

        $this->resolvingChildren = true;
        $this->sourceDirectory = $loadPath;

        try {
            return parent::getChildren($factory, $content, $loadPath);
        } finally {
            $this->resolvingChildren = false;
        }
    }

    public function setHash($hash)
    {
        $this->lastHash = $hash;
    }

    /**
     * Generates a hash for the object
     * @return string
     */
    public function hash()
    {
        return $this->lastHash ?: serialize($this);
    }

    public function hashAsset($asset, $localPath)
    {
        $factory = new AssetFactory($localPath);
        $children = $this->getChildren($factory, file_get_contents($asset), dirname($asset));

        $allFiles = [];
        foreach ($children as $child) {
            $allFiles[] = $child;
        }

        $modifieds = [];
        foreach ($allFiles as $file) {
            $modifieds[] = $file->getLastModified();
        }

        return md5(implode('|', $modifieds));
    }
}
