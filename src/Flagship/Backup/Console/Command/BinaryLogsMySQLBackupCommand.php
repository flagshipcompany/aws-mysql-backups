<?php

namespace Flagship\Backup\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Flagship\Backup\Console\Output\ProcOutput;

/**
 * Description of FullMySQLBackupCommand
 *
 * @author pleblanc
 */
class BinaryLogsMySQLBackupCommand extends Command implements DifferedConfigureCommandInterface
{

    protected function configure()
    {
        $this->setName('mysql:binary-logs-backup')
        ->setDescription('Flush the binary logs and backup the older one');

        //delay arguments and options settings since we require helpers.
    }

    public function differedConfigure()
    {
        $config = $this->getApplication()->getHelperSet()->get('configHolder');

        $mysqlConf = $config['command-settings']['mysql-binlogs-backup'];

        $this
        ->addArgument('filename', InputArgument::OPTIONAL, 'What is the name of the binary log?', $mysqlConf['filename'])
        ->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'What user is able to flush the logs?', $mysqlConf['db-user'])
        ->addOption('db-pass', null, InputOption::VALUE_OPTIONAL, 'The password for the user', $mysqlConf['db-pass'])
        ->addOption('backup-path', null, InputOption::VALUE_OPTIONAL, 'To which path the backup will be generated', $mysqlConf['backup-path'])
        ->addOption('aws-bucket', null, InputOption::VALUE_OPTIONAL, 'To which S3 bucket the backup will be sent', $mysqlConf['aws-bucket'])
        ->addOption('backups-cleanup', null, InputOption::VALUE_OPTIONAL, 'Delete all full backups older than X days', $mysqlConf['backups-cleanup'])
        ->addOption('aws', null, InputOption::VALUE_NONE, 'Push the backup to AWS, provided it is properly configured');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /**
         * We're getting the most recent file that matches our pattern. This is the current binary log.
         * Once the flush logs command will be executed, this log will be gzip and backup.
         */
        $files = glob($input->getArgument('filename') . '*', GLOB_NOSORT);
        array_multisort(array_map('filemtime', $files), SORT_NUMERIC, SORT_DESC, $files);

        $toBackup = $files[0];

        $dbuser = $input->getOption('db-user');
        $dbpass = $input->getOption('db-pass');

        $dbBackupsPath = $input->getOption('backup-path');

        if (!is_dir($dbBackupsPath)) {
            mkdir($dbBackupsPath);
        }

        $backupName = date('Ymd_His') . '_binlog_backup.sql.gz';

        $gzipPath = $dbBackupsPath . DIRECTORY_SEPARATOR . $backupName;

        $dbpass = $dbpass != "" ? "-p$dbpass" : '';

        $commandString = "mysqladmin -u $dbuser $dbpass flush-logs && gzip -c $toBackup > $gzipPath";

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
        $files = glob($dbBackupsPath . '/*_binlog_backup.sql.gz');

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
