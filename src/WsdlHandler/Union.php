<?php

namespace Tutu\Wsdl2PhpGenerator\WsdlHandler;

/**
 * Class Union
 * @package Tutu\Wsdl2PhpGenerator\WsdlHandler
 */
class Union
{
	/**
	 * @var  string
	 */
	public $name;
	/**
	 * @var  string
	 */
	public $baseType;
	/**
	 * @var  string[]
	 */
	public $types;
	/**
	 * @var  string
	 */
	public $wsdlUnion;


	/**
	 * Pattern constructor.
	 *
	 * @param $wsdlUnion
	 */
	public function __construct($wsdlUnion = '')
	{
		if (!empty($wsdlUnion))
		{
			$this->wsdlUnion = $wsdlUnion;
			$this->parseWsdlUnion();
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
	 * @return \string[]
	 */
	public function getTypes(): array
	{
		return $this->types;
	}


	/**
	 * @param \string[] $types
	 */
	public function setTypes(array $types)
	{
		$this->types = $types;
	}


	/**
	 * @param string $type
	 */
	public function addType($type)
	{
		$this->types[] = $type;
	}

	/**
	 * @return string
	 */
	public function getWsdlUnion(): string
	{
		return $this->wsdlUnion;
	}


	/**
	 * @param string $wsdlUnion
	 */
	public function setWsdlUnion(string $wsdlUnion)
	{
		$this->wsdlUnion = $wsdlUnion;
	}


	/**
	 * Parse wsdl union string
	 * Ex: union FlightNumber {,_flightNumberPattern,_flightNumberLiterals}
	 */
	public function parseWsdlUnion()
	{
		$wsdlString = trim($this->wsdlUnion);
		list($this->baseType, $this->name, $types) = explode(' ', $wsdlString);
		$types       = ltrim(str_replace(['{', '}'], '', $types), ',');
		$this->types = explode(',', $types);
	}

}