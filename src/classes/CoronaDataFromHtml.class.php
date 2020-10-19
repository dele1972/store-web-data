<?php

    class CoronaDataFromHtml {

        public $data;
        
        private $unixtimestamp;
        private $cet;
        private $xpath;

        function __construct(DomDocument $document) {

            $this->xpath = new DOMXpath($document);
            
            try {
                $this->data['document'] = $document->saveHTML();
                
                $this->data['last-updated'] = $this->getAttributeValue(
                    $this->xpath->query("//article[@class='content-view-full']//time"),
                    'content'
                );
                $this->data['infected-total'] = $this->getNodeValue(
                    $this->xpath->query("//article[@class='content-view-full']/div/div[1]/div[2]/div[2]/p[1]/b[1]")
                );
                // @ToDo: !!! sometimes an information about the increase count is inserted and then this xpath don't work!
                $this->data['recovered-total'] = $this->getNodeValue(
                    $this->xpath->query("//article[@class='content-view-full']/div/div[1]/div[2]/div[2]/p[1]/b[2]")
                );
                // @ToDo: !!! sometimes an information about the increase count is inserted and then this xpath don't work!
                $this->data['deceased-total'] = $this->getNodeValue(
                    $this->xpath->query("//article[@class='content-view-full']/div/div[1]/div[2]/div[2]/p[1]/b[3]")
                );
                $this->data['infected-total-distribution-by-age'] = $this->getTableArray(
                    $this->xpath->query("//article[@class='content-view-full']//table[@summary='Alter']"),
                    TRUE
                );
                $this->data['infected-total-distribution-by-sex'] = $this->getTableArray(
                    $this->xpath->query("//article[@class='content-view-full']//table[@summary='Geschlecht']"),
                    FALSE
                );
                $this->data['infected-total-distribution-by-municipalities'] = $this->getTableArray(
                    $this->xpath->query("//article[@class='content-view-full']//table[@summary='Verteilung Kommunen']"),
                    TRUE
                );
            } catch (TypeError $e) {
                throw new Exception("wrong xpath");
                // @ToDo: do a log if a query could not find the xpath in the document - e.g.:
                //        Argument 1 passed to CoronaDataFromHtml::getNodeValue() must be an instance of DOMNodeList, bool given, called in /home/lederich/dev/web/domains/lederich.de/htdocs/store-web-data/src/CoronaDataFromHtml.php on line 28
            }

            $this->unixtimestamp = strtotime($this->data['last-updated']);
            $this->cet = new DateTime(date_create_from_format('U',$this->unixtimestamp,new DateTimeZone('CET'))->format('d.m.Y H:i'));

        }


        private function getTableArray(DOMNodeList $resultingDOMNodeList, bool $tableWithColHeader = FALSE): array {

            $resultarray['head'] = [];
            $resultarray['data'] = [];

            if (is_null($resultingDOMNodeList)) {

                return array();

            }

            // iterate the Nodes of the DOMNodeList (https://www.php.net/manual/en/class.domnodelist.php) -> should be one Table
            foreach ($resultingDOMNodeList as $tableElement) {

                // $tableElement->tagName is allways 'table'
                
                // with the following you can iterate downwards from total tr node count to 1 ($includesTotalTR-$trLoopCount) in the foreach
                $includesTotalTR = $tableElement->childNodes->length;
                $trLoopCount = 0;

                // iterate tr
                foreach ($tableElement->childNodes as $key => $trElement) {

                    // $trElement->tagName is allways 'tr'
                    #echo "<hr /><div style='color:blue;'>TR Count = ".($includesTotalTR-$trLoopCount)."</div><br />";
                    // with the following you can iterate downwards from total tr node count to 1 ($includesTotalChildNodes-$cellLoopCount) in the foreach
                    $includesTotalChildNodes = $trElement->childNodes->length;
                    $cellLoopCount = 0;

                    $rowValues = [];

                    foreach ($trElement->childNodes as $cellKey => $cellNode){
                        
                        // $cellNode->tagName is sometimes 'td' (every second is an unneeded object)
                        #echo "<hr /><div style='color:red;'>Cell Count = ".($includesTotalChildNodes-$cellLoopCount)."</div><br />";

                        $cellLoopCount++;

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

                    $trLoopCount++;

                    if ($tableWithColHeader && $key == 0){

                        array_push($resultarray['head'], $rowValues);
                        continue;

                    }

                    array_push($resultarray['data'], $rowValues);

                }
            }

            return $resultarray;
        }


        private function getNodeValue(DOMNodeList $resultingDOMNodeList): string {

            $result = "";

            if (is_null($resultingDOMNodeList)) {  

                return $result;

            }

            foreach ($resultingDOMNodeList as $element) {

                $nodes = $element->childNodes;

                foreach ($nodes as $node) {

                    $result = filter_var($node->nodeValue, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);

                }
            }

            return $result;
        }


        private function getAttributeValue(DOMNodeList $resultingDOMNodeList, string $attribute_name): string {

            $result = "";

            if (is_null($resultingDOMNodeList)) {

                return $result;

            }

            foreach ($resultingDOMNodeList as $element) {

                $attributes = $element->attributes;

                foreach ($attributes as $attrib) {

                    if ($attrib->name==$attribute_name){

                        $result = $attrib->value;

                    }
                }
            }

            return $result;
        }


        public function printData(bool $printWithTableDump = TRUE) {

            echo "<p>Last Updated: ".$this->data['last-updated']." / " . $this->unixtimestamp ." / " . $this->cet->format('d.m.Y H:i') . "</p>";
            echo "<p>Total infected: ".$this->data['infected-total']."</p>";
            echo "<p>Total recovered: ".$this->data['recovered-total']."</p>";
            echo "<p>Total deceased: ".$this->data['deceased-total']."</p>";
            // echo "<p>Document size: ".strlen($this->data['document'])."</p>";

            if ($printWithTableDump){
                echo "<p><pre style='font-size: 0.7em'>";
                print var_dump($this->data['infected-total-distribution-by-age']);
                print var_dump($this->data['infected-total-distribution-by-sex']);
                print var_dump($this->data['infected-total-distribution-by-municipalities']);
                echo "</pre></p>";
            }

        }
    }
?>
