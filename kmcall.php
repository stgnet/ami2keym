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
        $soap_client=new SoapClient($wsdl,array(
//            'soap_version'=>SOAP_1_2,
            'trace'=>1,
            'exception'=>true,
//            'cache_wsdl'=>WSDL_CACHE_NONE,
//            'features'=>SOAP_SINGLE_ELEMENT_ARRAYS
        )); //$wsdl,$header);
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
        /*
        $params=array();
        foreach ($calldata as $name => $value)
            $params[]=new SoapParam($value,$name);

        //$result=$soap_client->AddCall(array($params));
        $result=$soap_client->__soapCall("AddCall",$params);
        */
        $result=$soap_client->__soapCall("AddCall",array('AddCall'=>array('Call'=>$calldata)));
    }
    catch (Exception $e)
    {
        exit(json_encode(array('error'=>"Failure in AddCall(): ".$e->getMessage())));
    }

    exit(json_encode(array('result'=>$result)));

