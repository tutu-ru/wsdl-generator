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
	 * @var mixed
	 */
	public $defaultValue;
	/**
	 * @var mixed
	 */
	public $typeHint = [];

	/**
	 * @var string
	 */
	public $baseType;
	/**
	 * @var string
	 */
	public $originalType;
	/**
	 * @var string[]
	 */
	public $mixedTypes = [];
	/**
	 * @var bool
	 */
	public $isStruct = false;
	/**
	 * @var bool
	 */
	public $isMixed = false;
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
	 * @param mixed  $defaultValue
	 */
	public function __construct($name, $baseType, $defaultValue = null)
	{
		$this->name         = $name;
		$this->defaultValue = $defaultValue;
		$this->baseType     = $baseType;
		$this->originalType = $baseType;
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
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}


	/**
	 * @param mixed $defaultValue
	 */
	public function setDefaultValue($defaultValue)
	{
		$this->defaultValue = $defaultValue;
	}


	/**
	 * @return array
	 */
	public function getTypeHint(): array 
	{
		return $this->typeHint;
	}


	/**
	 * @param array $typeHint
	 */
	public function setTypeHint(array $typeHint)
	{
		$this->typeHint = $typeHint;
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
	 * @return string
	 */
	public function getOriginalType(): string
	{
		return $this->originalType;
	}


	/**
	 * @param string $originalType
	 */
	public function setOriginalType(string $originalType)
	{
		$this->originalType = $originalType;
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


	/**
	 * @return bool
	 */
	public function isMixed(): bool
	{
		return $this->isMixed;
	}


	/**
	 * @param bool $isMixed
	 */
	public function setIsMixed(bool $isMixed)
	{
		$this->isMixed = $isMixed;
	}


	/**
	 * @return \string[]
	 */
	public function getMixedTypes(): array
	{
		return $this->mixedTypes;
	}


	/**
	 * @param \string[] $mixedTypes
	 */
	public function setMixedTypes(array $mixedTypes)
	{
		$this->mixedTypes = $mixedTypes;
	}

	/**
	 * @param $type
	 */
	public function addToMixedTypes($type)
	{
		if(!in_array($type, $this->mixedTypes))
		{
			$this->mixedTypes[] = $type;
		}
	}
}