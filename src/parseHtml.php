<?php

    require '../config/AppConfig.php';

    // we don't want the '.' and '..' elements of scandir in our array
    $files = array_diff(scandir(AppConfig::$htmlFileInputPath), array('.', '..'));
    
    foreach ($files as $file) {
        if (!strpos($file, 'extracted') > -1)
            continue;
    
        $real_file = AppConfig::$htmlFileInputPath . $file;
        echo "<hr/>";
        echo "<h2>$real_file</h2>";
        $dom = new DomDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTMLFile($real_file);

        doTheShit($dom);
    }


    function doTheShit($document) {

        $xpath = new DOMXpath($document);

        $data['last-updated'] = getAttributeValue(
            $xpath->query("//article[@class='content-view-full']//time"),
            'content'
        );
        $data['infected-total'] = getNodeValue(
            $xpath->query("//article[@class='content-view-full']/div/div[1]/div[2]/div[2]/p[1]/b[1]")
        );
        $data['recovered-total'] = getNodeValue(
            $xpath->query("//article[@class='content-view-full']/div/div[1]/div[2]/div[2]/p[1]/b[2]")
        );
        $data['deceased-total'] = getNodeValue(
            $xpath->query("//article[@class='content-view-full']/div/div[1]/div[2]/div[2]/p[1]/b[3]")
        );
        $data['infected-total-distribution-by-age'] = getTableArray(
            $xpath->query("//article[@class='content-view-full']//table[@summary='Alter']"),
            TRUE
        );
        $data['infected-total-distribution-by-sex'] = getTableArray(
            $xpath->query("//article[@class='content-view-full']//table[@summary='Geschlecht']"),
            FALSE
        );
        $data['infected-total-distribution-by-municipalities'] = getTableArray(
            $xpath->query("//article[@class='content-view-full']//table[@summary='Verteilung Kommunen']"),
            TRUE
        );
        
        $unixtimestamp = strtotime($data['last-updated']);
        $cet = date_create_from_format('U',$unixtimestamp,new DateTimeZone('CET'))->format('d.m.Y H:i');

        echo "<p>Last Updated: ".$data['last-updated']." / " . $unixtimestamp ." / " . $cet . "</p>";
        echo "<p>Total infected: ".$data['infected-total']."</p>";
        echo "<p>Total recovered: ".$data['recovered-total']."</p>";
        echo "<p>Total deceased: ".$data['deceased-total']."</p>";
        echo "<p><pre style='font-size: 0.7em'>";

        print var_dump($data['infected-total-distribution-by-age']);
        print var_dump($data['infected-total-distribution-by-sex']);
        print var_dump($data['infected-total-distribution-by-municipalities']);

        echo "</pre></p>";
    }


    function getTableArray($elements, $withColHeader = FALSE) {
        $resultarray['head'] = [];
        $resultarray['data'] = [];

        if (is_null($elements)) {

            return array();

        }

        foreach ($elements as $element) {

            $nodes = $element->childNodes;

            foreach ($nodes as $key => $node) {
                
                $rowValues = [];
                $cell_nodes = $node->childNodes;

                foreach ($cell_nodes as $cellKey => $cellNode){

                    // I don't know why, but sometimes other objects occurs
                    if (!property_exists($cellNode, 'tagName')){

                        continue;

                    }

                    // in some cases the input file differs from the encoding - for our needs this convertion  is enough
                    if (strpos($cellNode->nodeValue, "Ã") !== false) {

                        array_push($rowValues, trim(utf8_decode($cellNode->nodeValue)));
                        continue;

                    }
                    
                    array_push($rowValues, trim($cellNode->nodeValue));
                }

                if ($withColHeader && $key == 0){

                    array_push($resultarray['head'], $rowValues);
                    continue;

                }

                array_push($resultarray['data'], $rowValues);

            }
        }

        return $resultarray;
    }

    function getNodeValue($elements) {
        $result = "";

        if (is_null($elements)) {  

            return $result;

        }

        foreach ($elements as $element) {

            $nodes = $element->childNodes;

            foreach ($nodes as $node) {

                $result = filter_var($node->nodeValue, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

            }
        }

        return $result;
    }

    function getAttributeValue($elements, $attribute_name) {
        $result = "";

        if (is_null($elements)) {

            return $result;

        }

        foreach ($elements as $element) {

            $attributes = $element->attributes;

            foreach ($attributes as $attrib) {

                if ($attrib->name==$attribute_name){

                    $result = $attrib->value;

                }
            }
        }

        return $result;
    }
?>