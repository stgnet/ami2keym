<?php
    poof_theme("cerulean");

    $navmenu=array('Status'=>"/");
    foreach (arDir(".")->Match("menu-*.php")->Sort() as $file)
        $navmenu[ucwords(basename(substr($file,5),".php"))]=$file;

    $navbar=uiDiv("navbar")->Add(
        uiDiv("navbar-inner")->Add(
            //uiImage("../img/poof.png","../demo.php")->AddClass("nav"),
            uiLink("/","AMI2KEYM")->AddClass("brand"),
            uiNavList($navmenu)->AddClass("pull-right")
        )
    );

