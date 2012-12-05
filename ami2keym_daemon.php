<?php

require_once 'poof/poof.php';

date_default_timezone_set('America/New_York');

require_once 'log4php/Logger.php';

require_once 'PAMI/Autoloader/Autoloader.php';  

PAMI\Autoloader\Autoloader::register();

use PAMI\Client\Impl\ClientImpl as PamiClient;  
use PAMI\Message\Event\EventMessage;
use PAMI\Listener\IEventListener;

class ami2keym_daemon extends pfDaemonServer
{
    private $pami;
    private $active_calls;

    function __construct()
    {
        // do NOT timeout this daemon
        $this->SetTimeout(0);

        $this->pami=false;

        $pamiClientOptions = array(  
            'log4php.properties' => realpath(__DIR__).DIRECTORY_SEPARATOR.'log4php.properties',
            'host' => 'apbx7.axiapbx.com', 
            'scheme' => 'tcp://', 
            'port' => 5038, 
            'username' => 'scott', 
            'secret' => 'integrate',
            'connect_timeout' => 10000,
            'read_timeout' => 10000       
        );
 
        $this->pami = new PamiClient($pamiClientOptions);  

        // Open the connection  
        $this->pami->open();  

        $this->pami->registerEventListener(function(EventMessage $event)
        {
            global $calls;

            $keys=$event->getKeys();

            if (empty($keys['uniqueid']))
                return;

            $uid=$keys['uniqueid'];

            if (empty($calls[$uid]))
                $calls[$uid]=array();

            foreach ($keys as $name => $value)
            {
                if (empty($calls[$uid][$name]))
                    $calls[$uid][$name]=$value;
                $calls[$uid][$name.'-last']=$value;
            }

            if ($keys['event']=="Hangup")
            {
                print_r($calls[$uid]);
                unset($calls[$uid]);
            }

        });

        // initialize the daemon interface (this does NOT return)
        parent::__construct('ami2keym');

    }
    function _Process()
    {
        if ($this->pami)
        $this->pami->process();
    }
    function __destruct()
    {
        // Close the connection  
        if ($this->pami)
            $this->pami->close();  
    }
    function GetCalls()
    {
        global $calls;
        return($calls);
    }
}

if (!empty($argv[1]) && $argv[1]=="-daemon") new ami2keym_daemon();

