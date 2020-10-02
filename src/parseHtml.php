<?php
    // turn off php notice messages
    error_reporting(E_ALL & ~E_NOTICE);
    
    include './autoloader.php';

    require '../config/AppConfig.php';

    // we don't want the '.' and '..' elements of scandir in our array
    $files = array_diff(scandir(AppConfig::$htmlFileInputPath), array('.', '..'));
    $count_files = count($files);
    $counter_processed_files = 0;
    
    foreach ($files as $file) {
        $counter_processed_files++;
        //if (!strpos($file, 'extracted') > -1) {
        //    continue;
        //}

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

        $coronaData->printData(FALSE);

        // move file
        //if (is_resource($real_input_file))
        //    fclose($real_input_file);
        //sleep(1);    // this does the trick
        //rename($real_input_file, $real_input_file . "processed.html");
    }

?>
