<?php

namespace Tutu\Wsdl2PhpGenerator\Xml;

/**
 * Class ServiceNode
 *
 * @package Tutu\Wsdl2PhpGenerator\Xml
 */
class ServiceNode extends DocumentedNode
{

	/**
	 * Returns the name of the service.
	 *
	 * @return string The service name.
	 */
	public function getName()
	{
		return $this->element->getAttribute('name');
	}
}
