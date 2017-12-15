<?php

namespace Tutu\Wsdl2PhpGenerator\Validation;

/**
 * Class Validator
 *
 * @package Tutu\Wsdl2PhpGenerator\Validation
 */
class Validator
{
	/**
	 * The prefix to prepend to invalid names.
	 *
	 * @var string
	 */

	const NAME_PREFIX = 'a';

	/**
	 * The suffix to append to invalid names.
	 *
	 * @var string
	 */
	const NAME_SUFFIX = 'Custom';

	/**
	 * Array containing all PHP keywords.
	 *
	 * @var array
	 * @link http://www.php.net/manual/en/reserved.keywords.php
	 */
	private static $keywords = [
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
		'float',
		'final',
		'finally',
		'for',
		'foreach',
		'function',
		'global',
		'goto',
		'if',
		'implements',
		'include',
		'include_once',
		'int',
		'instanceof',
		'insteadof',
		'interface',
		'isset',
		'list',
		'namespace',
		'new',
		'or',
		'parent',
		'print',
		'private',
		'protected',
		'public',
		'require',
		'require_once',
		'return',
		'static',
		'string',
		'switch',
		'throw',
		'trait',
		'try',
		'unset',
		'use',
		'var',
		'while',
		'xor',
		'yield'
	];


	/**
	 * Get class name from namespace
	 * 
	 * @param string $name
	 *
	 * @return string
	 */
	public static function getClassNameFromNS($name)
	{
		$nameParts = explode('\\', $name);
		return array_pop($nameParts);
	}


	/**
	 * Validates a class name against PHP naming conventions and already defined classes.
	 *
	 * @param string $name      the name of the class to test
	 * @param string $namespace the name of the namespace
	 *
	 * @return string The validated version of the submitted class name
	 */
	public static function validateClass($name, $namespace = null)
	{
		$name      = trim($name, '_');
		$nameParts = explode('.', $name);
		$name      = implode(', ', array_map("ucfirst", $nameParts));
		$name      = self::validateNamingConvention($name);

		$prefix = !empty($namespace) ? $namespace . '\\' : '';

		$name = self::validateUnique(
			$name,
			function ($name) use ($prefix)
			{
				// Use reflection to get access to private isKeyword method.
				// @todo Remove this when we stop supporting PHP 5.3.
				$isKeywordMethod = new \ReflectionMethod(__CLASS__, 'isKeyword');
				$isKeywordMethod->setAccessible(true);
				$isKeyword = $isKeywordMethod->invoke(null, $name);
				return !$isKeyword &&
					!interface_exists($prefix . $name) &&
					!class_exists($prefix . $name);
			},
			self::NAME_SUFFIX
		);

		return $name;
	}


	/**
	 * Validates an operation name against PHP naming conventions.
	 *
	 * @param string $name the name of the operation to test
	 *
	 * @return string The validated version of the submitted operation name
	 */
	public static function validateOperation($name)
	{
		$name = self::validateNamingConvention($name);
		if (self::isKeyword($name))
		{
			$name = self::NAME_PREFIX . ucfirst($name);
		}
		return $name;
	}


	/**
	 * Validates an attribute name against PHP naming conventions.
	 *
	 * @param string $name the name of the attribute to test
	 *
	 * @return string The validated version of the submitted attribute name
	 */
	public static function validateAttribute($name)
	{
		// Contrary to other validations attributes can have names which are also keywords. Thus no need to check for
		// this here.
		return self::validateNamingConvention($name);
	}


	/**
	 * Validates a constant name against PHP naming conventions.
	 *
	 * @param string $name the name of the constant to test
	 *
	 * @return string The validated version of the submitted constant name
	 */
	public static function validateConstant($name)
	{
		$name = self::validateNamingConvention($name);
		if (self::isKeyword($name))
		{
			$name = self::NAME_PREFIX . ucfirst($name);
		}
		return $name;
	}


