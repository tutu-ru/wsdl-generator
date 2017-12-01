<?php

namespace Tutu\Wsdl2PhpGenerator\Config;

use InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;

/**
 * This class contains configurable key/value pairs.
 *
 * @package Tutu\Wsdl2PhpGenerator
 */
class Config implements ConfigInterface
{

	/**
	 * @var array The actual key/value pairs.
	 */
	protected $options;


	public function __construct(array $options)
	{
		$resolver = new OptionsResolver();
		$this->configureOptions($resolver);

		$this->options = $resolver->resolve($options);
	}


	/**
	 * @return array Available config options
	 */
	public static function getOptionsList()
	{
		return [
			// namespace and base extend class options
			self::PACKAGE_NAMESPACE,
			self::BASE_EXTEND_CLASS,

			// input file and directories options
			self::INPUT_FILE,
			self::OUTPUT_DIRECTORY,
			self::ARRAYS_DIRECTORY,
			self::ENUMS_DIRECTORY,
			self::SERVICES_DIRECTORY,
			self::STRUCTS_DIRECTORY,
			self::WSDLS_DIRECTORY,

			// classes generation options 
			self::CLASS_NAMES,
			self::CLASS_PREFIX,
			self::CLASS_SUFFIX,
			self::OPERATION_NAMES,
			self::SHARED_TYPES,
			self::MAPPED_TYPES,

			// methods and params options
			self::CONSTRUCTOR_NULL_PARAMS,
			self::CONSTRUCTORS_ENABLED,
			self::SETTERS_ENABLED,
			self::GETTERS_ENABLED,

			// soap class and options 
			self::SOAP_CLIENT_CLASS,
			self::SOAP_CLIENT_OPTIONS,

			// proxy options
			self::PROXY,

			// verbosity options
			self::VERBOSE,
		];
	}


	/**
	 * @return array Default config options
	 */
	public static function getDefaultOptions()
	{
		return [
			// namespace and base extend class options
			self::PACKAGE_NAMESPACE       => 'MyNS\MyService',
			self::BASE_EXTEND_CLASS       => null,

			// input file and directories options
			self::INPUT_FILE              => 'inputFile',
			self::OUTPUT_DIRECTORY        => 'output',
			self::ARRAYS_DIRECTORY        => 'Arrays',
			self::ENUMS_DIRECTORY         => 'Enums',
			self::SERVICES_DIRECTORY      => 'Services',
			self::STRUCTS_DIRECTORY       => 'Structs',
			self::WSDLS_DIRECTORY         => 'Wsdls',

			// classes generation options 
			self::CLASS_NAMES             => '',
			self::CLASS_PREFIX            => '',
			self::CLASS_SUFFIX            => '',
			self::OPERATION_NAMES         => '',
			self::SHARED_TYPES            => true,
			self::MAPPED_TYPES            => [],

			// methods and options
			self::CONSTRUCTOR_NULL_PARAMS => true,
			self::CONSTRUCTORS_ENABLED    => true,
			self::SETTERS_ENABLED         => true,
			self::GETTERS_ENABLED         => true,

			// soap class and options 
			self::SOAP_CLIENT_CLASS       => '\SoapClient',
			self::SOAP_CLIENT_OPTIONS     => [],

			// proxy options
			self::PROXY                   => null,

			// verbosity options
			self::VERBOSE                 => false,
		];
	}


	/**
	 * Get a value from the configuration by key.
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws GeneratorException
	 */
	public function get($key)
	{
		if (!in_array($key, self::getOptionsList()))
		{
			throw new GeneratorException(sprintf('The key %s does not exist in config options list.', $key));
		}

		return $this->options[$key];
	}


	/**
	 * Set or overwrite a configuration key with a given value.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return $this|ConfigInterface
	 * @throws GeneratorException
	 */
	public function set($key, $value)
	{
		if (!in_array($key, self::getOptionsList()))
		{
			throw new GeneratorException(sprintf('The key %s does not exist in config options list.', $key));
		}

		$this->options[$key] = $value;
		return $this;
	}


	protected function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setRequired(
			[
				'inputFile',
				'outputDir'
			]
		);

		$resolver->setDefaults(
			self::getDefaultOptions()
//			[
//				'verbose'                        => false,
//				'namespaceName'                  => '',
//				'classNames'                     => '',
//				'operationNames'                 => '',
//				'sharedTypes'                    => false,
//				'constructorParamsDefaultToNull' => true,
//				'soapClientClass'                => '\SoapClient',
//				'soapClientOptions'              => [],
//				'proxy'                          => false,
//
//				'baseComplexClass' => false,
//				'settersEnabled'   => true,
//				'gettersEnabled'   => true,
//
//				'arrayTypeFolder'       => 'Arrays',
//				'complexTypeFolder'     => 'Structs',
//				'enumerationTypeFolder' => 'Enums',
//				'serviceFolder'         => 'Services',
//			]
		);

