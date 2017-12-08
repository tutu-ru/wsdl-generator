<?php

namespace Tutu\Wsdl2PhpGenerator\WsdlHandler;

/**
 * Class Attribute
 * @package Tutu\Wsdl2PhpGenerator\WsdlHandler
 */
class Attribute
{
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 */
	public $baseType;
	/**
	 * @var bool
	 */
	public $isStruct = false;
	/**
	 * @var bool
	 */
	public $isArray = false;
	/**
	 * @var bool
	 */
	public $isNillable = false;


	/**
	 * Attribute constructor.
	 *
	 * @param string $name
	 * @param string $baseType
	 * @param bool   $isStruct
	 * @param bool   $isArray
	 * @param bool   $isNillable
	 */
	public function __construct($name, $baseType, $isStruct = false, $isArray = false, $isNillable = false)
	{
		$this->name       = $name;
		$this->baseType   = $baseType;
		$this->isStruct   = $isStruct;
		$this->isArray    = $isArray;
		$this->isNillable = $isNillable;
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
	 * @return string
	 */
	public function getBaseType(): string
	{
		return $this->baseType;
	}


	/**
	 * @param string $baseType
	 */
	public function setBaseType(string $baseType)
	{
		$this->baseType = $baseType;
	}


	/**
	 * @return bool
	 */
	public function isStruct(): bool
	{
		return $this->isStruct;
	}


	/**
	 * @param bool $isStruct
	 */
	public function setIsStruct(bool $isStruct)
	{
		$this->isStruct = $isStruct;
	}


	/**
	 * @return bool
	 */
	public function isArray(): bool
	{
		return $this->isArray;
	}


	/**
	 * @param bool $isArray
	 */
	public function setIsArray(bool $isArray)
	{
		$this->isArray = $isArray;
	}


	/**
	 * @return bool
	 */
	public function isNillable(): bool
	{
		return $this->isNillable;
	}


	/**
	 * @param bool $isNillable
	 */
	public function setIsNillable(bool $isNillable)
	{
		$this->isNillable = $isNillable;
	}
}