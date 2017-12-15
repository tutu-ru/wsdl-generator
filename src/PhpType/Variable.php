<?php

namespace Tutu\Wsdl2PhpGenerator\PhpType;

/**
 * Class Variable
 *
 * @package Tutu\Wsdl2PhpGenerator\PhpType
 */
class Variable
{
	/**
	 * @var string The type
	 */
	private $type;

	/**
	 * @var string The name
	 */
	private $name;

	/**
	 * @var boolean nullable
	 */
	private $nullable;

	/**
	 * @var string The namespace
	 */
	private $namespace;


	/**
	 * Variable constructor.
	 *
	 * @param string $type
	 * @param string $name
	 * @param bool   $nullable
	 * @param string $namespace
	 */
	public function __construct($type, $name, $nullable, $namespace = null)
	{
		$this->type      = $type;
		$this->name      = $name;
		$this->nullable  = $nullable;
		$this->namespace = $namespace;
	}


	/**
	 * @param string $type
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}


	/**
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}


	/**
	 * @param bool $nullable
	 */
	public function setNullable(bool $nullable)
	{
		$this->nullable = $nullable;
	}


	/**
	 * @param string $namespace
	 */
	public function setNamespace(string $namespace)
	{
		$this->namespace = $namespace;
	}


	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @return boolean
	 */
	public function getNullable()
	{
		return $this->nullable;
	}


	/**
	 * @return string|null
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}


	/**
	 * @return boolean
	 */
	public function isArray()
	{
		return substr($this->type, -2, 2) == '[]';
	}
}
