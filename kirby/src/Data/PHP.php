<?php

namespace Kirby\Data;

use Kirby\Exception\BadMethodCallException;
use Kirby\Exception\Exception;
use Kirby\Filesystem\F;

/**
 * Reader and write of PHP files with data in a returned array
 *
 * @package   Kirby Data
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class PHP extends Handler
{
	/**
	 * Converts an array to PHP file content
	 *
	 * @param string $indent For internal use only
	 */
	public static function encode($data, string $indent = ''): string
	{
		switch (gettype($data)) {
			case 'array':
				$indexed = array_keys($data) === range(0, count($data) - 1);
				$array   = [];

				foreach ($data as $key => $value) {
					$array[] = "$indent    " . ($indexed ? '' : static::encode($key) . ' => ') . static::encode($value, "$indent    ");
				}

				return "[\n" . implode(",\n", $array) . "\n" . $indent . ']';
			case 'boolean':
				return $data ? 'true' : 'false';
			case 'integer':
			case 'double':
				return (string)$data;
			default:
				return var_export($data, true);
		}
	}

	/**
	 * PHP strings shouldn't be decoded manually
	 */
	public static function decode($string): array
	{
		throw new BadMethodCallException('The PHP::decode() method is not implemented');
	}

	/**
	 * Reads data from a file
	 */
	public static function read(string $file): array
	{
		if (is_file($file) !== true) {
			throw new Exception('The file "' . $file . '" does not exist');
		}

		return (array)F::load($file, []);
	}

	/**
	 * Creates a PHP file with the given data
	 */
	public static function write(string $file, $data = []): bool
	{
		$php = static::encode($data);
		$php = "<?php\n\nreturn $php;";

		if (F::write($file, $php) === true) {
			F::invalidateOpcodeCache($file);
			return true;
		}

		return false; // @codeCoverageIgnore
	}
}
