<?php

require __DIR__ .  '/../vendor/autoload.php';

use Tutu\Wsdl2PhpGenerator\Generator\Generator;
use Tutu\Wsdl2PhpGenerator\Config\Config;


//build new config
$config = new Config(
//		[
//			'inputFile'                      => __DIR__ . '/wsdls/TravelItineraryReadRQ/TravelItineraryReadRQ3.9.0.wsdl',
//			'outputDir'                      => __DIR__ . '/tmp/output/Tir390',
//			'namespaceName'                  => 'Sabre\Avia\TravelItineraryRead',
//			'sharedTypes'                    => true,
//			'constructorParamsDefaultToNull' => true,
//			'baseComplexClass'               => '\Sabre\Avia\Base\AbstractBaseModel',
//		]
	[
		Config::PACKAGE_NAMESPACE        => 'Sabre\Avia\BargainFinderMax',
		Config::BASE_EXTEND_CLASS        => '\Sabre\Avia\Base\AbstractBaseModel',
		Config::INPUT_FILE               => __DIR__ . '/wsdls/BargainFinderMaxRQ/BargainFinderMaxRQ_GIR_v3.2.0.wsdl',
		Config::OUTPUT_DIRECTORY         => __DIR__ . '/tmp/output/Bfm320',
		Config::SHARED_TYPES             => true,
		Config::CONSTRUCTOR_NULL_PARAMS  => true,
	]
);

// create an instance of generator
$generator = new Generator($config);

// generate DTO classes from provided config
$generator->generate();

