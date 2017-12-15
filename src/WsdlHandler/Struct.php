<?php

namespace Tutu\Wsdl2PhpGenerator\WsdlHandler;

use Tutu\Wsdl2PhpGenerator\Base\ClassGenerator;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocComment;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocElementFactory;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpFunction;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpVariable;
use Tutu\Wsdl2PhpGenerator\Validation\Validator;

/**
 * Class Struct
 * @package Tutu\Wsdl2PhpGenerator\WsdlHandler
 */
class Struct implements ClassGenerator
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
	 * @var string
	 */
	public $baseType;
	/**
	 * @var string
	 */
	public $extend;
	/**
	 * @var string
	 */
	public $uses = [];
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
	 * @var string
	 */
	public $mergeWsdlStructs = '';

	/**
	 * @var PhpClass The class used to create the type.
	 */
	public $class;


	/**
	 * Struct constructor.
	 *
	 * @param ConfigInterface $config
	 * @param string          $wsdlStruct
	 */
	public function __construct($config, $wsdlStruct = '')
	{
		$this->config = $config;

		if (!empty($wsdlStruct))
		{
			$this->wsdlStruct       = $wsdlStruct;
			$this->mergeWsdlStructs = $wsdlStruct;
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
	 * @return string
	 */
	public function getExtend(): string
	{
		return $this->extend;
	}


	/**
	 * @param string $extend
	 */
	public function setExtend(string $extend)
	{
		$this->extend = $extend;
	}


	/**
	 * @param string $extend
	 */
	public function addToUses($extend)
	{
		$this->uses[] = $extend;
	}


	/**
	 * @return Attribute[]
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}


	/**
	 * @param Attribute[] $attributes
	 */
	public function setAttributes(array $attributes)
	{
		$this->attributes = $attributes;
	}


	/**
	 * @param string    $attributeName
	 * @param Attribute $attribute
	 */
	public function addAttribute($attributeName, $attribute)
	{
		$this->attributes[$attributeName] = $attribute;
	}


	/**
	 * @return \DOMElement
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
		/** @var Attribute[] $parts */
		$parts = [];
		for ($i = 1; $i < sizeof($wsdlLines) - 1; $i++)
		{ 
			$isArray = false;
			$wsdlLines[$i] = trim($wsdlLines[$i]);
			list($typeName, $name) = explode(" ", substr($wsdlLines[$i], 0, strlen($wsdlLines[$i]) - 1));
			if (substr($name, -2, 2) == '[]')
			{
				$name    = substr($name, 0, -2);
				$isArray = true;
			}
			$parts[$name] = new Attribute($name, $typeName);
			$parts[$name]->setIsArray($isArray);
		}
		return $parts;
	}


	/**
	 * @param string $wsdlStruct
	 */
	public function addMergeWsdlStruct($wsdlStruct = '')
	{
		$this->mergeWsdlStructs .= PHP_EOL . PHP_EOL . $wsdlStruct;
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
	protected function generateClass()
	{
		if ($this->class != null)
		{
			throw new GeneratorException("The class has already been generated");
		}

		$className      = Validator::validateClass($this->name);
		$classNamespace = $this->getNamespace();

		$classBaseType = null;

		if ($this->extend !== null && $this->extend !== $this->getName())
		{
			$classBaseType = $this->extend;
		}
		else if (($this->config->get($this->config::BASE_EXTEND_CLASS) !== false) &&
			!empty($this->config->get($this->config::BASE_EXTEND_CLASS))
		)
		{
			$configExtend = $this->config->get($this->config::BASE_EXTEND_CLASS);
			$classBaseType = Validator::getClassNameFromNS($configExtend);
			$this->uses[]  = ltrim($configExtend, '\\');;
		}

		$this->class = new PhpClass($classNamespace, $className, false, $classBaseType);
		$this->class->setSavePath($this->config->get($this->config::STRUCTS_DIRECTORY));
		if(!empty($this->uses))
		{
			$this->class->addUses($this->uses);
		}

		$constructorComment = new PhpDocComment();
		$constructorComment->setDescription($className . ' constructor.');

		$constructorSource     = '';
		$constructorParameters = [];
		$accessors             = [];

//
//		// Add base type members to constructor parameter list first and call base class constructor
//		$parentMembers = $this->getBaseTypeMembers($this);
//		if (!empty($parentMembers))
//		{
//			foreach ($parentMembers as $member)
//			{
//				$type = Validator::validateType($member->getType());
//				$name = Validator::validateAttribute($member->getName());
//
//				if (!$member->getNullable())
//				{
//					$constructorComment->addParam(PhpDocElementFactory::getParam($type, $name, ''));
//					$constructorParameters[$name] = Validator::validateTypeHint($type);
//				}
//			}
//			$constructorSource .= sprintf(
//					'  parent::__construct(%s);',
//					$this->buildParametersString($constructorParameters, false)
//				) . PHP_EOL;
//		}
//

		// Add member variables
		$attributes = $this->attributes;
		ksort($attributes);
		foreach ($attributes as $attribute)
		{
			$name     = Validator::validateAttribute($attribute->getName());
			$typeHint = $this->buildAttributeTypeHint($attribute);

			$comment = new PhpDocComment();
			$comment->setVar(PhpDocElementFactory::getVar($typeHint, $name, ''));
			$var = new PhpVariable('protected', $name, $attribute->getDefaultValue(), $comment);
			$this->class->addVariable($var);

			$constructorComment->addParam(PhpDocElementFactory::getParam($typeHint, $name, ''));
			$constructorParameters[$name] = $typeHint;
			$constructorSource            .= '  $this->' . $name . ' = $' . $name . ';' . PHP_EOL;


			if ($this->config->get($this->config::GETTERS_ENABLED) == true)
			{
				$getterMethodName = $this->getGetterMethodName($name, array_keys($accessors));
				$getterComment    = new PhpDocComment('Get ' . $name . ' value');
				$getterComment->setReturn(PhpDocElementFactory::getReturn($typeHint, ''));
				$getterCode = '  return $this->' . $name . ';' . PHP_EOL;
				$getter     = new PhpFunction('public', $getterMethodName, '', $getterCode, $getterComment);

				$accessors[$getterMethodName] = $getter;
			}

			if ($this->config->get($this->config::SETTERS_ENABLED))
			{
				$setterMethodName = $this->getSetterMethodName($name, array_keys($accessors));
				$setterComment    = new PhpDocComment('Set ' . $name . ' value');
				$setterComment->addParam(PhpDocElementFactory::getParam($typeHint, $name, ''));
				$setterComment->setReturn(PhpDocElementFactory::getReturn($this->getClassName(), ''));
				$setterCode = '  $this->' . $name . ' = $' . $name . ';' . PHP_EOL;
				$setterCode .= '  return $this;' . PHP_EOL;
				$setter     = new PhpFunction(
					'public',
					$setterMethodName,
					$this->buildParametersString([$name => $typeHint]),
					$setterCode,
					$setterComment
				);

				$accessors[$setterMethodName] = $setter;
			}

			if ($attribute->isArray())
			{
				$addToMethodName = $this->getAddToMethodName($name, array_keys($accessors));
				$addToParamTypeHint  = str_replace('[]','', $typeHint);
				$addToComment    = new PhpDocComment();
				$addToComment->addParam(PhpDocElementFactory::getParam($addToParamTypeHint, $name, ''));

				$addToCode = '  $this->' . $name . '[] = $' . $name . ';' . PHP_EOL;
				$addTo     = new PhpFunction(
					'public',
					$addToMethodName,
					$this->buildParametersString([$name => $addToParamTypeHint]),
					$addToCode,
					$addToComment
				);

				$accessors[$addToMethodName] = $addTo;
			}
		}

		$constructor = new PhpFunction(
			'public',
			'__construct',
			$this->buildParametersString(
				$constructorParameters,
				false,
				$this->config->get($this->config::CONSTRUCTOR_NULL_PARAMS)
			),
			$constructorSource,
			$constructorComment
		);
		$this->class->addFunction($constructor);

		foreach ($accessors as $accessor)
		{
			$this->class->addFunction($accessor);
		}
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
			$namespace .= (!empty($this->config->get($this->config::STRUCTS_DIRECTORY)))
				? ucfirst($this->config->get($this->config::STRUCTS_DIRECTORY))
				: '';
			return $namespace;
		}
		return null;
	}

	/**
	 * Implements the generation of namespace
	 *
	 * @return string
	 */
	public function getClassNamespace()
	{
		return ($this->getNamespace()) ? $this->getNamespace() . '\\' . $this->getClassName() : $this->getClassName();
	}


	/**
	 * Get getter method name
	 *
	 * @param $attributeName
	 * @param $methodsList
	 *
	 * @return string
	 * @throws GeneratorException
	 */
	public function getGetterMethodName($attributeName, $methodsList)
	{
		$name = 'get' . ucfirst($attributeName);
		if (!in_array($name, $methodsList))
		{
			return $name;
		}
		else
		{
			for ($k = 1; $k <= 9; $k++)
			{
				if (!in_array($name . $k, $methodsList))
				{
					return $name . $k;
				}
			}
		}
		throw new GeneratorException('Could not create a getter method name! Too many similar attributes!');
	}


	/**
	 * Get getter method name
	 *
	 * @param $attributeName
	 * @param $methodsList
	 *
	 * @return string
	 * @throws GeneratorException
	 */
	public function getSetterMethodName($attributeName, $methodsList)
	{
		$name = 'set' . ucfirst($attributeName);
		if (!in_array($name, $methodsList))
		{
			return $name;
		}
		else
		{
			for ($k = 1; $k <= 9; $k++)
			{
				if (!in_array($name . $k, $methodsList))
				{
					return $name . $k;
				}
			}
		}
		throw new GeneratorException('Could not create a setter method name! Too many similar attributes!');
	}


	/**
	 * Get addTo method name
	 *
	 * @param $attributeName
	 * @param $methodsList
	 *
	 * @return string
	 * @throws GeneratorException
	 */
	public function getAddToMethodName($attributeName, $methodsList)
	{
		$name = 'addTo' . ucfirst($attributeName);
		if (!in_array($name, $methodsList))
		{
			return $name;
		}
		else
		{
			for ($k = 1; $k <= 9; $k++)
			{
				if (!in_array($name . $k, $methodsList))
				{
					return $name . $k;
				}
			}
		}
		throw new GeneratorException('Could not create a addTo method name! Too many similar attributes!');
	}


	/**
	 * @param Attribute $attribute
	 *
	 * @return string
	 */
	public function buildAttributeTypeHint(Attribute $attribute)
	{
		$typeHints = $attribute->getTypeHint();
		foreach($typeHints as $key => $typeHint)
		{
			if($attribute->isArray())
			{
				$typeHints[$key] .= '[]';
			}
		}
		return implode('|', $typeHints);
	}


	/**
	 * Generate a string representing the parameters for a function e.g. "type1 $param1, type2 $param2, $param3"
	 *
	 * @param array $parameters  A map of parameters. Keys are parameter names and values are parameter types.
	 *                           Parameter types may be empty. In that case they are not used.
	 * @param bool  $includeType Whether to include the parameters types in the string
	 * @param bool  $defaultNull Whether to set the default value of parameters to null.
	 *
	 * @return string The parameter string.
	 */
	public function buildParametersString(array $parameters, $includeType = false, $defaultNull = false)
	{
		$parameterStrings = [];
		foreach ($parameters as $name => $type)
		{
			$parameterString = '$' . $name;
			if (!empty($type) && $includeType)
			{
				$parameterString = $type . ' ' . $parameterString;
			}
			if ($defaultNull)
			{
				$parameterString .= ' = null';
			}
			$parameterStrings[] = $parameterString;
		}

		return implode(', ', $parameterStrings);
	}

	public function getExtensionBaseType()
	{
		$base = null;
		if($this->getXmlNode() !== null)
		{
			$extensions = $this->getXmlNode()->getElementsByTagName('extension');
			if ($extensions->length > 0)
			{
				$baseType = $extensions->item(0)->getAttribute('base');
				$parts = explode(':', $baseType);
				$base = end($parts);
			}
		}
		return $base;
	}
}