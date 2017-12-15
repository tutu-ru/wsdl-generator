<?php

namespace Tutu\Wsdl2PhpGenerator\Xml;

use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;

use Tutu\Wsdl2PhpGenerator\Validation\Validator;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Enum;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Pattern;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Struct;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Union;

/**
 * Class WsdlDocument
 *
 * @package Tutu\Wsdl2PhpGenerator\Xml
 */
class WsdlDocument extends SchemaDocument
{

	/**
	 * An instance of a PHP SOAP client based on the WSDL file
	 *
	 * @var \SoapClient
	 */
	protected $soapClient;

	/**
	 * The configuration.
	 *
	 * @var ConfigInterface
	 */
	protected $config;

	public $types = [];
	/**
	 * @var Struct[]
	 */
	public $structures = [];
	public $structuresNames = [];
	/**
	 * @var Pattern[]
	 */
	public $patterns = [];
	public $patternsNames = [];
	/**
	 * @var Union[]
	 */
	public $unions = [];
	/**
	 * @var Enum[]
	 */
	public $enums = [];


	/**
	 * WsdlDocument constructor.
	 *
	 * @param ConfigInterface $config
	 * @param string          $wsdlUrl
	 *
	 * @throws GeneratorException
	 */
	public function __construct(ConfigInterface $config, $wsdlUrl)
	{
		$this->config = $config;

		// Never use PHP WSDL cache to when creating the SoapClient instance used to extract information.
		// Otherwise we risk generating code for a WSDL that is no longer valid.
		$options = array_merge(
			$this->config->get($this->config::SOAP_CLIENT_OPTIONS), 
			['cache_wsdl' => WSDL_CACHE_NONE]
		);

		try
		{
			$soapClientClass  = new \ReflectionClass($this->config->get($this->config::SOAP_CLIENT_CLASS));
			$this->soapClient = $soapClientClass->newInstance($wsdlUrl, $options);
			parent::__construct($config, $wsdlUrl);
		}
		catch (\SoapFault $e)
		{
			throw new GeneratorException('Unable to load WSDL: ' . $e->getMessage(), $e->getCode(), $e);
		}
	}


	/**
	 * Returns representations of all the dataTypes used when working with the SOAP service.
	 *
	 * @return array
	 */
	public function getTypes()
	{
		$typeStrings = $this->soapClient->__getTypes();
		foreach ($typeStrings as $typeString)
		{
			//file_put_contents('struca.txt', $typeString . PHP_EOL,  FILE_APPEND);
			// We have 3 type of data in soap types
			// 1. Struct - Enum or Complex structure type 
			// 2. Union - an union of types
			// 3. Pattern - is a mapping type

			// collect all wsdl types
			if(substr($typeString, 0, strlen('struct')) == 'struct')
			{
				$struct = new Struct($this->config, $typeString);
				$this->structures[] = $struct;
				$this->structuresNames[] = $struct->getName();
			} 
			else if(substr($typeString, 0, strlen('union')) == 'union')
			{
				$union = new Union($typeString);
				$this->unions[$union->getName()] = $union;
			}
			else 
			{
				$pattern = new Pattern($typeString);
				$this->patterns[$pattern->getName()] = $pattern;
				$this->patternsNames[$pattern->getName()] = $pattern->getBaseType();
			}
		}
		$this->findEnums();
		$this->findStructuresNodes();
		$this->normalizeStructuresAttributes();
		$this->mergeStructures();
		$this->completeStructures();

		return [
			'structures' => $this->structures,
			'enums' => $this->enums,
			//'patterns' => $this->patterns,
			//'unions' => $this->unions,
		];
	}


	/**
	 * Get fallback pattern type
	 * 
	 * @param string $type
	 *
	 * @return string
	 */
	public function getPatternBaseType($type)
	{
		if(array_key_exists($type, $this->patternsNames))
		{
			return $this->getPatternBaseType($this->patternsNames[$type]);
		}
		return $type;
	}

