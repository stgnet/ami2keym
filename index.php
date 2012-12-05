<?php
    require 'poof/poof.php';
    require 'navbar.php';

    echo uiPage("Status")->Add(
        $navbar,
        uiContainer()->Add(
            uiWell()->Add(
                uiLegend("Status"),
                "Offline"
            )
        )
    );

