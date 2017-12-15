<?php

namespace Tutu\Wsdl2PhpGenerator\Service;

use Tutu\Wsdl2PhpGenerator\WsdlHandler\Struct;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Enum;
use Tutu\Wsdl2PhpGenerator\WsdlHandler\Pattern;

/**
 * Class Operation
 *
 * @package Tutu\Wsdl2PhpGenerator\Service
 */
class Operation
{
	/**
	 * @var string The name of the operation
	 */
	private $name;

	/**
	 * @var array An array with Variables
	 * @see Variable
	 */
	private $params;

	/**
	 * @var string A description of the operation
	 */
	private $description;

	/**
	 * @var string A description of the operation
	 */
	private $returns;


	/**
	 *
	 * @param string $name
	 * @param string $paramStr The parameter string for a operation from the wsdl
	 * @param string $description
	 * @param string $returns
	 */
	public function __construct($name, $paramStr, $description, $returns)
	{
		$this->name        = $name;
		$this->params      = [];
		$this->description = $description;
		$this->returns     = $returns;

		$this->generateParams($paramStr);
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}


	/**
	 * @return string
	 */
	public function getReturns()
	{
		return $this->returns;
	}


	/**
	 * @param array $validTypes An array of Type objects with valid types for type hinting
	 *
	 * @return string A parameter string
	 */
	public function getParamString(array $validTypes)
	{
		$params = [];

		foreach ($this->params as $value => $typeHint)
		{
			$ret = '';

			// Array or complex types is valid type hints
			if ($typeHint == 'array')
			{
				$ret .= $typeHint . ' ';
			}
			else
			{
				foreach ($validTypes as $type)
				{
					if ($type instanceof Struct)
					{
						if ($typeHint == $type->getClassName())
						{
							$ret .= $typeHint . ' ';
							break;
						}
					}
				}
			}

			$ret .= $value;

			if (strlen(trim($ret)) > 0)
			{
				$params[] = $ret;
			}
		}

		return implode(', ', $params);
	}


	/**
	 *
	 * @param string $name The param to get
	 * @param array  $validTypes An array of Type objects with valid types for type hinting
	 *
     * @return array A array with three keys like
     *               'type' => the type hint to use
     *               'name' => the name of the param and
     *               'desc' => A description of the param
	 */
	public function getPhpDocParams($name, array $validTypes)
	{
		$ret = [];

		$ret['desc'] = '';

		$paramType = '';
		foreach ($this->params as $value => $typeHint)
		{
			if ($name == $value)
			{
				$paramType = $typeHint;
			}
		}

		$ret['type'] = $paramType;

		foreach ($validTypes as $type)
		{
			if ($paramType == $type->getName())
			{
				if ($type instanceof Pattern)
				{
					$ret['type'] = $type->getDataType();
					$ret['desc'] = 'Restriction pattern: ' . $type->getValue();
				}
				else
				{
					$ret['type'] = $type->getName();

					if ($type instanceof Enum)
					{
						$ret['desc'] =
							'Constant: ' . $type->getClassName() . PHP_EOL . 'Valid values: ' . $type->getValidValues();
					}
				}
			}
		}

		$ret['name'] = $name;

		return $ret;
	}


	/**
	 *
	 * @return string A parameter string
	 */
	public function getParamStringNoTypeHints()
	{
		return implode(', ', array_keys($this->params));
	}


	/**
	 * @return array Returns the parameter array
	 */
	public function getParams()
	{
		return $this->params;
	}


	/**
	 *
	 * @param string $paramStr A comma separated list of parameters with optional type hints
	 */
	private function generateParams($paramStr)
	{
		$this->params = [];

		foreach (explode(', ', $paramStr) as $param)
		{
			$arr = explode(' ', $param);

			// Check if we have type hint. 1 = no type hint
			if (count($arr) == 1)
			{
				if (strlen($arr[0]) > 0)
				{
					$this->params[$arr[0]] = '';
				}
			}
			else
			{
				$this->params[$arr[1]] = $arr[0];
			}
		}
	}
}
