<?php

namespace Tutu\Wsdl2PhpGenerator\Generator;

use Psr\Log\LoggerInterface;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;
use Tutu\Wsdl2PhpGenerator\Filter\FilterFactory;
use Tutu\Wsdl2PhpGenerator\Output\OutputManager;
use Tutu\Wsdl2PhpGenerator\Service\ClassMap;
use Tutu\Wsdl2PhpGenerator\Service\Operation;
use Tutu\Wsdl2PhpGenerator\Service\Service;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Struct;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Enum;
use Tutu\Wsdl2PhpGenerator\Xml\WsdlDocument;

/**
 * Class Generator
 *
 * @package Tutu\Wsdl2PhpGenerator\Generator
 */
class Generator implements GeneratorInterface
{
	/**
	 * @var WsdlDocument
	 */
	protected $wsdl;

	/**
	 * @var Service
	 */
	protected $service;

	/**
	 * @var ClassMap
	 */
	protected $classMap;

	/**
	 * An array of objects that represents the types in the service
	 *
	 * @var array
	 */
	protected $types = [];

	/**
	 * This is the object that holds the current config
	 *
	 * @var ConfigInterface
	 */
	protected $config;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;


	/**
	 * Generator constructor.
	 *
	 * @param ConfigInterface $config The config to use for generation
	 */
	public function __construct(ConfigInterface $config)
	{
		$this->config = $config;
		$this->classMap = null;
		$this->service = null;
		$this->types   = [];
	}


	/**
	 * Generates php source code from a wsdl file
	 */
	public function generate()
	{
		$this->log('Starting generation');

		// Warn users who have disabled SOAP_SINGLE_ELEMENT_ARRAYS.
		// Note that this can be
		$options = $this->config->get($this->config::SOAP_CLIENT_OPTIONS);
		if (empty($options['features']) ||
			(($options['features'] & SOAP_SINGLE_ELEMENT_ARRAYS) != SOAP_SINGLE_ELEMENT_ARRAYS)
		)
		{
			$message = [
				'SoapClient option feature SOAP_SINGLE_ELEMENT_ARRAYS is not set.',
				'This is not recommended as dataTypes in DocBlocks for array properties will not be ',
				'valid if the array only contains a single value.'
			];
			$this->log(implode(PHP_EOL, $message), 'warning');
		}

		$wsdl = $this->config->get($this->config::INPUT_FILE);
		if (is_array($wsdl))
		{
			foreach ($wsdl as $ws)
			{
				$this->load($ws);
			}
		}
		else
		{
			$this->load($wsdl);
		}

		$this->savePhp();
		$this->log('Generation complete', 'info');
	}


	/**
	 * Load the wsdl file into php
	 *
	 * @param string $wsdl The wsdl file or url
	 */
	protected function load($wsdl)
	{
		$this->log('Loading the WSDL');
		$this->wsdl  = new WsdlDocument($this->config, $wsdl);
		$this->types = [];
		$this->loadTypes();
		$this->loadService();
	}


	/**
	 * Loads the service class
	 */
	protected function loadService()
	{
		$service = $this->wsdl->getService();
		$this->log('Starting to load service ' . $service->getName());
		$this->service = new Service($this->config, $service->getName(), $this->types, $service->getDocumentation());

		foreach ($this->wsdl->getOperations() as $function)
		{
			$this->log('Loading function ' . $function->getName());
			$this->service->addOperation(
				new Operation(
					$function->getName(),
					$function->getParams(),
					$function->getDocumentation(),
					$function->getReturns()
				)
			);
		}

		$this->log('Done loading service ' . $service->getName());
	}


