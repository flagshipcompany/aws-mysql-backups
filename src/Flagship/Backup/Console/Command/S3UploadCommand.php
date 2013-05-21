<?php

namespace Flagship\Backup\Console\Command;

use Aws\S3\S3Client;
use Aws\Common\Enum\Region;
use Aws\S3\Enum\CannedAcl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of FullMySQLBackupCommand
 *
 * @author pleblanc
 */
class S3UploadCommand extends Command
{

    protected function configure()
    {
        $this->setName('aws:s3upload')
            ->setDescription('Uploads a file to a S3 bucket')
            ->addArgument(
                'filename', InputArgument::REQUIRED, 'What file needs to be uploaded?')
            ->addArgument('bucket-name', InputArgument::REQUIRED, 'To which buck should it be uploaded? ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->getApplication()->getHelperSet()->get('configHolder');

        $awsConfig = $config['aws-settings'];


        $client = S3Client::factory(array(
                'key' => $awsConfig['S3Key'],
                'secret' => $awsConfig['S3Secret'],
        ));


        $awsOutput = $client->putObject(array(
            'Bucket' => $input->getArgument('bucket-name'),
            'Key' => basename($input->getArgument('filename')),
            'Body' => \Guzzle\Http\EntityBody::factory(fopen($input->getArgument('filename'), 'r')),
            'ACL' => CannedAcl::PRIVATE_ACCESS,
            'ServerSideEncryption' => 'AES256',
            'ContentType' => mime_content_type($input->getArgument('filename'))
        ));

        foreach ($awsOutput as $text) {
            $output->writeln($text);
        }
    }

}
