<?php

namespace Tutu\Wsdl2PhpGenerator\WsdlHandler;

/**
 * Class Pattern
 * @package Tutu\Wsdl2PhpGenerator\WsdlHandler
 */
class Pattern
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
	public $isArray = false;
	/**
	 * @var string
	 */
	public $wsdlPattern;


	/**
	 * Pattern constructor.
	 *
	 * @param string $wsdlPattern
	 */
	public function __construct($wsdlPattern = '') 
	{
		if(!empty($wsdlPattern))
		{
			$this->wsdlPattern = $wsdlPattern;
			$this->parseWsdlPattern();
		}
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
	 * @return string
	 */
	public function getWsdlPattern(): string
	{
		return $this->wsdlPattern;
	}


	/**
	 * @param string $wsdlPattern
	 */
	public function setWsdlPattern(string $wsdlPattern)
	{
		$this->wsdlPattern = $wsdlPattern;
	}


	/**
	 * Parse wsdl pattern string
	 * Ex: string AirlineCode
	 * Ex: PassengerType Passenger[]
	 */
	public function parseWsdlPattern()
	{
		$wsdlString = trim($this->wsdlPattern);
		list($this->baseType, $this->name) = explode(' ', $wsdlString);
		if (substr($this->name, -2, 2) == '[]')
		{
			$this->name    = substr($this->name, 0, -2);
			$this->isArray = true;
		}
	}
}