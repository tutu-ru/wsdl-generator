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
	 */
	public function generate();


	/**
	 * Inject a logger into the code generation process.
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger(LoggerInterface $logger);

}
