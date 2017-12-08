<?php

namespace Tutu\Wsdl2PhpGenerator\WsdlHandler;

/**
 * Class Struct
 * @package Tutu\Wsdl2PhpGenerator\WsdlHandler
 */
class Struct
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
	 * @var Attribute[]
	 */
	public $attributes = [];
	/**
	 * @var mixed
	 */
	public $xmlNode;
	/**
	 * @var bool
	 */
	public $isArray = false;
	/**
	 * @var string
	 */
	public $wsdlStruct;


	/**
	 * Struct constructor.
	 *
	 * @param string $wsdlStruct
	 */
	public function __construct($wsdlStruct = '')
	{
		if (!empty($wsdlStruct))
		{
			$this->wsdlStruct = $wsdlStruct;
			$this->parseWsdlStruct();
		}
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getBaseType()
	{
		return $this->baseType;
	}


	/**
	 * @param string $baseType
	 */
	public function setBaseType($baseType)
	{
		$this->baseType = $baseType;
	}


	/**
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}


	/**
	 * @param array $attributes
	 */
	public function setAttributes(array $attributes)
	{
		$this->attributes = $attributes;
	}


	/**
	 * @param string $attributeName
	 * @param string $attributeType
	 */
	public function addAttribute($attributeName, $attributeType)
	{
		$this->attributes[$attributeName] = $attributeType;
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
	public function getWsdlStruct(): string
	{
		return $this->wsdlStruct;
	}


	/**
	 * @param string $wsdlStruct
	 */
	public function setWsdlStruct(string $wsdlStruct)
	{
		$this->wsdlStruct = $wsdlStruct;
	}


	/**
	 * Parse wsdl struct string
	 */
	public function parseWsdlStruct()
	{
		$wsdlLines         = $this->getWsdlLines($this->wsdlStruct);
		$firstLineElements = explode(" ", $wsdlLines[0]);
		$this->baseType    = $firstLineElements[0];
		$this->name        = $firstLineElements[1];
		if (substr($this->name, -2, 2) == '[]')
		{
			$this->name    = substr($this->name, 0, -2);
			$this->isArray = true;
		}
		$this->attributes = $this->getWsdlAttributes($wsdlLines);
	}


	/**
	 * Returns the lines of WSDL type.
	 *
	 * @param string $wsdlStruct
	 *
	 * @return string[] The lines of the WSDL type.
	 */
	protected function getWsdlLines($wsdlStruct)
	{
		$newline = (strpos($wsdlStruct, "\r\n") ? "\r\n" : "\n");
		return explode($newline, $wsdlStruct);
	}


	/**
	 * @param $wsdlLines
	 *
	 * @return Attribute[]
	 */
	protected function getWsdlAttributes($wsdlLines)
	{
		$parts = [];
		for ($i = 1; $i < sizeof($wsdlLines) - 1; $i++)
		{
			$isStruct      = false;
			$isArray       = false;
			$wsdlLines[$i] = trim($wsdlLines[$i]);
			list($typeName, $name) = explode(" ", substr($wsdlLines[$i], 0, strlen($wsdlLines[$i]) - 1));
			if (substr($name, -2, 2) == '[]')
			{
				$name    = substr($name, 0, -2);
				$isArray = true;
			}
			$parts[$name] = new Attribute($name, $typeName, $isStruct, $isArray);
		}
		return $parts;
	}
}