	/**
	 * Loads all type classes
	 */
	protected function loadTypes()
	{
		$this->log('Loading types');
		$types = $this->wsdl->getTypes();
		$this->classMap = new ClassMap($this->config);
		foreach ($types['structures'] as $structure)
		{
			/** @var Struct $structure */
			$this->types[$structure->getName()] = $structure;
			$this->classMap->addToClass($structure->getName(), $structure->getClassNamespace());
		}
		foreach ($types['enums'] as $enum)
		{
			/** @var Enum $enum */
			$this->types[$enum->getName()] = $enum;
		}
//		foreach ($types['structures'] as $typeNode)
//		{
//			$type = null;
//			if ($typeNode->isComplex())
//			{
//				if ($typeNode->isArray())
//				{
//					$type = new ArrayType($this->config, $typeNode->getName());
//				}
//				else
//				{
//					$type = new ComplexType($this->config, $typeNode->getName());
//				}
//				$this->log('Loading type ' . $type->getPhpIdentifier());
//				$type->setAbstract($typeNode->isAbstract());
//				foreach ($typeNode->getParts() as $name => $typeName)
//				{
//					// There are 2 ways a wsdl can indicate that a field accepts the null value -
//					// by setting the "nillable" attribute to "true" or by setting the "minOccurs" attribute to "0".
//					// See http://www.ibm.com/developerworks/webservices/library/ws-tip-null/index.html
//					$nillable = $typeNode->isElementNillable($name) || $typeNode->getElementMinOccurs($name) === 0;
//					$type->addMember($typeName, $name, $nillable);
//				}
//			}
//			elseif ($enumValues = $typeNode->getEnumerations())
//			{
//				$type = new Enum($this->config, $typeNode->getName(), $typeNode->getRestriction());
//				array_walk($enumValues, function ($value) use ($type) { $type->addValue($value); });
//			}
//			elseif ($pattern = $typeNode->getPattern())
//			{
//				$type = new Pattern($this->config, $typeNode->getName(), $typeNode->getRestriction());
//				$type->setValue($pattern);
//				$type->setBaseType($typeNode->getRestrictionBaseType());
//				$this->typesMappings[$typeNode->getName()] = $type->getBaseType();
//			}
//			else
//			{
//				$this->typesMappings[$typeNode->getName()] = $typeNode->getRestriction();
//			}
//
//			if ($type != null)
//			{
//				$already_registered = false;
//				$foundType          = null;
//				if ($this->config->get($this->config::SHARED_TYPES))
//				{
//					foreach ($this->types as $key => $registeredType)
//					{
//						if ($registeredType->getIdentifier() == $type->getIdentifier())
//						{
//							$already_registered = true;
//							$foundType          = $key;
//							break;
//						}
//						if ($registeredType->getPhpIdentifier() == $type->getPhpIdentifier())
//						{
//							$already_registered = true;
//							$foundType          = $key;
//							break;
//						}
//					}
//				}
//				if (!$already_registered)
//				{
//					if ($type instanceof ComplexType || $type instanceof Enum)
//					{
//						$this->typesNamespaces[$type->getPhpIdentifier()] = $type->getNamespace();
//					}
//					$type->setGenerator($this);
//					$this->types[$typeNode->getName()] = $type;
//				}
//				else
//				{
//					// @todo check types for consistency and omitted attributes
//					if (($type instanceof ComplexType) && ($this->types[$foundType] instanceof ComplexType))
//					{
//						$type = $this->getMergedComplexType($type, $this->types[$foundType]);
//						$type->setGenerator($this);
//						$this->types[$typeNode->getName()] = $type;
//					}
//				}
//			}
//		}
//
//		// Loop through all types again to setup class inheritance.
//		// We can only do this once all types have been loaded. Otherwise we risk referencing types which have not been
//		// loaded yet.
//		foreach ($types as $type)
//		{
//			if (($baseType = $type->getBase()) && isset($this->types[$baseType]) &&
//				$this->types[$baseType] instanceof ComplexType
//			)
//			{
//				$this->types[$type->getName()]->setBaseType($this->types[$baseType]);
//			}
//		}
//
//		// Loop through all types again to setup class inheritance.
//		// We can only do this once all types have been loaded. Otherwise we risk referencing types which have not been
//		// loaded yet.
//		foreach ($this->types as $key => $type)
//		{
//			if($type instanceof ComplexType)
//			{
//				$this->types[$key]->normalizeMembers();
//			}
//		}
		$this->log('Done loading types');
	}

	/**
	 * Save all the loaded classes to the configured output dir
	 *
	 * @throws GeneratorException If no service is loaded
	 */
	protected function savePhp()
	{
		$factory         = new FilterFactory();
		$filter          = $factory->create($this->config);
		$filteredService = $filter->filter($this->service);
		$service         = $filteredService->getClass();
		$filteredTypes   = $filteredService->getTypes();
		if ($service == null)
		{
			throw new GeneratorException('No service loaded');
		}

		$output = new OutputManager($this->config);
		$types = [];
		foreach ($filteredTypes as $type)
		{
			/** @var Struct|Enum $type */
			$types[] = $type->getClass();
		}
		$types[] = $this->classMap->getClass();
//
//		// Generate all type classes
//		$types = [];
//		foreach ($filteredTypes['structures'] as $type)
//		{
//			$class = $type->getClass();
//			if ($class != null)
//			{
//				$types[] = $class;
//			}
//		}
//		foreach ($filteredTypes['enums'] as $type)
//		{
//			$class = $type->getClass();
//			if ($class != null)
//			{
//				$types[] = $class;
//			}
//		}

		$output->save($service, $types);
	}


	/**
	 * Logs a message.
	 *
	 * @param string $message The message to log
	 * @param string $level
	 */
	protected function log($message, $level = 'notice')
	{
		if ($this->config->get($this->config::VERBOSE) && isset($this->logger))
		{
			$this->logger->log($level, $message);
		}
	}


	/**
	 * @inheritdoc
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

}
