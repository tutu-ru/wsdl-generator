<?php

namespace Tutu\Wsdl2PhpGenerator\Output;

use \Exception;
use Tutu\Wsdl2PhpGenerator\Config\ConfigInterface;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpClass;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpFile;
use Tutu\Wsdl2PhpGenerator\PhpSource\PhpFunction;

/**
 * Class OutputManager
 *
 * @package Tutu\Wsdl2PhpGenerator\Output
 */
class OutputManager
{
	/**
	 * @var string The directory to save the files
	 */
	private $dir = '';


	/**
	 * @var ConfigInterface A reference to the config
	 */
	private $config;


	/**
	 * @param ConfigInterface $config The config to use
	 */
	public function __construct(ConfigInterface $config)
	{
		$this->config = $config;
		$this->setOutputDirectory();
	}


	/**
	 * Saves the service and types php code to file
	 *
	 * @param PhpClass $service
	 * @param array    $types
	 */
	public function save(PhpClass $service, array $types)
	{
		$this->saveClassToFile($service);
		foreach ($types as $type)
		{
			$this->saveClassToFile($type);
		}

		$classes = array_merge([$service], $types);
		$this->saveAutoloader($service->getIdentifier(), $classes);
	}


	/**
	 * Sets the output directory, creates it if needed
	 * This must be called before saving a file
	 *
	 * @throws Exception If the dir can't be created and don't already exists
	 */
	private function setOutputDirectory()
	{
		$outputDirectory = $this->config->get('outputDir');

		//Try to create output dir if non existing
		if ($this->createDirectory($outputDirectory))
		{
			$this->dir = $outputDirectory;
		}
		else
		{
			throw new Exception('Could not create output directory and it does not exist!');
		}
	}


	/**
	 * Append a class to a file and save it
	 * If no file is created the name of the class is the filename
	 *
	 * @param PhpClass $class
	 *
	 * @throws Exception
	 */
	private function saveClassToFile(PhpClass $class)
	{
		if ($this->isValidClass($class))
		{
			$file = new PhpFile($class->getIdentifier());

			$namespace = $class->getNamespace();
			if (!empty($namespace))
			{
				$file->addNamespace($namespace);
			}
			$saveDir = $this->dir;
			$saveDir .= ((($class->getSavePath() !== null) && !empty($class->getSavePath())))
				? '/' . $class->getSavePath()
				: '';
			if ($this->createDirectory($saveDir))
			{
				$file->addClass($class);
				$file->save($saveDir);
			}
			else
			{
				throw new Exception(
					sprintf('Can\'t save class to %s folder. Make sure the folder exists!', $saveDir)
				);
			}
		}
	}


	/**
	 * Checks if the class is approved
	 * Removes the prefix and suffix for name checking
	 *
	 * @param PhpClass $class
	 *
	 * @return bool Returns true if the class is ok to add to file
	 */
	private function isValidClass(PhpClass $class)
	{
		$classNames = $this->config->get('classNames');
		return (empty($classNames) || in_array(
				$class->getIdentifier(),
				$classNames
			));
	}


	/**
	 * Save a file containing an autoloader for the generated files. Developers can include this when using the
	 * generated classes.
	 *
	 * @param string     $name    The name of the autoloader. Should be unique for the service to avoid name clashes.
	 * @param PhpClass[] $classes The classes to include in the autoloader.
	 */
	private function saveAutoloader($name, array $classes)
	{
		$autoloaderName = 'autoload_' . md5($name . $this->config->get('namespaceName'));

		// The autoloader function we build contain two parts:
		// 1. An array of generated classes and their paths.
		// 2. A check to see if the autoloader contains the argument and if so include it.
		//
		// First we generate a string containing the known classes and the paths they map to. One line for each string.
		$autoloadClasses = [];
		foreach ($classes as $class)
		{
			$className           = $class->getNamespace() . '\\' . $class->getIdentifier();
			$className           = ltrim($className, '\\');
			$saveDir             = ((($class->getSavePath() !== null) && !empty($class->getSavePath())))
				? '/' . $class->getSavePath()
				: '';
			$autoloadClasses[] = sprintf(
				"'%s' => __DIR__ . '%s/%s.php'", 
				$className, 
				$saveDir, 
				$class->getIdentifier()
			);
		}
		$autoloadClasses = implode(',' . PHP_EOL . str_repeat(' ', 8), $autoloadClasses);

		// Assemble the source of the autoloader function containing the classes and the check to include.
		// Our custom code generation library does not support generating code outside of functions and we need to
		// register the autoloader in the global scope. Consequently we manually insert a } to end the autoloader
		// function, register it and finish with a {. This means our generated code ends with a no-op {} statement.
		$autoloaderSource = <<<EOF
    \$classes = array(
        $autoloadClasses
    );
    if (!empty(\$classes[\$class])) {
        include \$classes[\$class];
    };
}

spl_autoload_register('$autoloaderName');

// Do nothing. The rest is just leftovers from the code generation.
{
EOF;

		$autoloader = new PhpFunction(null, $autoloaderName, '$class', $autoloaderSource);
		$file       = new PhpFile('autoload');
		$file->addFunction($autoloader);
		$file->save($this->dir);
	}


	/**
	 * @param string $dirName
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function createDirectory($dirName)
	{
		//Try to create output dir if non existing
		if (is_dir($dirName) == false)
		{
			if (mkdir($dirName, 0777, true) == false)
			{
				throw new Exception('Could not create output directory and it does not exist!');
			}
		}
		return true;
	}
}
