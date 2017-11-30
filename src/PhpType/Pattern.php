<?php

namespace Tutu\Wsdl2PhpGenerator\PhpType;

use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;

/**
 * Class Pattern
 *
 * @package Tutu\Wsdl2PhpGenerator\PhpType
 */
class Pattern extends Type
{
	/**
	 * @var string The pattern string
	 */
	private $value;

	/**
	 * @var string The baseType string
	 */
	private $baseType;


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
		$this->value = '';
	}


	/**
	 * Implements the loading of the class object
	 * Always returns null because the pattern is not used as a class
	 *
	 * @throws \Exception if the class is already generated(not null)
	 * @return null
	 */
	protected function generateClass()
	{
		if ($this->class != null)
		{
			throw new \Exception("The class has already been generated");
		}

		return null;
	}


	/**
	 * Implements the generation of namespace
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		return null;
	}

	/**
	 * @return string The pattern string
	 */
	public function getValue()
	{
		return $this->value;
	}


	/**
	 * @param string $value The pattern string
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}


	/**
	 * @return string The baseType string
	 */
	public function getBaseType()
	{
		return $this->baseType;
	}


	/**
	 * @param string $baseType The baseType string
	 */
	public function setBaseType(string $baseType = null)
	{
		$this->baseType = $baseType;
	}
}
