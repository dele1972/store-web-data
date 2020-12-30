<?php
    // turn off php notice messages
    error_reporting(E_ALL & ~E_NOTICE);
    
    include './autoloader.php';

    include './configloader.php';


    echo <<<END
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>parse HTML to my DB</title>
        <link rel='stylesheet' type='text/css' href='https://lederich.de/global-styles/normalize.css'>
        <link rel='stylesheet' type='text/css' href='https://lederich.de/global-styles/boilerplate-8.0.0.css'>
        <link rel='stylesheet' type='text/css' href='https://lederich.de/styles/global.css'>
    </head>
    <body>
END;
    // we don't want the '.' and '..' elements of scandir in our array
    $files = array_diff(scandir(AppConfig::$htmlFileInputPath), array('.', '..'));
    $count_files = count($files);
    $counter_processed_files = 0;

    if ($count_files === 0) {
        print "<h1> No files for processing found</h1></body></html>";
        return;
    }

    $database_data['host'] = AppConfig::$db_host;
    $database_data['name'] = AppConfig::$db_name;
    $database_data['user'] = AppConfig::$db_user;
    $database_data['password'] = AppConfig::$db_password;
    $database_data['collation'] = AppConfig::$db_collation;


    try {
        $obj = new Model($database_data);
    } catch (Exception $e) {
        print "Problems with the database prevent execution.<br/>";
        rename($real_input_file, AppConfig::$htmlFileOutErrorPath . $file);
        /*
        Code 2002   -> couldn't establish db connection -
        Code 1049   -> Unknown database
        Code 1044   -> DB Access denied or db user unknown
        Code 1045   -> DB Access denied - wrong password
        */
        // print "<br />Error: " . $e->getMessage();
        // print "<br />Code: " . $e->getCode();

        return;
    }
    
    foreach ($files as $file) {
        $counter_processed_files++;

        $real_input_file = AppConfig::$htmlFileInputPath . $file;
        $real_output_file = AppConfig::$htmlFileOutPath . $file;
        echo "<hr/>";
        echo "<h2>[$counter_processed_files / $count_files] $real_input_file</h2>";

        $domDocument = getDomDocument($real_input_file);

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

        if (!is_object($coronaData)) {
            echo "<div>Could not parse Data for this document!</div>";
            rename($real_input_file, AppConfig::$htmlFileOutErrorPath . $file);
            continue;
        }
        
        $coronaData->setSourceFileName($file);
        // @ToDo - this debug output is important to get the wrong recovered and deceased count!
        $coronaData->printData(false);

        // store data to database and move the file
        try {
            $obj->storeData($coronaData);
            // move file to processed
            rename($real_input_file, AppConfig::$htmlFileOutPath . $file);
        } catch (Exception $e) {
            
            /*
            Code 1064   -> You have an error in your SQL syntax
            Code 1062   -> Duplicate entry for key 'lastupdated_tmstmp'
            */

            echo "<br />DB INSERT ERROR.";
            print "<br />Error: " . $e->getMessage();
            print "<br />Code: " . $e->getCode();

            // move file to processed/duplicates
            if ($e->getCode()===1062) {
                rename($real_input_file, AppConfig::$htmlFileOutDuplicatePath . $file);
            } else {
                rename($real_input_file, AppConfig::$htmlFileOutErrorPath . $file);
            }
        }
    }

    echo <<<END
    </body>
    </html>
END;


    function getDomDocument(string $htmlSourceFileName):DomDocument
    {
        $htmlContent = file_get_contents($htmlSourceFileName);

        $document = new DomDocument();

        // we don't want to see warnings about invalid tags in the DomDocument
        libxml_use_internal_errors(true);

        // UTF-8 WAS NOT SET and will be fixed
        if (!strpos($htmlContent, "charset=UTF-8") !== false) {

            // echo "UTF-8 WAS NOT SET and will be fixed";
            $document->loadHTML('<?xml version="1.0" encoding="UTF-8"?>' . $htmlContent);
            return $document;
        }

        // UTF-8 was set correctly, only regular loading is neccessary
        // echo "UTF-8 was set correctly, only regular loading is neccessary";
        $document->loadHTML($htmlContent);
        return $document;
    }
