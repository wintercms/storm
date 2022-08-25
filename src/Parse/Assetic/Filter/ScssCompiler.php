<?php namespace Winter\Storm\Parse\Assetic\Filter;

use Assetic\Filter\ScssphpFilter;
use Assetic\Factory\AssetFactory;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\HashableInterface;
use Assetic\Contracts\Filter\DependencyExtractorInterface;
use Winter\Storm\Support\Facades\Event;

/**
 * SCSS Compiler Filter
 * Class used to compile SCSS files into CSS
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class ScssCompiler extends ScssphpFilter implements HashableInterface, DependencyExtractorInterface
{
    protected $currentFiles = [];

    protected $variables = [];

    protected $lastHash;

    public function __construct()
    {
        Event::listen('cms.combiner.beforePrepare', function ($compiler, $assets) {
            foreach ($assets as $asset) {
                if (pathinfo($asset)['extension'] == 'scss') {
                    $this->currentFiles[] = $asset;
                }
            }
        });
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

    public function filterLoad(AssetInterface $asset)
    {
        parent::setVariables($this->variables);
        parent::filterLoad($asset);
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
