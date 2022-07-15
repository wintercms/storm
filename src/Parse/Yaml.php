<?php namespace Winter\Storm\Parse;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Yaml\Yaml as YamlComponent;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Winter\Storm\Parse\Processor\Contracts\YamlProcessor;
use Winter\Storm\Support\Facades\Config;

/**
 * Yaml helper class
 *
 * @author Alexey Bobkov, Samuel Georges
 * @author Winter CMS
 */
class Yaml
{
    /** @var YamlProcessor|null active YAML processor instance */
    protected $processor;

    /**
     * Parses supplied YAML contents in to a PHP array.
     *
     * @param string $contents YAML contents to parse.
     * @return mixed The YAML contents.
     */
    public function parse($contents)
    {
        $yaml = new Parser;

        // Only run the preprocessor if parsing fails
        try {
            $parsed = $yaml->parse($contents);
        } catch (\Throwable $throwable) {
            if (!$this->processor) {
                throw $throwable;
            }
            $contents = $this->processor->preprocess($contents);
            $parsed = $yaml->parse($contents);
        }

        if (!is_null($this->processor)) {
            $parsed = $this->processor->process($parsed);
        }

        return $parsed;
    }

    /**
     * Parses YAML file contents in to a PHP array.
     *
     * @param string $fileName File to read contents and parse.
     * @return mixed The YAML contents.
     */
    public function parseFile($fileName)
    {
        try {
            // Cache parsed yaml file if debug mode is disabled
            if (!Config::get('app.debug', false)) {
                return Cache::remember('yaml::' . $fileName . '-' . filemtime($fileName), now()->addDays(30), function () use ($fileName) {
                    return $this->parse(file_get_contents($fileName));
                });
            } else {
                return $this->parse(file_get_contents($fileName));
            }
        } catch (\Exception $e) {
            throw new ParseException("A syntax error was detected in $fileName. " . $e->getMessage(), __LINE__, __FILE__);
        }
    }

    /**
     * Renders a PHP array to YAML format.
     *
     * Supported options:
     * - inline: The level where you switch to inline YAML.
     * - exceptionOnInvalidType: if an exception must be thrown on invalid types.
     * - objectSupport: if object support is enabled.
     */
    public function render(array $vars = [], array $options = []): string
    {
        $inline = (int) ($options['inline'] ?? 20);
        $exceptionOnInvalidType = (bool) ($options['exceptionOnInvalidType'] ?? false);
        $objectSupport = (bool) ($options['objectSupport'] ?? true);

        $flags = null;

        if ($exceptionOnInvalidType === true) {
            $flags |= YamlComponent::DUMP_EXCEPTION_ON_INVALID_TYPE;
        }

        if ($objectSupport === true) {
            $flags |= YamlComponent::DUMP_OBJECT;
        }

        $yaml = new Dumper;

        if (!is_null($this->processor) && method_exists($this->processor, 'prerender')) {
            $vars = $this->processor->prerender($vars);
        }

        $yamlContent = $yaml->dump($vars, $inline, 0, $flags);

        if (!is_null($this->processor) && method_exists($this->processor, 'render')) {
            $yamlContent = $this->processor->render($yamlContent);
        }

        return $yamlContent;
    }

    /**
     * Sets a processor.
     *
     * @param YamlProcessor $processor
     * @return static
     */
    public function setProcessor(YamlProcessor $processor)
    {
        $this->processor = $processor;
        return $this;
    }

    /**
     * Removes the current processor.
     *
     * @return static
     */
    public function removeProcessor()
    {
        $this->processor = null;
        return $this;
    }

    /**
     * Temporarily uses a processor for the YAML parser within a callback.
     *
     * Once the callback is fired, any previous active processor will be restored.
     *
     * @return mixed
     */
    public function withProcessor(YamlProcessor $processor, callable $callback)
    {
        if (!is_null($this->processor)) {
            $oldProcessor = $this->processor;
        }

        $this->setProcessor($processor);
        $data = $callback($this);
        $this->removeProcessor();

        if (isset($oldProcessor)) {
            $this->setProcessor($oldProcessor);
        }

        return $data;
    }
}
