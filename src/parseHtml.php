<?php
    // turn off php notice messages
    error_reporting(E_ALL & ~E_NOTICE);
    
    include './autoloader.php';

    require '../config/AppConfig.php';
    //require '../../.data-nogit/store-web-data/AppConfig.php';

    // we don't want the '.' and '..' elements of scandir in our array
    $files = array_diff(scandir(AppConfig::$htmlFileInputPath), array('.', '..'));
    $count_files = count($files);
    $counter_processed_files = 0;

    $obj = new Model();
    
    foreach ($files as $file) {
        $counter_processed_files++;

        $real_input_file = AppConfig::$htmlFileInputPath . $file;
        $real_output_file = AppConfig::$htmlFileOutPath . $file;
        echo "<hr/>";
        echo "<h2>[$counter_processed_files / $count_files] $real_input_file</h2>";
        $domDocument = new DomDocument();

        // we don't want to see warnings about invalid tags in the DomDocument
        libxml_use_internal_errors(true);

        $domDocument->loadHTMLFile($real_input_file);

        try {
            $coronaData = new CoronaDataFromHtml($domDocument);
            #$coronaData = new CoronaDataFromHtml(new stdClass());  // for testing an empty object parameter
        } catch (TypeError $e) {
            // logging? move file to processed/failed/nodom?
            //    Constructor Parameter Type is not a DomDocument Object
        } catch (Exception $e) {
            // logging? move file to processed/failed/novalidxpath?
            //    Constructor has got xpath Problems
        }

        if (!is_object($coronaData)){
            echo "<div>Could not parse Data for this document!</div>";
            continue;
        }
        echo "<div>get_class: " . get_class($coronaData) . "</div>";

        //$coronaData->printData(FALSE);

        try {
            $obj->storeData($coronaData);

        } catch (Exception $e) {
            print "Error: " . $e->getMessage();
            print "Code: " . $e->getCode();
        }

        // move file (is working but disabled)
        // rename($real_input_file, $real_output_file);
    }

?>
