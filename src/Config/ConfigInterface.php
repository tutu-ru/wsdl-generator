<?php

namespace Tutu\Wsdl2PhpGenerator\Config;

use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;

/**
 * The config interface which implemented represents
 * a configuration that is used across this project.
 *
 * @package Tutu\Wsdl2PhpGenerator
 */

interface ConfigInterface
{
	// namespace and base extend class options
	const PACKAGE_NAMESPACE = 'packageNamespace';
	const BASE_EXTEND_CLASS = 'baseExtendClass';

	// input file and directories options
	const INPUT_FILE = 'inputFile';
	const OUTPUT_DIRECTORY = 'outputDirectory';
	const ARRAYS_DIRECTORY = 'arraysDirectory';
	const ENUMS_DIRECTORY = 'enumsDirectory';
	const SERVICES_DIRECTORY = 'servicesDirectory';
	const STRUCTS_DIRECTORY = 'structsDirectory';
	const WSDLS_DIRECTORY = 'wsdlsDirectory';

	// classes generation options 
	const CLASS_NAMES = 'classNames';
	const CLASS_PREFIX = 'classPrefix';
	const CLASS_SUFFIX = 'classSuffix';
	const OPERATION_NAMES = 'operationNames';
	const SHARED_TYPES = 'sharedTypes';
	const MAPPED_TYPES = 'mappedTypes';

	// methods and params options
	const CONSTRUCTOR_NULL_PARAMS = 'constructorNullParams';
	const CONSTRUCTORS_ENABLED = 'constructorsEnabled';
	const SETTERS_ENABLED = 'settersEnabled';
	const GETTERS_ENABLED = 'gettersEnabled';

	// soap class and options 
	const SOAP_CLIENT_CLASS = 'soapClientClass';
	const SOAP_CLIENT_OPTIONS = 'soapClientOptions';

	// proxy options
	const PROXY = 'proxy';

	// verbosity options
	const VERBOSE = 'verbose';


	/**
	 * Get a value from the configuration by key.
	 * Throws an exception if key is invalid.
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws GeneratorException
	 */
	public function get($key);


	/**
	 * Set or overwrite a configuration key with a given value.
	 * Throws an exception if key is invalid.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return ConfigInterface
	 * @throws GeneratorException
	 */
	public function set($key, $value);
}
