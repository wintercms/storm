<?php namespace Winter\Storm\Database;

use Exception;
use Winter\Storm\Support\Facades\File;
use Winter\Storm\Database\Model;

/**
 * Database updater
 *
 * Executes database migration and seed scripts based on their filename.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class Updater
{
    /**
     * @var array Local cache of migration file paths to support anonymous migrations [$path => $anonInstance || $className]
     */
    protected static $migrationCache = [];

    /**
     * Sets up a migration or seed file.
     */
    public function setUp($file)
    {
        $object = $this->resolve($file);

        if ($object === null) {
            return false;
        }

        $this->isValidScript($object, $file);

        Model::unguard();

        if ($object instanceof Updates\Migration && method_exists($object, 'up')) {
            $object->up();
        }
        elseif ($object instanceof Updates\Seeder && method_exists($object, 'run')) {
            $object->run();
        }

        Model::reguard();

        return true;
    }

    /**
     * Packs down a migration or seed file.
     */
    public function packDown($file)
    {
        $object = $this->resolve($file);

        if ($object === null) {
            return false;
        }

        $this->isValidScript($object, $file);

        Model::unguard();

        if ($object instanceof Updates\Migration && method_exists($object, 'down')) {
            $object->down();
        }

        Model::reguard();

        return true;
    }

    /**
     * Resolve a migration instance from a file.
     * @param  string  $file
     * @return object|null
     */
    public function resolve($file): ?object
    {
        if (!File::isFile($file)) {
            return null;
        }

        if (isset(static::$migrationCache[$file])) {
            return is_object(static::$migrationCache[$file])
                ? static::$migrationCache[$file]
                : new static::$migrationCache[$file];
        }

        $instance = require_once $file;

        if (is_object($instance)) {
            return static::$migrationCache[$file] = $instance;
        }

        if ($class = $this->getClassFromFile($file)) {
            return new (static::$migrationCache[$file] = $class);
        }
        
        return null;
    }

    /**
     * Checks if the object is a valid update script.
     */
    protected function isValidScript($object, $file)
    {
        if ($object instanceof Updates\Migration) {
            return true;
        }
        elseif ($object instanceof Updates\Seeder) {
            return true;
        }

        throw new Exception(sprintf(
            'Database script [%s] must define a class that inherits the "Winter\Storm\Database\Updates\Migration" or "Winter\Storm\Database\Updates\Seeder" classes',
            $file
        ));
    }

    /**
     * Extracts the namespace and class name from a file.
     * @param string $file
     * @return string|false
     */
    public function getClassFromFile($file)
    {
        $fileParser = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;

        while (!$class) {
            if (feof($fileParser)) {
                break;
            }

            $buffer .= fread($fileParser, 512);

            // Prefix and suffix string to prevent unterminated comment warning
            $tokens = token_get_all('/**/' . $buffer . '/**/');

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                /*
                 * Namespace opening
                 */
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === ';') {
                            break;
                        }

                        $namespace .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                    }
                }

                /*
                 * Class opening
                 */
                if ($tokens[$i][0] === T_CLASS && $tokens[$i-1][1] !== '::') {
                    $class = $tokens[$i+2][1];
                    break;
                }
            }
        }

        if (!strlen(trim($namespace)) && !strlen(trim($class))) {
            return false;
        }

        return trim($namespace) . '\\' . trim($class);
    }
}
