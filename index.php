<?php
    require 'poof/poof.php';
    require 'navbar.php';

    $ami2keym=pfDaemon('ami2keym');

    $status=function($data)
    {
        global $ami2keym;

        $calls=$ami2keym->GetCalls();

        if (!$calls || !count($calls))
        {
            sleep(5);
            echo "<p>No active calls</p>";
        }
        else
        {
            $table=array();
            foreach ($calls as $call)
            {
                if (empty($call['extension']))
                    $call['extension']='unknown';
                if (empty($call['context-last']))
                    $call['context-last']='unknown';
                if (empty($call['application-last']))
                    $call['application-last']='unknown';

                $table[]=array(
                    'CID'=>$call['calleridnum']." ".$call['calleridname'],
                    'DID'=>$call['extension'],
                    'Context'=>$call['context-last'],
                    'Application'=>$call['application-last']
                );
            }
            echo uiTable(dbArray($table));
        }
    };

    echo uiPage("Status")->Add(
        $navbar,
        uiContainer()->Add(
            uiWell()->Add(
                uiLegend("Status"),
                uiLongPoll()->Post($status)
            )
        )
    );

