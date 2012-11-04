<?php

// to get this example running just change the urls to point to sleep.php
// or any other script you like

require_once __DIR__ . '/../src/Japo/Curl/MultiHandler.php';

// init example call that will take 10 seconds to finish
$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, "http://localhost/sleep.php?t=10");
curl_setopt($ch1, CURLOPT_HEADER, 0);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);

// init example call that will take 5 seconds to finish
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, "http://localhost/sleep.php?t=5");
curl_setopt($ch2, CURLOPT_HEADER, 0);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

// init example that will immediatly exit with CURLE_COULDNT_RESOLVE_HOST
$ch3 = curl_init();
curl_setopt($ch3, CURLOPT_URL, "http://thisUrlIsNot.Valid");
curl_setopt($ch3, CURLOPT_HEADER, 0);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);

// general error handler
$error_handler = function($handle, $code) {
            echo 'GOT ERROR: ', \Japo\Curl\MultiHandler::getResultName($code), '(' . $code . ')', "\n";
        };

// initialize multi handler
$m = new \Japo\Curl\MultiHandler();

// register curl handler 1
$m->addHandle($ch1, function($handle) {
            // execute on curl handle 1 finishs
            var_dump(curl_multi_getcontent($handle));
        }, $error_handler);

// register curl handler 2
$m->addHandle($ch2, function($handle) {
            // execute on curl handle 2 finishs
            var_dump(curl_multi_getcontent($handle));
        }, $error_handler);

// register curl handler 3
$m->addHandle($ch3, function($handle) {
            // execute on curl handle 3 finishs
            // never executed in this example
            var_dump(curl_multi_getcontent($handle));
        }, $error_handler);

// blocking call
$m->execute();
