<?php
namespace Flagship\Backup\Console;

use Flagship\Backup\Console\Command\DifferedConfigureCommandInterface;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * This app run a differed configuration on run time. This allows the app to be 
 * loaded in each command nefore excuting the differed config.
 * 
 * @author pleblanc
 */
class DifferedConfigApp extends Application
{
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        foreach($this->all() as $command){
            if($command instanceof DifferedConfigureCommandInterface)
                $command->differedConfigure();
        }
        
        parent::run($input, $output);
    }
}