		// A set of configuration options names and normalizer callable's.
		$normalizers = [
			self::CLASS_NAMES         => [$this, 'normalizeArray'],
			self::OPERATION_NAMES     => [$this, 'normalizeArray'],
			self::SOAP_CLIENT_OPTIONS => [$this, 'normalizeSoapClientOptions'],
			self::PROXY               => [$this, 'normalizeProxy'],
		];
		// Convert each callable to a closure as that is required by OptionsResolver->setNormalizer().
		$normalizers = array_map(
			function ($callable)
			{
				return function (Options $options, $value) use ($callable)
				{
					// Using reflection here is quite ugly. This can be reduced to the following
					// once we drop 5.3 support.
					// return call_user_func_array($callable, func_get_args());
					list($object, $method) = $callable;
					$normalizer = new \ReflectionMethod(get_class($object), $method);
					$normalizer->setAccessible(true);
					return $normalizer->invokeArgs($object, func_get_args());
				};
			},
			$normalizers
		);

		foreach ($normalizers as $option => $normalizer)
		{
			$resolver->setNormalizer($option, $normalizer);
		}
	}


	/**
	 * Normalize a string or array to an array.
	 *
	 * Each value is cleaned up, removing excessive spacing before and after values.
	 *
	 * @param Options      $options
	 * @param array|string $value The value to be normalized.
	 *
	 * @return array An array of normalized values.
	 */
	protected function normalizeArray(Options $options, $value)
	{
		if(is_array($value))
		{
			return $value;
		}
		if (strlen($value) === 0)
		{
			return [];
		}

		return array_map('trim', explode(',', $value));
	}


	/**
	 * Normalize the soapClientOptions configuration option.
	 *
	 * @see http://php.net/manual/en/soapclient.soapclient.php.
	 *
	 * @param Options $options
	 * @param array   $value The value to be normalized.
	 *
	 * @return array An array of normalized values.
	 */
	protected function normalizeSoapClientOptions(Options $options, array $value)
	{
		// The SOAP_SINGLE_ELEMENT_ARRAYS feature should be enabled by default if no other option has been set
		// explicitly while leaving this out. This cannot be handled in the defaults as soapClientOptions is a
		// nested array.
		if (!isset($value['features']))
		{
			$value['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		}

		// Merge proxy options into soapClientOptions to propagate general configuration options into the
		// SoapClient. It is important that the proxy configuration has been normalized before it is merged.
		// The OptionResolver ensures this by normalizing values on access.
		if (!empty($options['proxy']))
		{
			$value = array_merge($options['proxy'], $value);
		}

		return $value;
	}


	/**
	 * Normalize the proxy configuration option.
	 *
	 * The normalized value is an array with the following keys:
	 * - proxy_host
	 * - proxy_port
	 * - proxy_login (optional)
	 * - proxy_password (optional)
	 *
	 * @param Options      $options
	 * @param string|array $value The value to be normalized
	 *
	 * @return array|bool The normalized value.
	 * @throws GeneratorException
	 */
	protected function normalizeProxy(Options $options, $value)
	{
		if (!$value)
		{
			// proxy setting is optional
			return false;
		}
		if (is_string($value))
		{
			$url_parts = parse_url($value);
			if ($url_parts === false)
			{
				throw new GeneratorException('"proxy" configuration setting contains a malformed url.');
			}

			$proxy_array = [
				'proxy_host' => $url_parts['host']
			];
			if (isset($url_parts['port']))
			{
				$proxy_array['proxy_port'] = $url_parts['port'];
			}
			if (isset($url_parts['user']))
			{
				$proxy_array['proxy_login'] = $url_parts['user'];
			}
			if (isset($url_parts['pass']))
			{
				$proxy_array['proxy_password'] = $url_parts['pass'];
			}
			$value = $proxy_array;
		}
		elseif (is_array($value))
		{
			foreach ($value as $k => $v)
			{
				// Prepend proxy_ to each key to match the expended proxy option names of the PHP SoapClient.
				$value['proxy_' . $k] = $v;
				unset($value[$k]);
			}

			if (empty($value['proxy_host']) || empty($value['proxy_port']))
			{
				throw new GeneratorException(
					'"proxy" configuration setting must contain at least keys "host" and "port'
				);
			}
		}
		else
		{
			throw new GeneratorException(
				'"proxy" configuration setting must be either a string containing the proxy url '
				. 'or an array containing at least a key "host" and "port"'
			);
		}

		// Make sure port is an integer
		$value['proxy_port'] = intval($value['proxy_port']);

		return $value;
	}
}
