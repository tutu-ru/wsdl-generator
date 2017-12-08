<?php

namespace Tutu\Wsdl2PhpGenerator\Base;

use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;

/**
 * Class StreamContextFactory
 *
 * @package Tutu\Wsdl2PhpGenerator\Base
 */
class StreamContextFactory
{

	/**
	 * Creates a stream context based on the provided configuration.
	 *
	 * @param ConfigInterface $config The configuration.
	 *
	 * @return resource A stream context based on the provided configuration.
	 */
	public function create(ConfigInterface $config)
	{
		$options = [];
		$headers = [];

		$proxy = $config->get($config::PROXY);
		if (is_array($proxy))
		{
			$options = ['http' => ['proxy' => $proxy['proxyHost'] . ':' . $proxy['proxyPort']]];
			if (isset($proxy['proxyLogin']) && isset($proxy['proxyPassword']))
			{
				$authHash = base64_encode($proxy['proxyLogin'] . ':' . $proxy['proxyPassword']);
				$headers[] = 'Proxy-Authorization: Basic ' . $authHash;
			}
		}

		$soapOptions = $config->get($config::SOAP_CLIENT_OPTIONS);

		if (
			(
				!isset($soapOptions['authentication']) 
				|| $soapOptions['authentication'] === SOAP_AUTHENTICATION_BASIC
			) 
			&& isset($soapOptions['login']) 
			&& isset($soapOptions['password'])
		)
		{
			$authHash = base64_encode($soapOptions['login'] . ':' . $soapOptions['password']);
			$headers[] = 'Authorization: Basic ' . $authHash;
		}

		if (count($headers) > 0)
		{
			$options['http']['header'] = $headers;
		}

		return stream_context_create($options);
	}
}
