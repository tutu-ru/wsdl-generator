<?php

namespace Tutu\Wsdl2PhpGenerator\WsdlHandler;

use Tutu\Wsdl2PhpGenerator\Base\StreamContextFactory;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;

class WsdlSchema extends \DOMDocument
{
	public $url;
	public $basePath;
	public $schemaName;
	public $content;
	public $document;
	public $config;

	public function resolveWsdlSchemaPaths()
	{
		$this->basePath   = '';
		$parts            = explode('/', $this->url);
		$this->schemaName = $parts[count($parts) - 1];
		unset($parts[count($parts) - 1]);
		if (count($parts) > 0)
		{
			$this->basePath = implode('/', $parts);
		}
	}
	
	public function load($filename, $resolveIncludes = true, $options = null)
	{
		$this->url    = $filename;
		$this->resolveWsdlSchemaPaths();
		$content = file_get_contents($filename);
		if($content == false)
		{
			var_dump($this->url);
		}
		//chdir(dirname($filename));

		parent::loadXML($content, $options);

		if ($resolveIncludes) {
			$this->resolveIncludes();
		}
	}

	public function resolveIncludes()
	{
		$this->resolveNodeIncludes($this);
	}

	private function resolveNodeIncludes(\DOMNode $node)
	{
		if ($this->isIncludeNode($node)) {

			$location = $node->attributes->getNamedItem('schemaLocation')->textContent;
			var_dump($this->basePath .'/'. $location);
			if (!empty($location))
			{
				$included = new static();
				$included->load($this->basePath .'/'. $location);
				$this->replaceIncludedElements($included, $node);
			}
			else
			{
				throw  new GeneratorException('Found an empty include schema location in ' . $this->url);
			}
		} elseif ($node->childNodes) {
			//var_dump($node->localName);
			for($i = 0; $i < $node->childNodes->length; $i++) {
				$this->resolveNodeIncludes($node->childNodes->item($i));
			}
		}
	}

	private function replaceIncludedElements(\DOMDocument $included, \DOMNode $replace)
	{
		for ($i = 0; $i < $included->childNodes->length; $i++)
		{
			$replace->parentNode->insertBefore(
				$this->importNode($included->childNodes->item($i), true),
				$replace
			);
		}
		//foreach ($included->firstChild->childNodes as $childNode) {
		//	$replace->parentNode->insertBefore($this->importNode($childNode, true), $replace);
		//}

		$replace->parentNode->removeChild($replace);
	}

	private function isIncludeNode(\DOMNode $node)
	{
		return ($node->localName == 'include') || ($node->localName == 'import');// && $node->namespaceURI == 'http://www.w3.org/2001/XMLSchema';
	}
}
