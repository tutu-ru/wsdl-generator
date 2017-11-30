<?php

namespace Tutu\Wsdl2PhpGenerator\Filter;


use Tutu\Wsdl2PhpGenerator\Service\Service;

/**
 * Class DefaultFilter
 *
 * @package Tutu\Wsdl2PhpGenerator\Filter
 */
class DefaultFilter implements FilterInterface
{
	/**
	 * @inheritdoc
	 */
	public function filter(Service $service)
	{
		return $service;
	}
}
