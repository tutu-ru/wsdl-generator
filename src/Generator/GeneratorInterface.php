<?php

namespace Tutu\Wsdl2PhpGenerator\Generator;


use Psr\Log\LoggerInterface;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;

/**
 * Interface GeneratorInterface
 *
 * @package Tutu\Wsdl2PhpGenerator\Generator
 */
interface GeneratorInterface
{

	/**
	 * Generates php source code from a wsdl file
	 *
	 * @param ConfigInterface $config The config to use for generation
	 */
	public function generate(ConfigInterface $config);


	/**
	 * Inject a logger into the code generation process.
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger(LoggerInterface $logger);

}
