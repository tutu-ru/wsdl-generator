<?php


namespace Tutu\Wsdl2PhpGenerator\Xml;

/**
 * Class DocumentedNode
 *
 * @package Tutu\Wsdl2PhpGenerator\Xml
 */
abstract class DocumentedNode extends XmlNode
{

	/**
	 * Retrieves the documentation for the node.
	 *
	 * @return string The documentation.
	 */
	public function getDocumentation()
	{
		$documentation      = null;
		$documentationNodes = $this->element->getElementsByTagName('documentation');
		if ($documentationNodes->length > 0)
		{
			$documentation = $documentationNodes->item(0)->nodeValue;
		}
		return $documentation;
	}
}
