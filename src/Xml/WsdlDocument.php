<?php

namespace Tutu\Wsdl2PhpGenerator\Xml;

use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;

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
	 * @return TypeNode[] DataTypes related to the service.
	 */
	public function getTypes()
	{
		$types       = [];
		$typeStrings = $this->soapClient->__getTypes();
		foreach ($typeStrings as $typeString)
		{
			if(substr($typeString, 0, strlen('union')) !== 'union')
			{
				$type    = new TypeNode($typeString);
				$element = $this->findTypeElement($type);
				if (!empty($element))
				{
					$type->setElement($this->document, $element);
				}
				$types[] = $type;
			}
		}
		return $types;
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
}
