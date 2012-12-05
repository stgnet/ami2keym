<?php
    require 'poof/poof.php';
    require 'navbar.php';


    $fields=array(
        'number'=>array('type'=>"text",'desc'=>"Number"),
    );

    $db_did=dbCsv("did.csv")->SetFields($fields,'key');

    function match_number($number)
    {
        global $db_did;

        foreach ($db_did->records() as $record)
        {
            if (empty($record['number']))
                continue;

            $len=strlen($record['number']);
            if (substr($number,-$len)==$record['number'])
                return(true);
        }
        return(false);
    }

    $help1="Enter a number to check in list.";
    $help2="Inbound calls to destination numbers listed here will be transmitted to KeyMetrics.
    Phone numbers will be matched on right justified values, thus 1234 will match call to 5551234.";

    $testform=array(
        'number'=>array('type'=>"text",'desc'=>"Number"),
//        'Test'=>array('type'=>"submit",'desc'=>"Test")
    );

    $target=uiWell();
    $postfunc=function($data)
    {
        if (match_number($data['number']))
            echo uiSpan("label label-success")->Add("Yes");
        else
            echo uiSpan("label label-important")->Add("No");
    };


    echo uiPage("DID List")->Add(
        $navbar,
        uiContainer()->Add(
            uiWell()->Add(
                uiLegend()->Add("DID Test",uiHelpIcon($help1)->Right()),
                uiDiv()->Add(
                    uiForm($testform,false,"inline")->OnSubmit($target)->Post($postfunc),
                    $target
                )

            ),
            uiWell()->Add(
                uiLegend()->Add("DID List",uiHelpIcon($help2)->Right()
                ),
                uiEditable($db_did,$fields)
            )
        )
    );
