<?php

// framework library (for daemon class)
require_once 'poof/poof.php';

// prevent logger from throwing timezone error
date_default_timezone_set('UTC');

// Asterisk interface and logger prerequisite
require_once 'log4php/Logger.php';
require_once 'PAMI/Autoloader/Autoloader.php';  
PAMI\Autoloader\Autoloader::register();
use PAMI\Client\Impl\ClientImpl as PamiClient;  
use PAMI\Message\Event\EventMessage;
use PAMI\Listener\IEventListener;

// number matching algorithm
require_once 'match-number.php';


// pass call detail to keymetric
function keymetric_call($data)
{
    global $keymetric_status;

    $in=json_encode($data);
    $out=shell_exec("php kmcall.php '$in'");
    if ($out[0]!='{')
        Fatal("invalid output from kmcall: '$out'");
    $result=(array)json_decode($out);
    if (!empty($result['error']))
        $keymetric_status="SOAP ERROR: ".$result['error'];
    else
        $keymetric_status="Result: ".print_r($result,true);
    return($result);
}

// main daemon that processes AMI events into KeyMetric SOAP push
class ami2keym_daemon extends pfDaemonServer
{
    private $pami;
    private $pami_status;

    function AmiEvent(EventMessage $event)
    {
        global $calls;
        global $keymetric_config;

        // get the array of values from the event
        $keys=$event->getKeys();

        if (empty($keys['uniqueid']))
            return;

        $uid=$keys['uniqueid'];
        $keys['time']=time();

        if (empty($calls[$uid]))
            $calls[$uid]=array();

        // accumulate first instance and last values
        foreach ($keys as $name => $value)
        {
            if (empty($calls[$uid][$name]))
                $calls[$uid][$name]=$value;
            $calls[$uid][$name.'-last']=$value;
        }

        // when the call completes, process it
        if ($keys['event']=="Hangup")
        {
            $call=$calls[$uid];
            //file_put_contents("sample.txt",print_r($call,true));
            if (!empty($call['extension']) && match_number($call['extension']))
            {
                $callstart=date('Y-m-d\TH:i:s\Z',$call['time']);
                $duration=$call['time-last']-$call['time'];
                $data=array(
                    'Vendor'=>$keymetric_config['vendor'],
                    'VendorCallId'=>$call['uniqueid'],
                    'CallStart'=>$callstart,
                    'Duration'=>$duration,
                    'CallerID'=>$call['calleridnum'],
                    'DialedNumber'=>$call['extension'],
                    'CallStatus'=>$call['context-last'],
                    'CallerName'=>$call['calleridname'],
                );

                try
                {
                    keymetric_call($data);
                }
                catch (Exception $e)
                {
                    $this->pami_status="Failure in keymetric_call(): ".$e->getMessage();
                }
            }
            // remove call from active list
            unset($calls[$uid]);
        }
    
    }

    // simulate call through event handling
    function test()
    {
        global $db_did;
        global $keymetric_config;

        // force loading of db_did;
        match_number('12345');

        $didlist=$db_did->records();
        if (empty($didlist))
        {
            $this->pami_status="Error: cannot test without a number in DID list";
            return;
        }
        $entry=array_rand($didlist);

        $number="3175550155"; //$entry['number'];

        $uniqueid=md5(rand());

                $callstart=date('Y-m-d\TH:i:s\Z',time()-61);
                $duration=60;
                $data=array(
                    'Vendor'=>$keymetric_config['vendor'],
                    'VendorCallId'=>$uniqueid,
                    'CallStart'=>$callstart,
                    'Duration'=>$duration,
                    'CallerID'=>"+13175550123",
                    'DialedNumber'=>$number,
                    'CallStatus'=>"from-pstn",
                    'CallerName'=>"TEST CALL",
                );

                try
                {
                    keymetric_call($data);
                }
                catch (Exception $e)
                {
                    $this->pami_status="Failure in keymetric_call(): ".$e->getMessage();
                }

    }


    // when START button is pressed on web ui
    function start()
    {
        global $keymetric_config;

        // do not let the daemon timeout and shutdown
        $this->SetTimeout(0);

        // save state of engine for restart of daemon
        file_put_contents("state.txt",true);

        // reload load soap config on restart
        $keymetric_config=dbCsv("keymetric.csv")->Lookup(array('key'=>1));

        // don't attempt to start if already running
        if ($this->pami) return;

        $this->pami_status="Starting...";

        $ami_config=dbCsv('amiconfig.csv')->Lookup(array('key'=>1));

        if (!$ami_config || 
            empty($ami_config['server']) ||
            empty($ami_config['username']) ||
            empty($ami_config['password']))
        {
            $this->pami_status="Missing AMI configuration";
            return;
        }

        $pamiClientOptions = array(  
            'log4php.properties' => realpath(__DIR__).DIRECTORY_SEPARATOR.'log4php.properties',
            'host' => $ami_config['server'], 
            'scheme' => 'tcp://', 
            'port' => 5038, 
            'username' => $ami_config['username'], 
            'secret' => $ami_config['password'],
            'connect_timeout' => 10000,
            'read_timeout' => 10000       
        );

        try
        {
            $this->pami = new PamiClient($pamiClientOptions);  

            // Open the connection  
            $this->pami->open();  

            // add handler for Asterisk events
            $this->pami->registerEventListener(array($this,'AmiEvent'));
            
            $this->pami_status="Running";
        }
        catch (Exception $e)
        {
            $this->pami_status="AMI ERROR: ".$e->getMessage();
            $this->pami=false;
        }
    }
    // Status display above start/stop buttons
    public function status()
    {
        global $keymetric_status;
        return($this->pami_status."\n".$keymetric_status);
    }
    // STOP button on web ui
    public function stop()
    {
        file_put_contents("state.txt",false);

        if ($this->pami)
            $this->pami->close();
        $this->pami_status="Stopped";
        $this->pami=false;

        // allow the daemon to timeout and shutdown
        $this->SetTimeout(1);
    }

    // initialize the daemon
    function __construct()
    {

        $this->pami=false;
        $this->pami_status="not initialized";

        global $calls;
        $calls=false;

        $state=false;
        if (file_exists('state.txt'))
            $state=file_get_contents('state.txt');
        //if ($state)
        //    $this->start();

        // initialize the daemon interface (this does NOT return)
        parent::__construct('ami2keym');
    }
    // called every second to process AMI events
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

    // Activity box on status page on web ui
    function GetCalls()
    {
        global $calls;

        if (empty($calls))
            return(array());

        $data=array();
        foreach ($calls as $call)
        {
            if (empty($call['calleridnum']) && empty($call['extension']))
                continue;

            $row['CID']=(empty($call['calleridnum'])?'':$call['calleridnum'])." ".
                (empty($call['calleridname'])?'':$call['calleridname']);
            $row['DID']=empty($call['extension'])?'':$call['extension'];
            $row['Context']=empty($call['context-last'])?'':$call['context-last'];
            $row['Application']=empty($call['application-last'])?'':$call['application-last'];

            $row['Match']=match_number($row['DID'])?"Yes":"No";

            $data[]=$row;
        }
        return($data);
    }
}

if (!empty($argv[1]) && $argv[1]=="-daemon") new ami2keym_daemon();

