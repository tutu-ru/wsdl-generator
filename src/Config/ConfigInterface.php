<?php

namespace Tutu\Wsdl2PhpGenerator\Config;

/**
 * The config interface which implemented represents
 * a configuration that is used across this project.
 *
 * @package Tutu\Wsdl2PhpGenerator
 */

interface ConfigInterface
{
	/**
	 * Get a value from the configuration by key.
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function get($key);


	/**
	 * Set or overwrite a configuration key with a given value.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return ConfigInterface
	 */
	public function set($key, $value);
}
