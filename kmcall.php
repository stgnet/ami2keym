<?php

    date_default_timezone_set('America/New_York');

    function readcsv($file)
    {
        $records=array();
        $fp=fopen($file,"r");
        $header=fgetcsv($fp);
        while ($row=fgetcsv($fp))
        {
            $record=array();
            $index=0;
            foreach ($row as $data)
            {
                if (empty($header[$index]))
                    $header[$index]="COL$index";
                $record[$header[$index]]=$data;
                $index++;
            }
            $records[]=$record;
        }
        return($records);
    }

    if (empty($argv[1]))
    {
        exit(json_encode(array('error'=>"no data provided")));
    }
    $data=(array)json_decode($argv[1]);
    if (empty($data))
    {
        exit(json_encode(array('error'=>"no json encoded data provided")));
    }

    $kmcsv=readcsv("keymetric.csv");
    $keymetric_config=$kmcsv[0];

    $calldata=array_merge(array('Vendor'=>$keymetric_config['vendor']),$data);

    $wsdl=$keymetric_config['server'];
    if (empty($wsdl))
    {
        exit(json_encode(array('error'=>"Simulated AddCall() with ".print_r($data,true))));
    }

/*
        <UserAuthentication xmlns="http://webservice.keymetric.net/api/v1">
            <CustomerId>12345</CustomerId>
            <UserId>api@example.com</UserId>
            <Password>password123</Password>
        </UserAuthentication>
*/
    $header=array(
        'CustomerId'=>$keymetric_config['customerid'],
        'UserId'=>$keymetric_config['userid'],
        'Password'=>$keymetric_config['password']
    );

    try
    {
        $soap_client=new SoapClient($wsdl,
            array('trace'=>1,'exception'=>1)); //$wsdl,$header);
    }
    catch (Exception $e)
    {
        exit(json_encode(array('error'=>"Failure in SoapClient($wsdl): ".$e->getMessage())));
    }

    try
    {
        $soap_client->__setSoapHeaders(
            new SOAPHeader("http://webservice.keymetric.net/api/v1", 'UserAuthentication', $header)
        ); 
    }
    catch (Exception $e)
    {
        exit(json_encode(array('error'=>"Failure in setSoapHeaders(): ".$e->getMessage())));
    }

    try
    {
        $result=$soap_client->AddCall($calldata);
    }
    catch (Exception $e)
    {
        exit(json_encode(array('error'=>"Failure in AddCall(): ".$e->getMessage())));
    }

    exit(json_encode(array('result'=>$result)));

