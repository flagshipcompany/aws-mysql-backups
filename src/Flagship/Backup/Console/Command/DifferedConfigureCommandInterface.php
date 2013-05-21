<?php
namespace Flagship\Backup\Console\Command;
/**
 *
 * @author pleblanc
 */
interface DifferedConfigureCommandInterface 
{
    /**
     * Will be called on DifferedConfigApp->run()
     */
    public function differedConfigure();
}