	public function findEnums()
	{
		foreach ($this->patterns as $pattern)
		{
			$name = $pattern->getName();
			$elements = $this->getElementsList($name);
			if(count($elements) > 0)
			{
				foreach ($elements as $element)
				{
					foreach ($element['el'] as $e)
					{
						/** @var \DOMElement $e */
						$enums = [];
						foreach ($e->getElementsByTagName('enumeration') as $enum)
						{
							/** @var \DOMElement $enum */
							$enums[] = $enum->getAttribute('value');
						};
						if(count($enums) > 0)
						{
							$this->enums[$name] = new Enum($this->config, $name, $enums, $e);
						}
					}
				}
			}
		}
	}

	/**
	 * Normalize structure attributes
	 */
	public function normalizeStructuresAttributes()
	{
		foreach($this->structures as $sKey => $structure)
		{
			$attributes = $structure->getAttributes();
			foreach($attributes as $aKey => $attribute)
			{
				$type = $this->getPatternBaseType($attribute->getBaseType());
				if(array_key_exists($type, $this->unions))
				{
					$type = 'string';
				}
				$type = Validator::validateType($type);
				$attributes[$aKey]->setBaseType($type);
			}
			$this->structures[$sKey]->setAttributes($attributes);
		}
	}

	public function findStructuresNodes()
	{
		foreach ($this->structures as $sKey => $structure)
		{
			$elements = $this->getElementsList($structure->getName());
			if(count($elements) == 0)
			{
				throw new \Exception('Not found type '. $structure->getName());
			}
			//var_dump($elements); exit;
			$elem = null;
			$xPath = null;
			foreach ($elements as $element)
			{
				/**
				 * @var $xpath \DOMXPath 
				 */
				$xpath = $element['x']->getXPath();
				foreach($element['el'] as $e)
				{
					$found = true;

					$attrList = $structure->getAttributes();
					foreach ($attrList as $aKey => $attribute)
					{
						$attr = $attribute->getName();
						if (($attr != '_') && ($attr != 'any'))
						{
							$z = $xpath->query(
								'//s:element[@name="' . $attr . '"]|//s:attribute[@name="' . $attr . '"]',
								$e
							);
							if ($z->length <= 0)
							{
								$found = false;
								break;
							}
//							else if($z->length > 1) 
//							{
//								var_dump($structure->getName() . ' --- ' . $attribute->getName() . ' -- '. $z->length); 
//							}
//							$attrList[] = $attribute->getName();
						}
					}
					if ($found)
					{
						$elem = $e;
//						foreach ($structure->getAttributes() as $attribute)
//						{
//							$attr = $attribute->getName();
//							if (($attr != '_') && ($attr != 'any'))
//							{
//								$z = $xpath->query(
//									'//s:element[@name="' . $attr . '"]|//s:attribute[@name="' . $attr . '"]',
//									$e
//								);
//								if ($z->length <= 0)
//								{
//									$found = false;
//									break;
//								}
//								$attrList[] = $attribute->getName();
//							}
//						}
						$xPath = $xpath;
						break;
					}
				}
				if($found)
				{
					break;
				}
			}
			if($elem == null)
			{
				/** @var \DOMNodeList $e */
				$e = $elements[0]['el'];
				$elem = $e->item(0);
				//$xPath->query()
			}

			/** @var \DOMElement $elem */
			$arr = false;
			$maxOccurs = $elem->getAttribute('maxOccurs');
			if(($maxOccurs > 1) || ($maxOccurs === 'unbounded'))
			{
				$arr = true;
			}
			$this->structures[$sKey]->setIsArray($arr);
			$this->structures[$sKey]->setXmlNode($elem);
		}
	}

