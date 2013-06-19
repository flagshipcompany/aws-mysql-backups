<?php
namespace Flagship\Backup\Console\Helper;

use Symfony\Component\Console\Helper\Helper;

class ConfigHolderHelper extends Helper implements \ArrayAccess
{
    protected $config;

    public function __construct($configPath)
    {
      $this->config = json_decode(file_get_contents($configPath),true);

    }
    public function getName()
    {
        return 'configHolder';
    }

    public function offsetExists($offset)
    {
        return exist($this->config[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->config[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->config[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->config[$offset]);
    }

}
