<?php

namespace Tutu\Wsdl2PhpGenerator\Service;

use Tutu\Wsdl2PhpGenerator\Base\ClassGenerator;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocComment;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocElementFactory;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpFunction;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpVariable;
use Tutu\Wsdl2PhpGenerator\PhpType\ComplexType;
use Tutu\Wsdl2PhpGenerator\PhpType\Type;
use Tutu\Wsdl2PhpGenerator\Validation\Validator;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Struct;

/**
 * Class Service
 *
 * @package Tutu\Wsdl2PhpGenerator\Service
 */
class Service implements ClassGenerator
{

	/**
	 * @var ConfigInterface
	 */
	private $config;

	/**
	 * @var string The name of the service
	 */
	private $name;

	/**
	 * @var PhpClass The class used to create the service.
	 */
	private $class;

	/**
	 * @var Operation[] An array containing the operations of the service
	 */
	private $operations;

	/**
	 * @var string The description of the service used as description in the phpdoc of the class
	 */
	private $description;

	/**
	 * @var Struct[] An array of Types
	 */
	private $types;


	/**
	 * @param ConfigInterface $config      Configuration
	 * @param string          $name        The name of the service
	 * @param array           $types       The types the service knows about
	 * @param string          $description The description of the service
	 */
	public function __construct(ConfigInterface $config, $name, array $types, $description)
	{
		$this->config      = $config;
		$this->name        = $name;
		$this->description = $description;
		$this->operations  = [];
		$this->types       = $types;
	}


	/**
	 * @return PhpClass Returns the class, generates it if not done
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
	 * Returns an operation provided by the service based on its name.
	 *
	 * @param string $operationName The name of the operation.
	 *
	 * @return Operation|null The operation or null if it does not exist.
	 */
	public function getOperation($operationName)
	{
		return isset($this->operations[$operationName]) ? $this->operations[$operationName] : null;
	}


	/**
	 * Returns the description of the service.
	 *
	 * @return string The service description.
	 */
	public function getDescription()
	{
		return $this->description;
	}


	/**
	 * Returns the name for the service ie. the name.
	 *
	 * @return string The service name.
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * Returns a type used by the service based on its name.
	 *
	 * @param string $identifier The identifier for the type.
	 *
	 * @return Type|null The type or null if the type does not exist.
	 */
	public function getType($identifier)
	{
		return isset($this->types[$identifier]) ? $this->types[$identifier] : null;
	}


	/**
	 * Returns all types defined by the service.
	 *
	 * @return array An array of types.
	 */
	public function getTypes()
	{
		return $this->types;
	}


	/**
	 * Generates the class if not already generated
	 */
	public function generateClass()
	{

		$className      = $this->getClassName();
		$classNamespace = $this->getNamespace();

//		$name = $this->name;
//
//		// Generate a valid class name
//		$name = Validator::validateClass($name, $this->config->get($this->config::PACKAGE_NAMESPACE));
//
//		// Uppercase the name
//		$name = ucfirst($name);

		// Create the class object
		$comment = new PhpDocComment($this->description);

		// Create the service class
		$this->class = new PhpClass(
			$classNamespace,
			$className,
			false,
			$this->config->get($this->config::SOAP_CLIENT_CLASS),
			$comment
		);
		$this->class->setSavePath($this->config->get($this->config::SERVICES_DIRECTORY));

		// Create the constructor
		$comment = new PhpDocComment();
		$comment->addParam(PhpDocElementFactory::getParam('array', 'options', 'A array of config values'));
		$comment->addParam(PhpDocElementFactory::getParam('string', 'wsdl', 'The wsdl file to use'));

		$source  = '  if(!isset($options[\'classmap\'])) {' . PHP_EOL;
		$source .= '    $options[\'classmap\'] = ClassMap::get();' . PHP_EOL;
		$source .= '  }' . PHP_EOL;
		$source .= sprintf(
			'  $options = array_merge(%s, $options);' . PHP_EOL,
			var_export($this->config->get($this->config::SOAP_CLIENT_OPTIONS), true)
		);
		$source .= '  if (!$wsdl) {' . PHP_EOL;
		$source .= '    $wsdl = \'' . $this->config->get($this->config::INPUT_FILE) . '\';' . PHP_EOL;
		$source .= '  }' . PHP_EOL;
		$source .= '  parent::__construct($wsdl, $options);' . PHP_EOL;

		$function = new PhpFunction(
			'public',
			'__construct',
			'array $options = array(), $wsdl = null',
			$source,
			$comment
		);

		// Add the constructor
		$this->class->addFunction($function);

//		// Generate the classmap
//		$name    = 'classmap';
//		$comment = new PhpDocComment();
//		$comment->setVar(PhpDocElementFactory::getVar('array', $name, 'The defined classes'));
//
//		$init = [];
//		foreach ($this->types as $type)
//		{
//			if ($type instanceof Struct)
//			{
//				$init[$type->getName()] = '\\' . $type->getClassNamespace();
//			}
//		}
//		$var = new PhpVariable('private static', $name, var_export($init, true), $comment);
//
//		// Add the classmap variable
//		$this->class->addVariable($var);

		// Add all methods
		foreach ($this->operations as $operation)
		{
			$name = Validator::validateOperation($operation->getName());

			$comment = new PhpDocComment($operation->getDescription());
			$comment->setReturn(PhpDocElementFactory::getReturn($operation->getReturns(), ''));

			foreach ($operation->getParams() as $param => $hint)
			{
				$arr = $operation->getPhpDocParams($param, $this->types);
				$comment->addParam(PhpDocElementFactory::getParam($arr['type'], $arr['name'], $arr['desc']));
			}

			$source = sprintf(
				'return $this->__soapCall(\'%s\',array(%s));' . PHP_EOL,
				$operation->getName(),
				$operation->getParamStringNoTypeHints()
			);

			$paramStr = $operation->getParamString($this->types);

			$function = new PhpFunction('public', $name, $paramStr, $source, $comment);

			if ($this->class->functionExists($function->getIdentifier()) == false)
			{
				$this->class->addFunction($function);
			}
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
			$namespace .= (!empty($this->config->get($this->config::SERVICES_DIRECTORY)))
				? ucfirst($this->config->get($this->config::SERVICES_DIRECTORY))
				: '';
			return $namespace;
		}
		return null;
	}


	/**
	 * Add an operation to the service.
	 *
	 * @param Operation $operation The operation to be added.
	 */
	public function addOperation(Operation $operation)
	{
		$this->operations[$operation->getName()] = $operation;
	}
}
