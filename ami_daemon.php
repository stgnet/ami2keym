<?php

    date_default_timezone_set('America/New_York');

    require_once 'log4php/Logger.php';

    require_once 'PAMI/Autoloader/Autoloader.php';  

    PAMI\Autoloader\Autoloader::register();

    use PAMI\Client\Impl\ClientImpl as PamiClient;  
    use PAMI\Message\Event\EventMessage;
    use PAMI\Listener\IEventListener;

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
 
    $pamiClient = new PamiClient($pamiClientOptions);  
 
    // Open the connection  
    $pamiClient->open();  

    $pamiClient->registerEventListener(function(EventMessage $event){
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

    while (1)
    {
        $pamiClient->process();
        usleep(1000);
    }
 
    // Close the connection  
    $pamiClient->close();  

