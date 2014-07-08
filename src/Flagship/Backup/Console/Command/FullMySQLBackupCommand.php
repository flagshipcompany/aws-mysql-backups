<?php

namespace Flagship\Backup\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Flagship\Backup\Console\Output\ProcOutput;

/**
 * Description of FullMySQLBackupCommand
 *
 * @author pleblanc
 */
class FullMySQLBackupCommand extends Command implements DifferedConfigureCommandInterface
{

    protected $s3Command;

    protected function configure()
    {
        $this->setName('mysql:fullbackup')
            ->setDescription('Makes a complete Datadump of mysql and uploads it to an AWS bucket');

        //delay arguments and options settings since we require helpers.
    }

    public function differedConfigure()
    {
        $config = $this->getApplication()->getHelperSet()->get('configHolder');

        $mysqlConf = $config['command-settings']['mysql-full-backup'];

        $this
            ->addArgument('db', InputArgument::REQUIRED, 'What db to backup?')
            ->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'What user are we going to use on mysqldump?', $mysqlConf['db-user'])
            ->addOption('db-pass', null, InputOption::VALUE_OPTIONAL, 'The password for the user', $mysqlConf['db-pass'])
            ->addOption('backup-path', null, InputOption::VALUE_OPTIONAL, 'To which path the backup will be generated', $mysqlConf['backup-path'])
            ->addOption('aws-bucket', null, InputOption::VALUE_OPTIONAL, 'To which S3 bucket the backup will be sent', $mysqlConf['aws-bucket'])
            ->addOption('backups-cleanup', null, InputOption::VALUE_OPTIONAL, 'Delete all full backups older than X days', $mysqlConf['backups-cleanup'])
            ->addOption('aws', null, InputOption::VALUE_NONE, 'Push the backup to AWS, provided it is properly configured');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        //$this->s3Command = $this->getApplication()->find('S3:Upload');
        $dbName = $input->getArgument('db');

        $dbuser = $input->getOption('db-user');
        $dbpass = $input->getOption('db-pass');
        $dbBackupsPath = $input->getOption('backup-path');
        $backupName = date('Ymd_His') . '_' . $dbName . '_fullbackup.sql.gz';

        $gzipPath = $dbBackupsPath . DIRECTORY_SEPARATOR . $backupName;

        $dbpass = $dbpass != "" ? "-p$dbpass" : '';

        $commandString = "mysqldump -u $dbuser $dbpass --add-drop-database --add-drop-table --lock-all-tables --databases $dbName | gzip > $gzipPath";

        if ($output instanceof ProcOutput) {
            $output->exec($commandString);
        } else {
            system($commandString);
        }

        $this->executeAws($input, $output, $gzipPath);

        $this->executeCleanup($input, $output, $dbBackupsPath);
    }

    protected function executeAws($input, $output, $gzipPath)
    {
        if ($input->getOption('aws')) {
            $command = $this->getApplication()->find('aws:s3upload');

            $arguments = array(
                'command' => 'aws:s3upload',
                'filename' => $gzipPath,
                'bucket-name' => $input->getOption('aws-bucket'),
            );

            $input = new ArrayInput($arguments);

            $command->run($input, $output);
        }
    }

    protected function executeCleanup($input, $output, $dbBackupsPath)
    {
        $files = glob($dbBackupsPath . '/*_fullbackup.sql.gz');

        $dateToFlush = strtotime("-{$input->getOption('backups-cleanup')} DAYS");

        $toDelete = array_filter(
            $files,
            function ($var) use ($dateToFlush) {
                return (filemtime($var) < $dateToFlush);
            }
        );

        array_map(
            function ($filename) {
                unlink($filename);
            },
            $toDelete
        );
    }
}