	public function mergeStructures()
	{
		/** @var Struct[] $structs */
		$structs = [];
		foreach ($this->structures as $structure)
		{
			if(!array_key_exists($structure->getName(), $structs))
			{
				$structs[$structure->getName()] = $structure;
			}
			else
			{
				$structs[$structure->getName()]->setIsArray(
					(
						($structs[$structure->getName()]->isArray() || $structure->isArray())
							? true 
							: false
					)
				);
				$structs[$structure->getName()]->addMergeWsdlStruct($structure->getWsdlStruct());
				$attributes = $structs[$structure->getName()]->getAttributes();
				foreach ($structure->getAttributes() as $attribute)
				{
					if(!array_key_exists($attribute->getName(), $attributes))
					{
						$structs[$structure->getName()]->addAttribute($attribute->getName(), $attribute);
					}
					else
					{
						if(
							($attribute->getOriginalType() ==  $attributes[$attribute->getName()]->getOriginalType())
							||
							($attribute->getBaseType() ==  $attributes[$attribute->getName()]->getBaseType())
						)
						{
							continue;
						}
						else
						{
							if($attributes[$attribute->getName()]->isMixed())
							{
								$attributes[$attribute->getName()]->addToMixedTypes($attribute->getBaseType());
							}
							else
							{
								$attributes[$attribute->getName()]->setIsMixed(true);
								$attributes[$attribute->getName()]->addToMixedTypes($attributes[$attribute->getName()]->getBaseType());
								$attributes[$attribute->getName()]->addToMixedTypes($attribute->getBaseType());
								if($attributes[$attribute->getName()]->isArray() || $attribute->isArray())
								{
									$attributes[$attribute->getName()]->setIsArray(true);
								}
							}
						}
					}
				}
			}
		}
		$this->structures = $structs;
	}

	public function completeStructures()
	{
		foreach ($this->structures as $sKey => $structure)
		{
			// setup base extends type
			$baseType =  $structure->getExtensionBaseType();
			if(($baseType != null) && array_key_exists($baseType, $this->structures))
			{
				$this->structures[$sKey]->setExtend($this->structures[$baseType]->getClassName());
				$this->structures[$sKey]->addToUses($this->structures[$baseType]->getClassNamespace());
			}
			// setup attributes hints
			$attributes = $structure->getAttributes();
			foreach ($attributes as $aKey => $attribute)
			{
				$hints = [];
				if($attribute->isMixed())
				{
					$types = $attribute->getMixedTypes();
					foreach ($types as $type)
					{
						if(array_key_exists($type, $this->structures))
						{
							$hints[] = '\\' . $this->structures[$type]->getClassNamespace();
							if($this->structures[$type]->isArray())
							{
								$attributes[$aKey]->setIsArray(true);
							}
						}
						else
						{
							$hints[] = $type;
						}
					}
				} else {
					$type = $attribute->getBaseType();
					if(array_key_exists($type, $this->structures))
					{
						$hints[] = '\\' . $this->structures[$type]->getClassNamespace();
						if($this->structures[$type]->isArray())
						{
							$attributes[$aKey]->setIsArray(true);
						}
					}
					else
					{
						$hints[] = $type;
					}
				}
				$attributes[$aKey]->setTypeHint($hints);
			}
			$this->structures[$sKey]->setAttributes($attributes);
		}
	}
	
	/**
	 * Returns an array of values that the type may have if the type is an enumeration.
	 *
	 * @return string[] The valid enumeration values.
	 */
	public function getEnumerations()
	{
		$enums = [];
		foreach ($this->element->getElementsByTagName('enumeration') as $enum)
		{
			/** @var \DOMElement $enum */
			$enums[] = $enum->getAttribute('value');
		};

		return $enums;
	}

	/**
	 * Returns a representation of the service described by the WSDL file.
	 *
	 * @return ServiceNode The service described by the WSDL.
	 */
	public function getService()
	{
		$serviceNodes = $this->element->getElementsByTagName('service');
		if ($serviceNodes->length > 0)
		{
			return new ServiceNode($this->document, $serviceNodes->item(0));
		}
		return null;
	}


	/**
	 * Returns representations of all the operations exposed by the service.
	 *
	 * @return OperationNode[] The operations exposed by the service.
	 */
	public function getOperations()
	{
		$functions = [];
		foreach ($this->soapClient->__getFunctions() as $functionString)
		{
			$function      = new OperationNode($functionString);
			$functionNodes = $this->xpath('//wsdl:operation[@name=%s]', $function->getName());
			if ($functionNodes->length > 0)
			{
				$function->setElement($this->document, $functionNodes->item(0));
				$functions[] = $function;
			}
		}
		return $functions;
	}


	/**
	 * @param \DOMElement $e
	 */
	public function showNode($e)
	{
		$temp_dom = new \DOMDocument();
		/*foreach($elems as $e)*/ $temp_dom->appendChild($temp_dom->importNode($e, true));
		var_dump($temp_dom->saveHTML());
	}
}
