<?php

namespace Tutu\Wsdl2PhpGenerator\PhpType;

use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocComment;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocElementFactory;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpFunction;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpVariable;
use Tutu\Wsdl2PhpGenerator\Validation\Validator;

/**
 * Class ComplexType
 *
 * @package Tutu\Wsdl2PhpGenerator\PhpType
 */
class ComplexType extends Type
{
	/**
	 * Base type that the type extends
	 *
	 * @var ComplexType
	 */
	protected $baseType;

	/**
	 * The members in the type
	 *
	 * @var Variable[]
	 */
	protected $members;

	/**
	 * @var
	 */
	protected $abstract;


	/**
	 * Construct the object
	 *
	 * @param ConfigInterface $config The configuration
	 * @param string          $name   The identifier for the class
	 */
	public function __construct(ConfigInterface $config, $name)
	{
		parent::__construct($config, $name, null);
		$this->members  = [];
		$this->baseType = null;
		$this->abstract = false;
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

		$classBaseType = $this->getBaseTypeClass();

		$this->class = new PhpClass(
			$this->getNamespace(),
			$this->phpIdentifier,
			false,
			$classBaseType,
			null,
			false,
			$this->abstract
		);

		$constructorComment = new PhpDocComment();
		$constructorComment->setDescription($this->phpIdentifier . ' constructor.');

		$constructorSource     = '';
		$constructorParameters = [];
		$accessors             = [];

		if ($this instanceof ArrayType)
		{
			$this->class->setSavePath($this->config->get($this->config::ARRAYS_DIRECTORY));
		}
		else
		{
			$this->class->setSavePath($this->config->get($this->config::STRUCTS_DIRECTORY));
		}


		// Add base type members to constructor parameter list first and call base class constructor
		$parentMembers = $this->getBaseTypeMembers($this);
		if (!empty($parentMembers))
		{
			foreach ($parentMembers as $member)
			{
				$type = Validator::validateType($member->getType());
				$name = Validator::validateAttribute($member->getName());

				if (!$member->getNullable())
				{
					$constructorComment->addParam(PhpDocElementFactory::getParam($type, $name, ''));
					$constructorParameters[$name] = Validator::validateTypeHint($type);
				}
			}
			$constructorSource .= sprintf(
					'  parent::__construct(%s);',
					$this->buildParametersString($constructorParameters, false)
				) . PHP_EOL;
		}

		$knownTypes    = $this->getGenerator()->getTypesNamespaces();
		$fallbackTypes = $this->getGenerator()->getTypesMappings();
		// Add member variables
		foreach ($this->members as $member)
		{
			$type     = Validator::validateType($member->getType());
			$name     = Validator::validateAttribute($member->getName());
			$typeHint = Validator::validateTypeHint($type);

			$cleanType = $type;
			$isArray   = false;
			if (substr($cleanType, -2) == "[]")
			{
				$cleanType = substr($type, 0, -2);
				$isArray   = true;
			}
			$cleanType = $this->getFallbackMemberType($cleanType, $fallbackTypes);
			$cleanType = $this->getKnownMemberType($cleanType, $knownTypes);
			$type      = $cleanType . (($isArray) ? '[]' : '');

			$comment = new PhpDocComment();
			$comment->setVar(PhpDocElementFactory::getVar($type, $name, ''));
			$var = new PhpVariable('protected', $name, 'null', $comment);
			$this->class->addVariable($var);

			$constructorComment->addParam(PhpDocElementFactory::getParam($type, $name, ''));
			$constructorParameters[$name] = $typeHint;
			$constructorSource            .= '  $this->' . $name . ' = $' . $name . ';' . PHP_EOL;


			if ($this->config->get($this->config::GETTERS_ENABLED) == true)
			{
				$getterMethodName = $this->getGetterMethodName($name, array_keys($accessors));
				$getterComment    = new PhpDocComment('Get ' . $name . ' value');
				$getterComment->setReturn(PhpDocElementFactory::getReturn($type, ''));
				$getterCode = '  return $this->' . $name . ';' . PHP_EOL;
				$getter     = new PhpFunction('public', $getterMethodName, '', $getterCode, $getterComment);

				$accessors[$getterMethodName] = $getter;
			}

			if ($this->config->get($this->config::SETTERS_ENABLED))
			{
				$setterMethodName = $this->getSetterMethodName($name, array_keys($accessors));
				$setterComment    = new PhpDocComment('Set ' . $name . ' value');
				$setterComment->addParam(PhpDocElementFactory::getParam($type, $name, ''));
				$setterComment->setReturn(PhpDocElementFactory::getReturn($this->phpNamespaceIdentifier, ''));
				$setterCode = '  $this->' . $name . ' = $' . $name . ';' . PHP_EOL;
				$setterCode .= '  return $this;' . PHP_EOL;
				$setter     = new PhpFunction(
					'public',
					$setterMethodName,
					$this->buildParametersString(
						[$name => $typeHint],
						true,
						// If the type of a member is nullable we should allow passing null to the setter. If the type
						// of the member is a class and not a primitive this is only possible if setter parameter has
						// a default null value. We can detect whether the type is a class by checking the type hint.
						$member->getNullable() && !empty($typeHint)
					),
					$setterCode,
					$setterComment
				);

				$accessors[$setterMethodName] = $setter;
			}

			if (substr($type, -2, 2) == '[]')
			{
				$addToMethodName = $this->getAddToMethodName($name, array_keys($accessors));
				$addToParamType  = substr($type, 0, -2);
				$addToComment    = new PhpDocComment();
				$addToComment->addParam(PhpDocElementFactory::getParam($addToParamType, $name, ''));

				$addToCode = '  $this->' . $name . '[] = $' . $name . ';' . PHP_EOL;
				$addTo     = new PhpFunction(
					'public',
					$addToMethodName,
					$this->buildParametersString(
						[$name => $addToParamType],
						true,
						// If the type of a member is nullable we should allow passing null to the setter. If the type
						// of the member is a class and not a primitive this is only possible if setter parameter has
						// a default null value. We can detect whether the type is a class by checking the type hint.
						$member->getNullable() && !empty($typeHint)
					),
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
				true,
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
	 * Determine parent class
	 *
	 * @return string|null
	 *   Returns a string containing the PHP identifier for the parent class
	 *   or null if there is no applicable parent class.
	 */
	public function getBaseTypeClass()
	{
		// If we have a base type which is different than the current class then extend that.
		// It is actually possible to have different classes with the same name as PHP SoapClient has a poor
		// understanding of namespaces. Two types with the same name but in different namespaces will have the same
		// identifier.
		if ($this->baseType !== null && $this->baseType !== $this)
		{
			return $this->baseType->getPhpIdentifier();
		}
		else if (($this->config->get($this->config::BASE_EXTEND_CLASS) !== false) &&
			!empty($this->config->get($this->config::BASE_EXTEND_CLASS))
		)
		{
			return $this->config->get($this->config::BASE_EXTEND_CLASS);
		}

		return null;
	}


	/**
	 * Returns the base type for the type if any.
	 *
	 * @return ComplexType|null
	 *   The base type or null if the type has no base type.
	 */
	public function getBaseType()
	{
		return $this->baseType;
	}


	/**
	 * Set the base type
	 *
	 * @param ComplexType $type
	 */
	public function setBaseType(ComplexType $type)
	{
		$this->baseType = $type;
	}


	/**
	 * @return bool
	 */
	public function getAbstract()
	{
		return $this->abstract;
	}


	/**
	 * @param bool $abstract
	 */
	public function setAbstract($abstract)
	{
		$this->abstract = $abstract;
	}


	/**
	 * Adds the member. Overwrites members with same name
	 *
	 * @param string $type
	 * @param string $name
	 * @param bool   $nullable
	 * @param string $namespace
	 */
	public function addMember($type, $name, $nullable, $namespace = null)
	{
		if (array_key_exists($name, $this->members))
		{
			var_dump('--- -->' . $name);
		}
		$this->members[$name] = new Variable($type, $name, $nullable, $namespace);
	}


	/**
	 * Get type member list
	 *
	 * @return Variable[]
	 */
	public function getMembers()
	{
		return $this->members;
	}


	/**
	 * Get type member list
	 *
	 * @param Variable[] $members
	 */
	public function setMembers($members)
	{
		$this->members = $members;
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
	protected function buildParametersString(array $parameters, $includeType = true, $defaultNull = false)
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


	/**
	 * Get members from base types all the way through the type hierarchy.
	 *
	 * @param ComplexType $type The type to retrieve base type members from.
	 *
	 * @return Variable[] Member variables from all base types.
	 */
	protected function getBaseTypeMembers(ComplexType $type)
	{
		if (empty($type->baseType) || ($type === $type->baseType))
		{
			return [];
		}

		return array_merge($this->getBaseTypeMembers($type->baseType), $type->baseType->getMembers());
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
	protected function getGetterMethodName($attributeName, $methodsList)
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
	protected function getSetterMethodName($attributeName, $methodsList)
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
	protected function getAddToMethodName($attributeName, $methodsList)
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
	 * @param string $type
	 * @param array  $fallbackTypes
	 *
	 * @return string
	 */
	protected function getFallbackMemberType($type, $fallbackTypes)
	{
		$returnType = $type;
		if (array_key_exists($type, $fallbackTypes))
		{
			$returnType = $this->getFallbackMemberType(Validator::validateType($fallbackTypes[$type]), $fallbackTypes);
		}

		return $returnType;
	}


	/**
	 * @param string $type
	 * @param array  $knownTypes
	 *
	 * @return string
	 */
	protected function getKnownMemberType($type, $knownTypes)
	{
		$returnType = $type;
		if (array_key_exists($type, $knownTypes))
		{
			$returnType = '\\' . $knownTypes[$type] . '\\' . $type;
		}

		return $returnType;
	}
}
