<?php

namespace Tutu\Wsdl2PhpGenerator\PhpType;

use Tutu\Wsdl2PhpGenerator\Base\ClassGenerator;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Generator\Generator;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;
use Tutu\Wsdl2PhpGenerator\Validation\Validator;

/**
 * Class Type
 *
 * @package Tutu\Wsdl2PhpGenerator\PhpType
 */
abstract class Type implements ClassGenerator
{

	/**
	 * @var ConfigInterface
	 */
	protected $config;

	/**
	 * @var Generator
	 */
	protected $generator;

	/**
	 * @var PhpClass The class used to create the type. This is not used by patterns
	 */
	protected $class;

	/**
	 * @var string The name of the type
	 */
	protected $identifier;

	/**
	 * @var string The name of the type used in php code ie. the validated name
	 */
	protected $phpIdentifier;

	/**
	 * @var string The name of the type used in php code with namespace (if needed) ie. the validated name
	 */
	protected $phpNamespaceIdentifier;

	/**
	 * @var string The dataType the simple type is of. This not used by complex types
	 */
	protected $dataType;


	/**
	 * The minimum construction
	 *
	 * @param ConfigInterface $config   The configuration
	 * @param string          $name     The identifier for the type
	 * @param string          $dataType The restriction(dataType)
	 */
	public function __construct(ConfigInterface $config, $name, $dataType)
	{
		$this->config     = $config;
		$this->class      = null;
		$this->dataType   = $dataType;
		$this->identifier = $name;

		$this->phpIdentifier           = Validator::validateClass($name, $this->getNamespace());
		$namespace                     = $this->getNamespace();
		$this->phpNamespaceIdentifier  = ($namespace)
			? '\\' . $namespace . '\\' . $this->phpIdentifier
			: $this->phpIdentifier;
	}


	/**
	 * The abstract function for subclasses to implement
	 * This should load the class data into $class
	 * This is called by getClass if not previously called
	 */
	abstract protected function generateClass();


	/**
	 * The abstract function for subclasses to implement
	 * This should load the class namespace into $phpNamespaceIdentifier
	 * This is called by constructor if not previously called
	 *
	 * @return $string
	 */
	abstract public function getNamespace();


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
	 * Getter for the dataType
	 *
	 * @return string
	 */
	public function getDataType()
	{
		return $this->dataType;
	}


	/**
	 * Getter for the name
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}


	/**
	 * @return string The validated name of the type
	 */
	public function getPhpIdentifier()
	{
		return $this->phpIdentifier;
	}


	/**
	 * @return string The validated name of the type with namespace
	 */
	public function getPhpNamespaceIdentifier()
	{
		return $this->phpNamespaceIdentifier;
	}


	/**
	 * @return Generator
	 */
	public function getGenerator()
	{
		return $this->generator;
	}


	/**
	 * @param Generator $generator
	 */
	public function setGenerator(Generator $generator)
	{
		$this->generator = $generator;
	}
}
