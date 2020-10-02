<?php

//  Thanks to Dani Krossing (https://youtu.be/z3pZdmJ64jo) for this autoloader suggestion

spl_autoload_register('myAutoLoader');

function myAutoLoader($className) {
    $path = "classes/";
    $extension = ".class.php";
    $fullPath = $path . $className . $extension;

    if (!file_exists($fullPath))
        return false;

    include_once $fullPath;
}
