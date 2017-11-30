<?php

namespace Tutu\Wsdl2PhpGenerator\PhpType;

use \InvalidArgumentException;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocComment;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocElement;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpFunction;
use Tutu\Wsdl2PhpGenerator\Validation\Validator;

/**
 * Class Enum
 *
 * @package Tutu\Wsdl2PhpGenerator\PhpType
 */
class Enum extends Type
{
	/**
	 * @var array The values in the enum
	 */
	private $values;


	/**
	 * Construct the object
	 *
	 * @param ConfigInterface $config      The configuration
	 * @param string          $name        The identifier for the class
	 * @param string          $restriction The restriction(dataType) of the values
	 */
	public function __construct(ConfigInterface $config, $name, $restriction)
	{
		parent::__construct($config, $name, $restriction);
		$this->values = [];
	}


	/**
	 * Implements the loading of the class object
	 *
	 * @throws \Exception if the class is already generated(not null)
	 */
	protected function generateClass()
	{
		if ($this->class != null)
		{
			throw new \Exception("The class has already been generated");
		}

		$this->class = new PhpClass($this->getNamespace(), $this->phpIdentifier, false);
		$this->class->setSavePath($this->config->get('enumerationTypeFolder'));

		$first = true;
		$names = [];
		foreach ($this->values as $value)
		{
			$value = trim($value);
			if(!empty($value))
			{
				$name = Validator::validateConstant($value);
				$name = Validator::validateUnique(
					$name,
					function ($name) use ($names) { return !in_array($name, $names); }
				);

				if ($first)
				{
					$this->class->addConstant($name, '__default');
					$first = false;
				}

				$this->class->addConstant($value, $name);
				$names[] = $name;
			}
		}

		// add check functions

		$isValidEnumValueComment = new PhpDocComment();
		$isValidEnumValueComment->setDescription('Check for valid value');
		$isValidEnumValueComment->addParam(new PhpDocElement('param', null, 'value', null));
		$isValidEnumValueComment->addParam(new PhpDocElement('return', 'mixed', null, null));
		$isValidEnumValueSource = '  return in_array(' . PHP_EOL;
		$isValidEnumValueSource .='    $value,' . PHP_EOL;
		$isValidEnumValueSource .='    [' . PHP_EOL;
		foreach ($names as $name)
		{
			$isValidEnumValueSource .= '      self::' . $name . ',' . PHP_EOL;
		}
		$isValidEnumValueSource .= '    ]' . PHP_EOL;
		$isValidEnumValueSource .= '  );';
		$isValidEnumValueFunction = new PhpFunction(
			'public',
			'isValidEnumValue',
			'$value',
			$isValidEnumValueSource,
			$isValidEnumValueComment
		);
		$this->class->addFunction($isValidEnumValueFunction);
	}


	/**
	 * Implements the generation of namespace
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		if ($this->config->get('namespaceName'))
		{
			$namespace = $this->config->get('namespaceName') . '\\';
			$namespace .= (!empty($this->config->get('enumerationTypeFolder')))
				? ucfirst($this->config->get('enumerationTypeFolder'))
				: '';
			return $namespace;
		}
		return null;
	}


	/**
	 * Adds the value, type-checks strings and integers.
	 * Otherwise it only checks so the value is not null
	 *
	 * @param mixed $value The value to add
	 *
	 * @throws InvalidArgumentException if the value doesn't fit in the restriction
	 */
	public function addValue($value)
	{
		if ($this->dataType == 'string')
		{
			if (is_string($value) == false)
			{
				throw new InvalidArgumentException(
					sprintf('The value (%s) is not string but the restriction demands it', $value)
                );
			}
		}
		elseif ($this->dataType == 'integer')
		{
			// The value comes as string from the wsdl
			if (is_string($value))
			{
				$value = intval($value);
			}

			if (is_int($value) == false)
			{
				throw new InvalidArgumentException(
                    sprintf('The value (%s) is not int but the restriction demands it', $value)
				);
			}
		}
		else
		{
			if ($value == null)
			{
				throw new InvalidArgumentException(
                    sprintf('The value (%s) is null', $value)
                );
			}
		}

		$this->values[] = $value;
	}


	/**
	 * Returns a comma separated list of all the possible values for the enum
	 *
	 * @return string
	 */
	public function getValidValues()
	{
		$ret = '';
		foreach ($this->values as $value)
		{
			$ret .= $value . ', ';
		}

		$ret = substr($ret, 0, -2);

		return $ret;
	}
}