	/**
	 * Validates a wsdl type against known PHP primitive types, or otherwise
	 * validates the namespace of the type to PHP naming conventions
	 *
	 * @param string $typeName the type to test
	 *
	 * @return string the validated version of the submitted type
	 */
	public static function validateType($typeName)
	{
		// case of type variation
		$lowerTypeName = strtolower($typeName);
		foreach (self::getTypeMappings() as $type => $knownVariations)
		{
			if (in_array($lowerTypeName, $knownVariations))
			{
				return $type;
			}
		}

		// case of anonymous type
		if (strpos($typeName, 'anonymous') !== false)
		{
			return 'string';
		}

		return $typeName;
	}


	/**
	 * Validates a type to be used as a method parameter type hint.
	 *
	 * @param string $typeName The name of the type to test.
	 *
	 * @return null|string Returns a valid type hint for the type or null if there is no valid type hint.
	 */
	public static function validateTypeHint($typeName)
	{
		$typeHint = null;

		// We currently only support type hints for arrays.
		// Going forward we could support it for generated types. The challenge here are enums as they are actually
		// strings and not class instances and we have no way of determining whether the type is an enum at this point.
		if (substr($typeName, -2) == "[]")
		{
			$typeHint = 'array';
		}

		return $typeHint;
	}


	/**
	 * Validate that a name is unique.
	 *
	 * If a name is not unique then append a suffix and numbering.
	 *
	 * @param string   $name     The name to test.
	 * @param callable $function A callback which should return true if the element is unique. Otherwise false.
	 * @param string   $suffix   A suffix to append between the name and numbering.
	 *
	 * @return string A unique name.
	 */
	public static function validateUnique($name, $function, $suffix = null)
	{
		$i       = 1;
		$newName = $name;
		while (!call_user_func($function, $newName))
		{
			if (!$suffix)
			{
				$newName = $name . ($i + 1);
			}
			elseif ($i == 1)
			{
				$newName = $name . $suffix;
			}
			else
			{
				$newName = $name . $suffix . $i;
			}
			$i++;
		}

		return $newName;
	}


	/**
	 * Validates a name against standard PHP naming conventions
	 *
	 * @param string $name the name to validate
	 *
	 * @return string the validated version of the submitted name
	 */
	private static function validateNamingConvention($name)
	{
		$name = iconv("UTF-8", "ASCII//TRANSLIT", $name);
		// Prepend the string a to names that begin with anything but a-z This is to make a valid name
		if (preg_match('/^[A-Za-z_]/', $name) == false)
		{
			$name = self::NAME_PREFIX . ucfirst($name);
		}

		return preg_replace('/[^a-zA-Z0-9_x7f-xff]*/', '', preg_replace('/^[^a-zA-Z_x7f-xff]*/', '', $name));
	}


	/**
	 * Checks if a string is a restricted keyword.
	 *
	 * @param string $string the string to check..
	 *
	 * @return boolean Whether the string is a restricted keyword.
	 */
	private static function isKeyword($string)
	{
		return in_array(strtolower($string), self::$keywords);
	}


	private static function getTypeMappings()
	{
		return [
			'array'  => [
				'array',
			],
			'bool'   => [
				'bool',
				'boolean'
			],
			'float'  => [
				'float',
				'double',
				'decimal',
			],
			'int'    => [
				'int',
				'integer',
				'long',
				'byte',
				'short',
				'negativeinteger',
				'nonnegativeinteger',
				'nonpositiveinteger',
				'positiveinteger',
				'unsignedbyte',
				'unsignedint',
				'unsignedlong',
				'unsignedshort',
				'numeric',
				'numeric0to9',
				'numeric0to99',
				'numeric0to999',
				'numeric0to9999',
				'numeric0to99999',
			],
			'string' => [
				'string',
				'stringvalue',
				'stringtype',
				'commonstring',
				'<anyxml>',
				'anytype',
				'anyuri',
				'unknown',
				'token',
				'nmtoken',
				'normalizedstring',
				'nonemptystring',
				'non-empty-string',
				'notemptystring',
				'not-empty-string',
				'stringlength1to20',
				'stringlength1to10',
				'date',
				'datetime',
				'gmonth',
				'gyear',
				'time',
				'hexbinary',
				'cryptobinary',
				'base64binary',
				'textlong',
				'text.long',
				'textshort',
				'text.short',
				'pseudocitycodeoroac',
				'airlinecodeorspecification',
			],
		];
	}
}
