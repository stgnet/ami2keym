<?php
    require 'poof/poof.php';
    require 'navbar.php';

    $amiconfig_fields=array(
        'key'=>array('type'=>"hidden"),
        'server'=>array('type'=>"text",'desc'=>"Server"),
        'username'=>array('type'=>"text",'desc'=>"Username"),
        'password'=>array('type'=>"password",'desc'=>"Password"),
        //'submit'=>array('type'=>"button",'value'=>"SAVE")
    );

    $keymetric_fields=array(
        'key'=>array('type'=>"hidden"),
        'server'=>array('type'=>"text",'desc'=>"Server"),
        'customerid'=>array('type'=>"text",'desc'=>"Customer ID"),
        'userid'=>array('type'=>"text",'desc'=>"User ID"),
        'password'=>array('type'=>"password",'desc'=>"Password"),
        'vendor'=>array('type'=>"text",'desc'=>"Vendor")
    );

    $amiconfig_edit=array_merge($amiconfig_fields,array(
        'submit'=>array('type'=>"button",'value'=>"SAVE")
    ));

    $keymetric_edit=array_merge($keymetric_fields,array(
        'submit'=>array('type'=>"button",'value'=>"SAVE")
    ));


    $amiconfig_db=dbCsv("amiconfig.csv")->SetFields($amiconfig_fields,'key');

    $amiconfig_record=$amiconfig_db->Lookup(array('key'=>1));
    if (!$amiconfig_record)
    {
        $amiconfig_db->Insert(array('key'=>1));
        $amiconfig_record=$amiconfig_db->Lookup(array('key'=>1));
    }

    $keymetric_db=dbCsv("keymetric.csv")->SetFields($keymetric_fields,'key');

    $keymetric_record=$keymetric_db->Lookup(array('key'=>1));
    if (!$keymetric_record)
    {
        $keymetric_db->Insert(array('key'=>1));
        $keymetric_record=$keymetric_db->Lookup(array('key'=>1));
    }

    $amiconfig_target=uiDiv();

    $keymetric_target=uiDiv();

    $amiconfig_post=function($data)
    {
        global $amiconfig_db;
        $amiconfig_db->update($data);
        echo uiDiv("alert alert-success")->Add("Saved");
    };

    $keymetric_post=function($data)
    {
        global $keymetric_db;
        $keymetric_db->update($data);
        echo uiDiv("alert alert-success")->Add("Saved");
    };

    echo uiPage("Configuration")->Add(
        $navbar,
        uiContainer()->Add(
            uiWell()->Add(
                uiLegend("AMI Configuration"),
                uiForm($amiconfig_edit,$amiconfig_record,"horizontal")
                    ->OnSubmit($amiconfig_target)
                    ->Post($amiconfig_post),
                $amiconfig_target
            ),
            uiWell()->Add(
                uiLegend("Keymetric Configuration"),
                uiForm($keymetric_edit,$keymetric_record,"horizontal")
                    ->OnSubmit($keymetric_target)
                    ->Post($keymetric_post),
                $keymetric_target
            )
        )
    );

