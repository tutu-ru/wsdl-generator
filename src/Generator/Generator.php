<?php

namespace Tutu\Wsdl2PhpGenerator\Generator;

use Psr\Log\LoggerInterface;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;
use Tutu\Wsdl2PhpGenerator\Filter\FilterFactory;
use Tutu\Wsdl2PhpGenerator\Output\OutputManager;
use Tutu\Wsdl2PhpGenerator\PhpType\ArrayType;
use Tutu\Wsdl2PhpGenerator\PhpType\ComplexType;
use Tutu\Wsdl2PhpGenerator\PhpType\Enum;
use Tutu\Wsdl2PhpGenerator\PhpType\Pattern;
use Tutu\Wsdl2PhpGenerator\PhpType\Type;
use Tutu\Wsdl2PhpGenerator\Service\Operation;
use Tutu\Wsdl2PhpGenerator\Service\Service;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\WsdlSchema;
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
	 * An array of Type objects that represents the types in the service
	 *
	 * @var Type[]
	 */
	protected $types = [];

	/**
	 * Array that holds all types and namespaces
	 *
	 * @var array
	 */
	protected $typesNamespaces = [];

	/**
	 * Array that holds all types mappings
	 *
	 * @var array
	 */
	protected $typesMappings = ['AncillaryTax' => 'Tax'];

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
		$this->service = null;
		$this->types   = [];
	}


	/**
	 * Generates php source code from a wsdl file
	 */
	public function generate()
	{
//		$schema = new WsdlSchema();
//		$schema->load('http://files.developer.sabre.com/wsdl/sabreXML1.0.00/shopping/BargainFinderMaxRQ_GIR_v3.3.0.wsdl');
//
//
//		//file_put_contents('lib2.xml', $schema->saveXML());
//
//		$xpath = new \DOMXPath($schema);
//		$xpath->registerNamespace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
//		$xpath->registerNamespace('s', 'http://www.w3.org/2001/XMLSchema');
//		$s = $xpath->query('//s:schema');
//		var_dump($s);
//		exit;
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
exit;
		foreach ($types as $typeNode)
		{
			$type = null;
			if ($typeNode->isComplex())
			{
				if ($typeNode->isArray())
				{
					$type = new ArrayType($this->config, $typeNode->getName());
				}
				else
				{
					$type = new ComplexType($this->config, $typeNode->getName());
				}
				$this->log('Loading type ' . $type->getPhpIdentifier());
				$type->setAbstract($typeNode->isAbstract());
				foreach ($typeNode->getParts() as $name => $typeName)
				{
					// There are 2 ways a wsdl can indicate that a field accepts the null value -
					// by setting the "nillable" attribute to "true" or by setting the "minOccurs" attribute to "0".
					// See http://www.ibm.com/developerworks/webservices/library/ws-tip-null/index.html
					$nillable = $typeNode->isElementNillable($name) || $typeNode->getElementMinOccurs($name) === 0;
					$type->addMember($typeName, $name, $nillable);
				}
			}
			elseif ($enumValues = $typeNode->getEnumerations())
			{
				$type = new Enum($this->config, $typeNode->getName(), $typeNode->getRestriction());
				array_walk($enumValues, function ($value) use ($type) { $type->addValue($value); });
			}
			elseif ($pattern = $typeNode->getPattern())
			{
				$type = new Pattern($this->config, $typeNode->getName(), $typeNode->getRestriction());
				$type->setValue($pattern);
				$type->setBaseType($typeNode->getRestrictionBaseType());
				$this->typesMappings[$typeNode->getName()] = $type->getBaseType();
			}
			else
			{
				$this->typesMappings[$typeNode->getName()] = $typeNode->getRestriction();
			}

			if ($type != null)
			{
				$already_registered = false;
				$foundType          = null;
				if ($this->config->get($this->config::SHARED_TYPES))
				{
					foreach ($this->types as $key => $registeredType)
					{
						if ($registeredType->getIdentifier() == $type->getIdentifier())
						{
							$already_registered = true;
							$foundType          = $key;
							break;
						}
						if ($registeredType->getPhpIdentifier() == $type->getPhpIdentifier())
						{
							$already_registered = true;
							$foundType          = $key;
							break;
						}
					}
				}
				if (!$already_registered)
				{
					if ($type instanceof ComplexType || $type instanceof Enum)
					{
						$this->typesNamespaces[$type->getPhpIdentifier()] = $type->getNamespace();
					}
					$type->setGenerator($this);
					$this->types[$typeNode->getName()] = $type;
				}
				else
				{
					// @todo check types for consistency and omitted attributes
					if (($type instanceof ComplexType) && ($this->types[$foundType] instanceof ComplexType))
					{
						$type = $this->getMergedComplexType($type, $this->types[$foundType]);
						$type->setGenerator($this);
						$this->types[$typeNode->getName()] = $type;
					}
				}
			}
		}

		// Loop through all types again to setup class inheritance.
		// We can only do this once all types have been loaded. Otherwise we risk referencing types which have not been
		// loaded yet.
		foreach ($types as $type)
		{
			if (($baseType = $type->getBase()) && isset($this->types[$baseType]) &&
				$this->types[$baseType] instanceof ComplexType
			)
			{
				$this->types[$type->getName()]->setBaseType($this->types[$baseType]);
			}
		}
		$this->log('Done loading types');
	}


	/**
	 * @param ComplexType $complexFirst
	 * @param ComplexType $complexSecond
	 *
	 * @return ComplexType
	 */
	public function getMergedComplexType($complexFirst, $complexSecond)
	{
		$commonMembers = $complexFirst->getMembers();
		foreach ($complexSecond->getMembers() as $name => $member)
		{
			$found = false;
			foreach ($commonMembers as $commonMember)
			{
				if ($commonMember->getName() == $member->getName())
				{
					if ($commonMember->getType() == $member->getType())
					{
						$found = true;
						break;
					}
					else
					{
						// @TODO map types
						var_dump($commonMember->getType() . '---xxx---' . $member->getType() . ' --- '. $complexFirst->getPhpIdentifier());
					}
				}
			}
			if (!$found)
			{
				if (!in_array($member->getName(), $commonMembers))
				{
					$commonMembers[$name] = $member;
				}
				else
				{
					// @TODO check for types simple vs array or other
					// var_dump($commonMembers[$name]->getType() . ' ---- ' . $member->getType());
				}
				//$commonMembers[$member->getName()] = $member;
			}
		}
		$complexFirst->setMembers($commonMembers);
		return $complexFirst;
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

		// Generate all type classes
		$types = [];
		foreach ($filteredTypes as $type)
		{
			$class = $type->getClass();
			if ($class != null)
			{
				$types[] = $class;
			}
		}

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


	/**
	 * @return array
	 */
	public function getTypesNamespaces()
	{
		return $this->typesNamespaces;
	}


	/**
	 * @return array
	 */
	public function getTypesMappings()
	{
		return $this->typesMappings;
	}
}
