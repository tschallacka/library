<?php namespace October\Rain\Database;

use File;
use Eloquent;

class Updater
{

    /**
     * Sets up a migration or seed file.
     */
    public function setUp($file)
    {
        $object = $this->resolve($file);
        if ($object === null)
            return false;

        Eloquent::unguard();

        $this->isValidScript($object);

        if ($object instanceof Updates\Migration)
            $object->up();
        elseif ($object instanceof Updates\Seeder)
            $object->run();

        Eloquent::reguard();
        return true;
    }

    /**
     * Packs down a migration or seed file.
     */
    public function packDown($file)
    {
        $object = $this->resolve($file);
        if ($object === null)
            return false;

        Eloquent::unguard();

        $this->isValidScript($object);

        if ($object instanceof Updates\Migration)
            $object->down();

        Eloquent::reguard();
        return true;
    }

    /**
     * Resolve a migration instance from a file.
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        if (!File::exists($file))
            return;

        require_once $file;
        $class = $this->getClassFromFile($file);
        return new $class;
    }

    /**
     * Checks if the object is a valid update script.
     */
    private function isValidScript($object)
    {
        if ($object instanceof Updates\Migration)
            return true;
        elseif ($object instanceof Updates\Seeder)
            return true;

        throw new \Exception('Database script ' . get_class($object) . ' must inherit October\Rain\Database\Updates\Migration or October\Rain\Database\Updates\Seeder classes');
    }

    /**
     * Extracts the namespace and class name from a file.
     * @param string $file
     * @return string
     */
    public function getClassFromFile($file)
    {
        $fileParser = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;

        while (!$class) {
            if (feof($fileParser))
                break;

            $buffer .= fread($fileParser, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false)
                continue;

            for (; $i < count($tokens); $i++) {

                /*
                 * Namespace opening
                 */
                if ($tokens[$i][0] === T_NAMESPACE) {

                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === ';')
                            break;

                        $namespace .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                    }

                }

                /*
                 * Class opening
                 */
                if ($tokens[$i][0] === T_CLASS) {

                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i+2][1];
                            break;
                        }
                    }

                }

            }
        }

        return trim($namespace) . '\\' . trim($class);
    }

}