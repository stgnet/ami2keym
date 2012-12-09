<?php

    function match_number($number)
    {
        global $db_did;
        if (empty($db_did))
            $db_did=dbCsv("did.csv");

        // go through all of the numbers in the database
        foreach ($db_did->records() as $record)
        {
            if (empty($record['number']))
                continue;

            // match the right-justified version of the numbers
            $len=strlen($record['number']);
            if (substr($number,-$len)==$record['number'])
                return(true);
        }
        return(false);
    }
