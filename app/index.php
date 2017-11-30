<?php

require __DIR__ .  '/../vendor/autoload.php';

use Tutu\Wsdl2PhpGenerator\Generator\Generator;
use Tutu\Wsdl2PhpGenerator\Config\Config;

// create an instance of generator
$generator = new Generator();

// generate DTO classes from provided config
$generator->generate(
	new Config(
//		[
//			'inputFile'                      => __DIR__ . '/wsdls/TravelItineraryReadRQ/TravelItineraryReadRQ3.9.0.wsdl',
//			'outputDir'                      => __DIR__ . '/tmp/output/Tir390',
//			'namespaceName'                  => 'Sabre\Avia\TravelItineraryRead',
//			'sharedTypes'                    => true,
//			'constructorParamsDefaultToNull' => true,
//			'baseComplexClass'               => '\Sabre\Avia\Base\AbstractBaseModel',
//		]
		[
			'inputFile'                      => __DIR__ . '/wsdls/BargainFinderMaxRQ/BargainFinderMaxRQ_GIR_v3.2.0.wsdl',
			'outputDir'                      => __DIR__ . '/tmp/output/Bfm320',
			'namespaceName'                  => 'Sabre\Avia\BargainFinderMax',
			'sharedTypes'                    => true,
			'constructorParamsDefaultToNull' => true,
			'baseComplexClass'               => '\Sabre\Avia\Base\AbstractBaseModel',
		]
	)
);

