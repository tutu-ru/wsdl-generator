<?php

namespace Tutu\Wsdl2PhpGenerator\Base;


use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;

/**
 * Interface for classes where instances be used can generate a PHP class.
 */
interface ClassGenerator
{

	/**
	 * Returns the object represented as a PHP class.
	 *
	 * @return PhpClass
	 */
	public function getClass();

}
