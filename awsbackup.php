<?php
require_once './vendor/autoload.php';

use Flagship\Backup\Console\DifferedConfigApp;
use Flagship\Backup\Console\Command\FullMySQLBackupCommand;
use Flagship\Backup\Console\Command\S3UploadCommand;
use Flagship\Backup\Console\Helper\ConfigHolderHelper;
use Flagship\Backup\Console\Output\ProcOutput;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();


$application = new DifferedConfigApp();
$application->getDefinition()->addOption(new InputOption('--config', '-c', InputOption::VALUE_REQUIRED, 'Configuration file path', './config.json'));

$application->getHelperSet()->set(new ConfigHolderHelper($input->getParameterOption(array('--config', '-c'), './config.json')));

$application->add(new FullMySQLBackupCommand());

$application->add(new S3UploadCommand());


$application->run($input, new ProcOutput());