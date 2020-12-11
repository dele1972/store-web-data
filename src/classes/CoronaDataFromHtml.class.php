<?php

    class CoronaDataFromHtml {

        public $data;
        
        private $unixtimestamp;
        private $cet;
        private $xpath;
        private $xPathFirstSentenceVersion;

        function __construct(DomDocument $document) {

            $this->xpath = new DOMXpath($document);
            
            // the structure of the first paragraph changed slightly during the time :(
            $this->xPathFirstSentenceVersion[0]['infected-total'] = "//article[@class='content-view-full']/div/div[1]/div[2]/div[2]//p[1]/b[1]";
            $this->xPathFirstSentenceVersion[0]['recovered-total'] = "//article[@class='content-view-full']/div/div[1]/div[2]/div[2]//p[1]/b[2]";
            $this->xPathFirstSentenceVersion[0]['deceased-total'] = "//article[@class='content-view-full']/div/div[1]/div[2]/div[2]//p[1]/b[3]";
            
            $this->xPathFirstSentenceVersion[1]['infected-total'] = "//article[@class='content-view-full']/div/div[1]/div[2]/div[2]//p[1]/b[1]";
            $this->xPathFirstSentenceVersion[1]['recovered-total'] = "//article[@class='content-view-full']/div/div[1]/div[2]/div[2]//p[1]/b[3]";
            $this->xPathFirstSentenceVersion[1]['deceased-total'] = "//article[@class='content-view-full']/div/div[1]/div[2]/div[2]//p[1]/b[4]";
            
            try {

                $this->data['document'] = $document->saveHTML();
                
                $this->data['last-updated'] = $this->getAttributeValue(
                    $this->xpath->query("//article[@class='content-view-full']//time"),
                    'content'
                );
                $this->unixtimestamp = strtotime($this->data['last-updated']);
                $this->cet = new DateTime(date_create_from_format('U',$this->unixtimestamp,new DateTimeZone('CET'))->format('d.m.Y H:i'));
                
                $this->data['seven_day_incidence'] = $this->getSevenDayIncidence();    

                $alternateDataVersion = $this->setAlternateDataVersion();
                $this->data['infected-total'] = $this->getIntValueOfHtmlpart($alternateDataVersion, 'infected-total');
                $this->data['recovered-total'] = $this->getIntValueOfHtmlpart($alternateDataVersion, 'recovered-total');
                $this->data['deceased-total'] = $this->getIntValueOfHtmlpart($alternateDataVersion, 'deceased-total');
                
                $this->data['infected-total-distribution-by-age'] = $this->getTotalDistributionByAge();    
                $this->data['infected-total-distribution-by-sex'] = $this->getTableArray(
                    $this->xpath->query("//article[@class='content-view-full']//table[@summary='Geschlecht']"),
                    FALSE
                );    
                $this->data['infected-total-distribution-by-municipalities'] = $this->getTableArray(
                    $this->xpath->query("//article[@class='content-view-full']//table[@summary='Verteilung Kommunen']"),
                    TRUE
                );    

            } catch (TypeError $e) {

                print var_dump($e);
                throw new Exception("wrong xpath");
                // @ToDo: do a log if a query could not find the xpath in the document - e.g.:
                //        Argument 1 passed to CoronaDataFromHtml::getNodeValue() must be an instance of DOMNodeList, bool given, called in /home/lederich/dev/web/domains/lederich.de/htdocs/store-web-data/src/CoronaDataFromHtml.php on line 28    

            }    

        }


        /**
         * since 2020-10-16 the xpath of the distribution by age table is changed
         */
        private function getTotalDistributionByAge() {

            try {
                
                if ($this->xpath->query("//article[@class='content-view-full']//table[@summary='Alter']")->length ===1){
                    
                    return $this->getTableArray(
                        $this->xpath->query("//article[@class='content-view-full']//table[@summary='Alter']"),
                        TRUE
                    );

                }
                
                return $this->getTableArray(
                    $this->xpath->query("//article[@class='content-view-full']//table[@summary='s']"),
                    TRUE
                );

            } catch (TypeError $e) {

                print var_dump($e);
                throw new Exception("wrong xpath");
                // @ToDo: do a log if a query could not find the xpath in the document - e.g.:
                //        Argument 1 passed to CoronaDataFromHtml::getNodeValue() must be an instance of DOMNodeList, bool given, called in /home/lederich/dev/web/domains/lederich.de/htdocs/store-web-data/src/CoronaDataFromHtml.php on line 28    

            }
            
        }

        
        /**
         * In the first paragraph sometime this information is added "(+26 verglichen zur letzten Meldung)".
         * That mixes everything up - this function determine this alternate view.
         * 
         * returns
         *      TRUE    for alternative view
         *      FALSE   for regular view
         */
        private function checkFstPargrphAlternate(): bool {

            return strpos(
                $this->getNodeValue(
                    $this->xpath->query(
                        $this->xPathFirstSentenceVersion[0]['recovered-total']
                    )
                ),
                "+"
            ) !== FALSE;

        }


        /**
         * returns
         *    0 for regular view
         *    1 for alternative view
         */
        private function setAlternateDataVersion(): int {
            
            if ($this->checkFstPargrphAlternate()){
                
                return 1;
            }

            return 0;

        }


        /**
         * on 2020-10-02 the 7-day-incidence value is given in the first paragraph
         */
        private function getSevenDayIncidence() {

            $firstParagraph = $this->xpath->query("//article[@class='content-view-full']/div/div[1]/div[2]/div[2]/p[1]")[0]->textContent;
            $searchString = "7-Tages-Inzidenz";

            if (!strpos($firstParagraph, $searchString) !== false) {

              return NULL;

            }

            $substr = substr($firstParagraph, strpos($firstParagraph,"7-Tages-Inzidenz"));

            return $this->getNumberValueOfString(

                str_replace(
                    '7-Tages-Inzidenz pro 100.000',
                    '',
                    $substr

                )
            );

        }


        /**
         * strip all chars except 0-9 and ','
         */
        private function getNumberValueOfString(string $subject) {
            
            return preg_replace(
                "/[^\d,]/",
                "",
                $subject
            );

        }


        /**
         * sometimes '.' is used as separator
         */
        private function getIntValueOfHtmlpart(int $version, string $key): int {

            return (int)$this->getNumberValueOfString(
                str_replace(
                    ".",
                    "",
                    $this->getNodeValue(
                        $this->xpath->query($this->xPathFirstSentenceVersion[$version][$key])
                    )
                )
            );    

        }


        /**
         * Converts a HTML Table to an array
         * @ToDo: make it recursive to avoid (stacked) foreach
         */
        private function getTableArray(DOMNodeList $resultingDOMNodeList, bool $tableWithColHeader = FALSE): array {

            $resultarray['head'] = [];
            $resultarray['data'] = [];

            if (is_null($resultingDOMNodeList)) {

                return array();

            }

            // iterate the Nodes of the DOMNodeList (https://www.php.net/manual/en/class.domnodelist.php) -> should be one Table
            foreach ($resultingDOMNodeList as $tableElement) {

                // Since about 4.1.2020 the tablestruckture was changed and a TBODY layer was drawn
                // in between. This hack handles that so that TR elements can still be processed
                // in the foreach loop.
                  if ($tableElement->firstChild->tagName === "tbody") {
                    $tableElement = $tableElement->childNodes[0];
                  }

                // note: in this scope is $tableElement->tagName is 'table' or 'tbody'
                
                // with the following you can iterate downwards from total tr node count to 1 ($includesTotalTR-$trLoopCount) in the foreach
                $includesTotalTR = $tableElement->childNodes->length;
                $trLoopCount = 0;

                // iterate tr
                foreach ($tableElement->childNodes as $key => $trElement) {

                    // $trElement->tagName is allways 'tr'
                    // with the following you can iterate downwards from total tr node count to 1 ($includesTotalChildNodes-$cellLoopCount) in the foreach
                    $includesTotalChildNodes = $trElement->childNodes->length;
                    $cellLoopCount = 0;

                    $rowValues = [];

                    // iterate td (and something else)
                    foreach ($trElement->childNodes as $cellKey => $cellNode){
                        
                        // $cellNode->tagName is sometimes 'td' (every second is an unneeded object)

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


        /**
         * Get a single value of a HTML with given xPath
         */
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


        public function setSourceFileName(string $filename) {

            $this->data['sourceFilenName'] = $filename;

        }

        public function printData(bool $printWithTableDump = TRUE) {

            echo "<p>Last Updated: ".$this->data['last-updated']." / " . $this->unixtimestamp ." / " . $this->cet->format('d.m.Y H:i') . "</p>";
            echo "<p>Total infected: ".$this->data['infected-total']."</p>";
            echo "<p>Total recovered: ".$this->data['recovered-total']."</p>";
            echo "<p>Total deceased: ".$this->data['deceased-total']."</p>";
            echo "<p>7 day incidence: ".$this->data['seven_day_incidence']."</p>";
            echo "<p>Source Filename: ".$this->data['sourceFilenName']."</p>";
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
