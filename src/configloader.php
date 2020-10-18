<?php

spl_autoload_register('myConfigLoader');

function myConfigLoader($className) {

    //$alternatePath = "../../.data-nogit/store-web-data/AppConfig.php";

    if (!isset($alternatePath) || !file_exists($alternatePath)){

        include_once "../config/AppConfig.php";

    } else {

        include_once $alternatePath;

    }
}
