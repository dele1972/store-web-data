<?php
    // turn off php notice messages
    error_reporting(E_ALL & ~E_NOTICE);
    
    include 'autoloader.php';

    require '../config/AppConfig.php';

    // we don't want the '.' and '..' elements of scandir in our array
    $files = array_diff(scandir(AppConfig::$htmlFileInputPath), array('.', '..'));
    
    foreach ($files as $file) {
        if (!strpos($file, 'extracted') > -1) {
            continue;
        }

        $real_file = AppConfig::$htmlFileInputPath . $file;
        echo "<hr/>";
        echo "<h2>$real_file</h2>";
        $domDocument = new DomDocument();

        // we don't want to see warnings about invalid tags in the DomDocument
        libxml_use_internal_errors(true);

        $domDocument->loadHTMLFile($real_file);

        try {
            $coronaData = new CoronaDataFromHtml($domDocument);
            #$coronaData = new CoronaDataFromHtml(new stdClass());
        } catch (TypeError $e) {
            // logging?
            //    Constructor Parameter Type is not a DomDocument Object
        } catch (Exception $e) {
            // logging?
            //    Constructor has got xpath Problems
        }

        if (!is_object($coronaData)){
            echo "<div>Could not parse Data for this document!</div>";
            continue;
        }
        echo "<div>get_class: " . get_class($coronaData) . "</div>";

        $coronaData->printData();
        #print(var_dump($coronaData));
        
        echo "<hr />";
    }

?>