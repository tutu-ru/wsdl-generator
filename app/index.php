<?php

require __DIR__ . '/../vendor/autoload.php';

use Tutu\Wsdl2PhpGenerator\Generator\Generator;
use Tutu\Wsdl2PhpGenerator\Config\Config;

class AppLogger extends \Psr\Log\AbstractLogger
{
	/**
	 * @var string
	 */
	public $logDir;
	public $logFilename = '/logs.txt';


	public function __construct($logDir = __DIR__ . '/log')
	{
		$this->logDir = $logDir;
		unlink($this->logDir . $this->logFilename);
	}


	/**
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 */
	public function log($level, $message, array $context = [])
	{
		file_put_contents(
			$this->logDir . $this->logFilename,
			sprintf(
				'%s - %s - %s' . PHP_EOL . '%s',
				date('Y-m-d H:i:s'),
				$level,
				$message,
				((count($context)) ? json_encode($context) . PHP_EOL : '')
			),
			FILE_APPEND
		);
	}
}


//build new config
$config = new Config(
	[
		Config::PACKAGE_NAMESPACE       => 'Avia\Gate\Sabre\Wsdl\TravelItineraryRead',
		Config::BASE_EXTEND_CLASS       => '\Avia\Gate\Sabre\Wsdl\Base\AbstractBaseModel',
		Config::INPUT_FILE              => __DIR__ . '/wsdls/TravelItineraryReadRQ/TravelItineraryReadRQ3.9.0.wsdl',
		Config::OUTPUT_DIRECTORY        => __DIR__ . '/tmp/output/Lib390',
		Config::SHARED_TYPES            => true,
		Config::CONSTRUCTOR_NULL_PARAMS => true,
		Config::VERBOSE                 => true,
	]
//	[
//		Config::PACKAGE_NAMESPACE       => 'Avia\Gate\Sabre\Wsdl\Search',
//		Config::BASE_EXTEND_CLASS       => '\Avia\Gate\Sabre\Wsdl\Base\AbstractBaseModel',
//		Config::INPUT_FILE              => __DIR__ . '/wsdls/BargainFinderMaxRQ/BargainFinderMaxRQ_GIR_v3.2.0.wsdl',
//		Config::OUTPUT_DIRECTORY        => __DIR__ . '/tmp/output/Bfm320',
//		Config::SHARED_TYPES            => true,
//		Config::CONSTRUCTOR_NULL_PARAMS => true,
//		Config::VERBOSE                 => true,
//	]
);

// create an instance of generator
$generator = new Generator($config);
$generator->setLogger(new AppLogger());

// generate DTO classes from provided config
$generator->generate();

