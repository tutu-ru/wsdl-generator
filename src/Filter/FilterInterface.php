<?php

namespace Tutu\Wsdl2PhpGenerator\Filter;

use Tutu\Wsdl2PhpGenerator\Service\Service;

/**
 * Interface FilterInterface
 *
 * @package Tutu\Wsdl2PhpGenerator\Filter
 */
interface FilterInterface
{
	/**
	 * Filter a service.
	 *
	 * @param Service $service The initial service.
	 *
	 * @return Service The altered service.
	 */
	public function filter(Service $service);
}
