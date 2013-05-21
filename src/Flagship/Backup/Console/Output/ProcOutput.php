<?php

namespace Flagship\Backup\Console\Output;

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Enables the execution with proc_open with nicely formatted output of stdout and stderr.
 *
 * @author pleblanc
 */
class ProcOutput extends ConsoleOutput
{

    /**
     * Executes a command and prints the stdout and sterr properly
     * 
     * @param string $command
     */
    public function exec($command)
    {
        $descriptorSpec = array(
            0 => array("pipe", "r"), // stdin
            1 => array("pipe", "w"), // stdout
            2 => array("pipe", "w"), // stderr
        );


        $resource = proc_open($command, $descriptorSpec, $pipes);

        $stdout = array();
        $stderr = array();

        while (($buffer = fgets($pipes[1], 4096)) !== false) {
            $stdout[] = trim($buffer);
        }

        while (($buffer = fgets($pipes[2], 4096)) !== false) {
            $stderr[] = trim($buffer);
        }


        //Cleanup resources
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($resource);


        foreach ($stderr as $line) {
            $this->writeln("<error>$line</error>");
        }

        foreach ($stdout as $line) {
            $this->writeln("<info>$line</info>");
        }
    }

}