<?php
    require 'poof/poof.php';
    require 'navbar.php';

    $status=function($data)
    {
        //sleep(5);
        //if (rand(1,10)==1)
            echo time()."<br/>";
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

