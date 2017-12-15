<?php

namespace Tutu\Wsdl2PhpGenerator\Service;

use Tutu\Wsdl2PhpGenerator\Base\ClassGenerator;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\Exception\GeneratorException;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocElement;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpFunction;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpDocComment;

class ClassMap implements ClassGenerator
{
	/**
	 * @var ConfigInterface
	 */
	public $config;
	/**
	 * @var array The classes within classmap
	 */
	public $classes = [];
	/**
	 * @var PhpClass The class used to create the service.
	 */
	public $class;


	/**
	 * ClassMap constructor.
	 *
	 * @param ConfigInterface $config
	 */
	public function __construct(ConfigInterface $config) 
	{
		$this->config = $config;
	}

	public function getClass()
	{
		if ($this->class == null)
		{
			$this->generateClass();
		}

		return $this->class;
	}

	public function generateClass()
	{
		if ($this->class != null)
		{
			throw new GeneratorException("The class has already been generated");
		}
		$className      = $this->getClassName();
		$classNamespace = $this->getNamespace();

		$classComment = new PhpDocComment('Class which returns the class map definition');
		$this->class = new PhpClass($classNamespace, $className, false, '', $classComment);
		$this->class->setSavePath('');

		// add ClassMap::get() function
		$classMapGetComment = new PhpDocComment();
		$classMapGetComment->setDescription('Returns the mapping between the WSDL Structs and generated Structs classes');
		$classMapGetComment->addParam(new PhpDocElement('return', 'array', null, null));
		$classMapGetSource = '  return [' . PHP_EOL;
		ksort($this->classes);
		foreach ($this->classes as $baseWsdlClass => $generatedClass)
		{
			$classMapGetSource .= '    \'' . $baseWsdlClass . '\' => \'' . $generatedClass . '\',' . PHP_EOL;
		}
		$classMapGetSource   .= '  ];';
		$classMapGetFunction = new PhpFunction(
			'final public static',
			'get',
			'',
			$classMapGetSource,
			$classMapGetComment
		);
		$this->class->addFunction($classMapGetFunction);
	}

	public function addToClass($baseClass, $generatedClass)
	{
		$this->classes[$baseClass] = $generatedClass;
	}
	
	public function getClassName()
	{
		return 'ClassMap';
	}

	/**
	 * Implements the generation of namespace
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		if ($this->config->get($this->config::PACKAGE_NAMESPACE))
		{
			$namespace = $this->config->get($this->config::PACKAGE_NAMESPACE);
			return $namespace;
		}
		return null;
	}

	/**
	 * Implements the generation of namespace
	 *
	 * @return string
	 */
	public function getClassNamespace()
	{
		return ($this->getNamespace()) ? $this->getNamespace() . '\\' . $this->getClassName() : $this->getClassName();
	}
}