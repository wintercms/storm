<?php namespace Winter\Storm\Parse\Assetic\Filter;

use Less_Parser;
use Assetic\Filter\BaseFilter;
use Assetic\Filter\LessphpFilter;
use Assetic\Factory\AssetFactory;
use Assetic\Contracts\Asset\AssetInterface;
use Assetic\Contracts\Filter\HashableInterface;
use Assetic\Contracts\Filter\DependencyExtractorInterface;

/**
 * Less.php Compiler Filter
 * Class used to compile LESS stylesheet files into CSS
 *
 * @link https://github.com/wikimedia/less.php
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class LessCompiler extends BaseFilter implements HashableInterface, DependencyExtractorInterface
{
    protected $presets = [];

    protected $lastHash;

    /**
     * Additional roots beyond the asset's own source directory that `@import`
     * directives are allowed to resolve into. Configured by the caller (typically
     * `System\Classes\CombineAssets`) to permit legitimate cross-tree imports
     * (e.g. a plugin asset importing a module asset). Defaults to none — the
     * asset's own directory subtree is always allowed implicitly via the
     * resolver's contextDir rule.
     *
     * @var string[]
     */
    protected array $allowedImportRoots = [];

    public function setPresets(array $presets)
    {
        $this->presets = $presets;
    }

    /**
     * Configure additional roots that `@import` directives may resolve into. The
     * source file's own directory is always allowed; this list adds cross-tree
     * destinations.
     *
     * @param string[] $roots
     */
    public function setAllowedImportRoots(array $roots): void
    {
        $this->allowedImportRoots = $roots;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $parser = new Less_Parser();

        // Ensure unchanged behavior across Less.php 3.x and Less.php 5.x
        $parser->SetOption('strictMath', false);

        // CSS Rewriter will take care of this
        $parser->SetOption('relativeUrls', false);

        $sourceFile = $asset->getSourceRoot() . '/' . $asset->getSourcePath();

        // Constrain `@import` resolution to the source file's own directory subtree
        // plus any caller-configured roots. Without this, `@import (inline) "<path>"`
        // could disclose arbitrary server-readable files. See GHSA-58fp-mcx6-7qf9.
        $parser->SetImportDirs(
            LessImportResolver::buildImportDirs($sourceFile, $this->allowedImportRoots)
        );

        $parser->parseFile($sourceFile);

        // Set the LESS variables after parsing to override them
        $parser->ModifyVars($this->presets);

        $asset->setContent($parser->getCss());
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

    /**
     * Load children recusive
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $children = (new LessphpFilter)->getChildren($factory, $content, $loadPath);

        foreach ($children as $child) {
            $childContent = file_get_contents($child->getSourceRoot().'/'.$child->getSourcePath());
            $children = array_merge($children, (new LessphpFilter)->getChildren($factory, $childContent, $loadPath.'/'.dirname($child->getSourcePath())));
        }

        return $children;
    }
}
