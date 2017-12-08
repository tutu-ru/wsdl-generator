<?php

namespace Tutu\Wsdl2PhpGenerator\Xml;

use Tutu\Wsdl2PhpGenerator\Base\StreamContextFactory;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;

/**
 * Class SchemaDocument
 *
 * @package Tutu\Wsdl2PhpGenerator\Xml
 */
class SchemaDocument extends XmlNode
{

	/**
	 * The url representing the location of the schema.
	 *
	 * @var string
	 */
	protected $url;


	/**
	 * The schemas which are referenced by the current schema.
	 *
	 * @var SchemaDocument[]
	 */
	protected $references;

	/**
	 * The urls of schemas which have already been loaded.
	 *
	 * We keep a record of these to avoid cyclic imports.
	 *
	 * @var string[]
	 */
	protected static $loadedUrls;


	public function __construct(ConfigInterface $config, $xsdUrl)
	{
		$this->url = $xsdUrl;

		// Generate a stream context used by libxml to access external resources.
		// This will allow DOMDocument to load XSDs through a proxy.
		$streamContextFactory = new StreamContextFactory();
		libxml_set_streams_context($streamContextFactory->create($config));

		$document = new \DOMDocument();
		$loaded   = $document->load($xsdUrl);
		if (!$loaded)
		{
			throw new GeneratorException('Unable to load XML from ' . $xsdUrl);
		}

		parent::__construct($document, $document->documentElement);
		// Register the schema to avoid cyclic imports.
		self::$loadedUrls[] = $xsdUrl;

		// Locate and instantiate schemas which are referenced by the current schema.
		// A reference in this context can either be
		// - an import from another namespace: http://www.w3.org/TR/xmlschema-1/#composition-schemaImport
		// - an include within the same namespace: http://www.w3.org/TR/xmlschema-1/#compound-schema
		$this->references = [];
		foreach ($this->xpath(
			'//wsdl:import/@location|' .
			'//s:import/@schemaLocation|' .
			'//s:include/@schemaLocation'
		) as $reference)
		{
			$referenceUrl = $reference->value;
			if (strpos($referenceUrl, '//') === false)
			{
				$referenceUrl = dirname($xsdUrl) . '/' . $referenceUrl;
			}

			if (!in_array($referenceUrl, self::$loadedUrls))
			{
				$this->references[] = new SchemaDocument($config, $referenceUrl);
			}
		}
	}


	/**
	 * Parses the schema for a type with a specific name.
	 *
	 * @param TypeNode $typeNode The name of the type
	 *
	 * @return \DOMElement|null Returns the type node with the provided if it is found. Null otherwise.
	 */
	public function findTypeElement($typeNode)
	{
		$type = null;
		$name = $typeNode->getName();

		// @todo multiple types
		$elements = $this->xpath(
			'//s:simpleType[@name=%s]|//s:complexType[@name=%s]|//s:element[@name=%s and ./s:complexType[not(@name)]]',
			$name, 
			$name,
			$name
		);
		if ($elements->length > 0)
		{
			$type = $this->findElementByStructure($elements);
		}

		if (empty($type))
		{
			foreach ($this->references as $import)
			{
				$type = $import->findTypeElement($typeNode);
				if (!empty($type))
				{
					break;
				}
			}
		}

		return $type;
	}


	/**
	 * @param string $name
	 *
	 * @return \DOMElement[]
	 */
	public function getElementsList($name)
	{
		$elements = [];
		$docElements = $this->xpath(
			'//s:simpleType[@name=%s]|//s:complexType[@name=%s]|//s:element[@name=%s and ./s:complexType[not(@name)]]',
			$name,
			$name,
			$name
		);
		if($docElements->length > 0)
		{
			for($i = 0; $i< $docElements->length; $i++)
			{
				$item = $docElements->item($i);
				$elements[] = $item;

				//echo $item->;
				//$doc->xpath('//s:attribute');
				//$elements['attributes'][] = $doc->xpath('//s:attribute');
			}
		}
		foreach ($this->references as $import)
		{
			$docElements = $import->getElementsList($name);
			if(count($docElements))
			{
				foreach ($docElements as $element)
				{
					array_push($elements, $element);
				}
			}
		}
		return $elements;
	} 
	
	/**
	 * Find element by it's structure
	 * 
	 * @param \DOMNodeList $nodesList
	 *
	 * @return \DOMElement
	 */
	public function findElementByStructure($nodesList)
	{
		//array types has priority
		for($i = 0; $i < $nodesList->length; $i++)
		{
			$node = $nodesList->item($i);
			$maxOccurs = $node->getAttribute('maxOccurs');
			if(($maxOccurs > 1) || ($maxOccurs === 'unbounded'))
			{
				return $node;
			}
		}
		return $nodesList->item(0);
	}
}
