<?php

namespace Tutu\Wsdl2PhpGenerator\PhpSource;

use Exception;

/**
 * Class PhpDocElementFactory
 *
 * @package Tutu\Wsdl2PhpGenerator\PhpSource
 */
class PhpDocElementFactory
{
	/**
	 * Creates a param element
	 *
	 * @param string $dataType The name of the dataType of the variable
	 * @param string $name     The name of the variable
	 * @param string $description
	 *
	 * @throws Exception Throws exception if no name is supplied
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getParam($dataType, $name, $description)
	{
		if (strlen($name) == 0)
		{
			throw new Exception('A parameter must have a name!');
		}

		if ($name[0] == '$')
		{
			$name = substr($name, 1);
		}
		if ($dataType == 'long')
		{
			$dataType = 'int';
		}
		elseif ($dataType == 'double')
		{
			$dataType = 'float';
		}

		return new PhpDocElement('param', $dataType, $name, $description);
	}


	/**
	 * Creates a throws element
	 *
	 * @param string $exception
	 * @param string $description
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getThrows($exception, $description)
	{
		return new PhpDocElement('throws', $exception, '', $description);
	}


	/**
	 * Creates a throws element
	 *
	 * @param string $dataType    The name of the dataType
	 * @param string $name        The name of the variable
	 * @param string $description Description of the variable
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getVar($dataType, $name, $description)
	{
		return new PhpDocElement('var', $dataType, null, $description);
	}


	/**
	 * Creates a access element
	 *
	 * @param string $dataType The name of the dataType
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getAccess($dataType)
	{
		return new PhpDocElement('access', $dataType, '', '');
	}


	/**
	 * Creates a access element with the access public
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getPublicAccess()
	{
		return new PhpDocElement('access', 'public', '', '');
	}


	/**
	 * Creates a access element with the access private
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getPrivateAccess()
	{
		return new PhpDocElement('access', 'private', '', '');
	}


	/**
	 * Creates a access element with the access protected
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getProtectedAccess()
	{
		return new PhpDocElement('access', 'protected', '', '');
	}


	/**
	 * Creates a package element
	 *
	 * @param string $package The name of the package
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getPackage($package)
	{
		return new PhpDocElement('package', $package, '', '');
	}


	/**
	 * Creates a author element
	 *
	 * @param string $author The name of the author
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getAuthor($author)
	{
		return new PhpDocElement('author', $author, '', '');
	}


	/**
	 * Creates a return element
	 *
	 * @param string $dataType    The name of the dataType
	 * @param string $description The description of the return value
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getReturn($dataType, $description)
	{
		return new PhpDocElement('return', $dataType, '', $description);
	}


	/**
	 * Creates a abstract element
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getAbstract()
	{
		return new PhpDocElement('abstract', '', '', '');
	}


	/**
	 * Creates a final element
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getFinal()
	{
		return new PhpDocElement('final', '', '', '');
	}


	/**
	 * Creates a deprecated element
	 *
	 * @param string $information The description of why the element is deprecated etc.
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getDeprecated($information = '')
	{
		return new PhpDocElement('deprecated', '', '', $information);
	}


	/**
	 * Creates a licence element
	 *
	 * @param string $information Information about the licence
	 *
	 * @return PhpDocElement The created element
	 */
	public static function getLicence($information)
	{
		return new PhpDocElement('licence', '', '', $information);
	}
}
