<?php

namespace Tutu\Wsdl2PhpGenerator\WsdlHandler;

use Tutu\Wsdl2PhpGenerator\Base\ClassGenerator;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocComment;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocElement;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpFunction;
use Tutu\Wsdl2PhpGenerator\Validation\Validator;

/**
 * Class Enum
 * @package Tutu\Wsdl2PhpGenerator\WsdlHandler
 */
class Enum implements ClassGenerator
{
	/**
	 * @var ConfigInterface
	 */
	public $config;
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var array
	 */
	public $constants = [];
	/**
	 * @var mixed
	 */
	public $xmlNode;

	/**
	 * @var PhpClass The class used to create the type.
	 */
	public $class;


	/**
	 * Enum constructor.
	 *
	 * @param ConfigInterface $config
	 * @param string          $name
	 * @param array           $constants
	 * @param mixed           $xmlNode
	 */
	public function __construct($config, $name = null, array $constants = [], $xmlNode = null)
	{
		$this->config         = $config;
		$this->name           = $name;
		$this->constants      = $constants;
		$this->xmlNode        = $xmlNode;
		$this->class          = null;
	}


	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}


	/**
	 * @return array
	 */
	public function getConstants(): array
	{
		return $this->constants;
	}


	/**
	 * @param array $constants
	 */
	public function setConstants(array $constants)
	{
		$this->constants = $constants;
	}


	/**
	 * @return mixed
	 */
	public function getXmlNode()
	{
		return $this->xmlNode;
	}


	/**
	 * @param mixed $xmlNode
	 */
	public function setXmlNode($xmlNode)
	{
		$this->xmlNode = $xmlNode;
	}


	/**
	 * Getter for the class. Generates the class if it's null
	 *
	 * @return PhpClass
	 */
	public function getClass()
	{
		if ($this->class == null)
		{
			$this->generateClass();
		}

		return $this->class;
	}


	/**
	 * Implements the loading of the class object
	 *
	 * @throws GeneratorException if the class is already generated(not null)
	 */
	public function generateClass()
	{
		if ($this->class != null)
		{
			throw new GeneratorException("The class has already been generated");
		}
		$className      = $this->getClassName();
		$classNamespace = $this->getNamespace();

		$this->class = new PhpClass($classNamespace, $className, false);
		$this->class->setSavePath($this->config->get($this->config::ENUMS_DIRECTORY));

		$first = true;
		$names = [];
		foreach ($this->constants as $value)
		{
			$value = trim($value);
			if (!empty($value))
			{
				$name = $value;
				$name = Validator::validateUnique(
					$name,
					function ($name) use ($names) { return !in_array($name, $names); }
				);

				if ($first)
				{
					$this->class->setDefault($name);
					$first = false;
				}

				$this->class->addConstant($value, $name);
				$names[] = $name;
			}
		}

		// add check functions

		$isValidEnumValueComment = new PhpDocComment();
		$isValidEnumValueComment->setDescription('Check for valid value');
		$isValidEnumValueComment->addParam(new PhpDocElement('param', 'string', 'value', null));
		$isValidEnumValueComment->addParam(new PhpDocElement('return', 'bool', null, null));
		$isValidEnumValueSource = '  return in_array(' . PHP_EOL;
		$isValidEnumValueSource .= '    $value,' . PHP_EOL;
		$isValidEnumValueSource .= '    [' . PHP_EOL;
		foreach ($names as $name)
		{
			$isValidEnumValueSource .= '      self::VALUE_' . strtoupper($name) . ',' . PHP_EOL;
		}
		$isValidEnumValueSource   .= '    ]' . PHP_EOL;
		$isValidEnumValueSource   .= '  );';
		$isValidEnumValueFunction = new PhpFunction(
			'public static',
			'isValidEnumValue',
			'$value',
			$isValidEnumValueSource,
			$isValidEnumValueComment
		);
		$this->class->addFunction($isValidEnumValueFunction);
	}


	/**
	 * Get class name
	 *
	 * @return string
	 */
	public function getClassName()
	{
		return Validator::validateClass($this->getName());
	}


	/**
	 * Implements the generation of namespace
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		if ($this->config->get($this->config::PACKAGE_NAMESPACE))
		{
			$namespace = $this->config->get($this->config::PACKAGE_NAMESPACE) . '\\';
			$namespace .= (!empty($this->config->get($this->config::ENUMS_DIRECTORY)))
				? ucfirst($this->config->get($this->config::ENUMS_DIRECTORY))
				: '';
			return $namespace;
		}
		return null;
	}

	/**
	 * Returns a comma separated list of all the possible values for the enum
	 *
	 * @return string
	 */
	public function getValidValues()
	{
		$ret = '';
		foreach ($this->constants as $value)
		{
			$ret .= $value . ', ';
		}

		$ret = substr($ret, 0, -2);

		return $ret;
	}
}