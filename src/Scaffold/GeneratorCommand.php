<?php namespace Winter\Storm\Scaffold;

use Exception;
use ReflectionClass;
use Winter\Storm\Console\Command;
use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Support\Facades\Twig;
use Winter\Storm\Support\Str;

/**
 * Generator command.
 *
 * This class is used as a base for scaffolding commands, modifying stub files and copying them over to a specific
 * location.
 */
abstract class GeneratorCommand extends Command
{
    /**
     * @var \Winter\Storm\Filesystem\Filesystem The filesystem instance.
     */
    protected $files;

    /**
     * @var string The type of class being generated.
     */
    protected $type;

    /**
     * @var string The argument that the generated class name comes from
     */
    protected $nameFrom = 'name';

    /**
     * Reserved names that cannot be used for generation.
     *
     * @var string[]
     */
    protected $reservedNames = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
    ];

    /**
     * @var array A mapping of stub to generated file.
     */
    protected $stubs = [];

    /**
     * @var array An array of variables to use in stubs.
     */
    protected $vars = [];

    /**
     * Create a new controller creator command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->files = new Filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        // First we need to ensure that the given name is not a reserved word within the PHP
        // language and that the class name will actually be valid. If it is not valid we
        // can error now and prevent from polluting the filesystem using invalid files.
        if ($this->isReservedName($this->getNameInput())) {
            $this->error('The name "'.$this->getNameInput().'" is reserved by PHP.');

            return false;
        }

        $this->vars = $this->processVars($this->prepareVars());

        $this->makeStubs();

        $this->info($this->type . ' created successfully.');
    }

    /**
     * Prepare variables for stubs.
     */
    abstract protected function prepareVars(): array;

    /**
     * Make all stubs.
     */
    public function makeStubs(): void
    {
        $stubs = array_keys($this->stubs);

        // Make sure this command won't overwrite any existing files before running
        if (!$this->option('force')) {
            foreach ($stubs as $stub) {
                $destinationFile = $this->getDestinationForStub($stub);
                if ($this->files->exists($destinationFile)) {
                    throw new Exception("Cannot create the {$this->type}:\r\n$destinationFile already exists.\r\nPass --force to overwrite existing files.");
                }
            }
        }

        foreach ($stubs as $stub) {
            $this->makeStub($stub);
        }
    }

    /**
     * Get the destination path for the provided stub name
     */
    protected function getDestinationForStub(string $stubName): string
    {
        return Twig::parse(
            $this->getDestinationPath() . '/' . $this->stubs[$stubName],
            $this->vars
        );
    }

    /**
     * Make a single stub.
     *
     * @param string $stubName The source filename for the stub.
     */
    public function makeStub($stubName)
    {
        if (!isset($this->stubs[$stubName])) {
            return;
        }

        $sourceFile = $this->getSourcePath() . '/' . $stubName;
        $destinationFile = $this->getDestinationForStub($stubName);
        $destinationContent = $this->files->get($sourceFile);

        /*
         * Parse each variable in to the destination content
         */
        $destinationContent = Twig::parse($destinationContent, $this->vars);

        $this->makeDirectory($destinationFile);

        $this->files->put($destinationFile, $destinationContent);

        $this->comment('File generated: ' . str_replace(base_path(), '', $destinationFile));
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return void
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Converts all variables to available modifier and case formats.
     * Syntax is CASE_MODIFIER_KEY, eg: lower_plural_xxx
     */
    protected function processVars(array $vars): array
    {
        $cases = ['upper', 'lower', 'snake', 'studly', 'camel', 'title'];
        $modifiers = ['plural', 'singular', 'title'];

        foreach ($vars as $key => $var) {
            /*
             * Apply cases, and cases with modifiers
             */
            foreach ($cases as $case) {
                $primaryKey = $case . '_' . $key;
                $vars[$primaryKey] = $this->modifyString($case, $var);

                foreach ($modifiers as $modifier) {
                    $secondaryKey = $case . '_' . $modifier . '_' . $key;
                    $vars[$secondaryKey] = $this->modifyString([$modifier, $case], $var);
                }
            }

            /*
             * Apply modifiers
             */
            foreach ($modifiers as $modifier) {
                $primaryKey = $modifier . '_' . $key;
                $vars[$primaryKey] = $this->modifyString($modifier, $var);
            }
        }

        return $vars;
    }

    /**
     * Internal helper that handles modify a string, with extra logic.
     *
     * @param string|array $type
     * @param string $string
     * @return string
     */
    protected function modifyString($type, $string)
    {
        if (is_array($type)) {
            foreach ($type as $_type) {
                $string = $this->modifyString($_type, $string);
            }

            return $string;
        }

        if ($type == 'title') {
            $string = str_replace('_', ' ', Str::snake($string));
        }

        return Str::$type($string);
    }

    /**
     * Get the base path to output generated stubs to
     */
    protected function getDestinationPath(): string
    {
        return base_path();
    }

    /**
     * Get the base path to source stub files from
     */
    protected function getSourcePath(): string
    {
        $className = get_class($this);
        $class = new ReflectionClass($className);

        return dirname($class->getFileName());
    }

    /**
     * Get the desired class name from the input.
     */
    protected function getNameInput(): string
    {
        return trim($this->argument($this->nameFrom));
    }

    /**
     * Checks whether the given name is reserved.
     */
    protected function isReservedName(string $name): bool
    {
        $name = strtolower($name);

        return in_array($name, $this->reservedNames);
    }
}
