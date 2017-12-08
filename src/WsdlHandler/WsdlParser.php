<?php

namespace Tutu\Wsdl2PhpGenerator\WsdlHandler;


use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;

class WsdlParser
{

	/**
	 * The configuration.
	 *
	 * @var ConfigInterface
	 */
	protected $config;
	/**
	 * An instance of a PHP SOAP client based on the WSDL file
	 *
	 * @var \SoapClient
	 */
	protected $soapClient;
	/**
	 * @var mixed
	 */
	protected $wsdlDocument;
	/**
	 * @var array
	 */
	protected $wsdlSchemas = [];
	/**
	 * @var array
	 */
	public $structures = [];
	/**
	 * @var array
	 */
	public $patterns = [];
	/**
	 * @var array
	 */
	public $unions = [];
	/**
	 * @var array
	 */
	public $types = [];

	/**
	 * WsdlDocument constructor.
	 *
	 * @param ConfigInterface $config
	 *
	 * @throws GeneratorException
	 */
	public function __construct(ConfigInterface $config)
	{
		$this->config = $config;
		$wsdlUrl = $this->config->get($this->config::INPUT_FILE);
		$configSoapOptions = $this->config->get($this->config::SOAP_CLIENT_OPTIONS);
		$clientSoapOptions = array_merge($configSoapOptions, ['cache_wsdl' => WSDL_CACHE_NONE]);

		try
		{
			$soapClientClass  = new \ReflectionClass($this->config->get($this->config::SOAP_CLIENT_CLASS));
			$this->soapClient = $soapClientClass->newInstance($wsdlUrl, $clientSoapOptions);
		}
		catch (\SoapFault $e)
		{
			throw new GeneratorException('Unable to load WSDL: ' . $e->getMessage(), $e->getCode(), $e);
		}

		$this->loadSchemas();
		$this->parseSoapTypes();
		$this->buildPhpTypes();
	}


	/**
	 * Load all wsdl and xsd schemas
	 */
	protected function loadSchemas()
	{
		$this->wsdlSchemas = [];
	}


	/**
	 * Get parsed wsdl soap types
	 * 
	 * We have 3 type of data in soap types
	 * 1. Struct - Enum or Complex structure type
	 * 2. Union - an union of types
	 * 3. Pattern - is a mapping type
	 */
	protected function parseSoapTypes()
	{
		$typeStrings = $this->soapClient->__getTypes();
		foreach ($typeStrings as $typeString)
		{
			// collect all wsdl types
			if(substr($typeString, 0, strlen('struct')) == 'struct')
			{
				$struct = new Struct($typeString);
				$this->structures[] = $struct;
			}
			else if(substr($typeString, 0, strlen('union')) == 'union')
			{
				$union = new Union($typeString);
				$this->unions[] = $union;
			}
			else
			{
				$pattern = new Pattern($typeString);
				$this->patterns[] = $pattern;
			}
		}
	}


	/**
	 * Build php DTO types
	 */
	protected function buildPhpTypes()
	{
		$this->types = [];
	}
}