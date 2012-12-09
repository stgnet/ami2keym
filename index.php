<?php
    require 'poof/poof.php';
    require 'navbar.php';

    $ami2keym=pfDaemon('ami2keym');

    $start=function($data)
    {
        global $ami2keym;
        $ami2keym->start();
        echo uiBadge("success")->Add("Started");
    };
    $stop=function($data)
    {
        global $ami2keym;
        $ami2keym->stop();
        echo uiBadge("success")->Add("Stopped");
    };
    $status=function($data)
    {
        global $ami2keym;
        $info=htmlentities($ami2keym->status());
        echo "<pre>Status: $info</pre>";
    };

    $activity=function($data)
    {
        global $ami2keym;
        $calls=$ami2keym->GetCalls();
        if (!$calls || !count($calls))
            echo "<p>No active calls</p>";
        else
            echo uiTable(dbArray($calls));
    };

    $StatusDiv=uiLongPoll()->Every(3)->Post($status);

    echo uiPage("Status")->Add(
        $navbar,
        uiContainer()->Add(
            uiWell()->Add(
                uiLegend("Status"),
                $StatusDiv,
                uiDiv()->Add(
                    uiButton("START")->Post($start)->Target($StatusDiv),
                    uiButton("STOP")->Post($stop)->Target($StatusDiv)
                )
            ),
            uiWell()->Add(
                uiLegend("Activity"),
                uiLongPoll()->Every(2)->Post($activity)
            )
        )
    );

