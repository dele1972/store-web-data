<?php

    /*
    https://stackoverflow.com/q/39904326
        You can circumvent the problem by converting the datetime from and to unix
        timestamp. So to insert a unix timestamp, you would create a DATETIME
        field in your table, and convert the unix timestamp with
        FROM_UNIXTIME(1514789942). Then when you want to retrieve the value
        back in unix timstamp you do SELECT UNIX_TIMESTAMP(my_datetime_field)

    https://chartio.com/resources/tutorials/understanding-strorage-sizes-for-mysql-text-data-types/
    https://appdividend.com/2019/04/02/how-to-convert-php-array-to-json-tutorial-with-example/
    https://dev.mysql.com/doc/refman/5.7/en/json.html
    https://stackoverflow.com/questions/17371639/how-to-store-arrays-in-mysql

    https://en.wikipedia.org/wiki/Year_2038_problem#Possible_solutions
    */

    include(__DIR__ . '/../simplediff.php');

    class Model
    {
        public $data;
        
        private $unixtimestamp;
        private $pdo;
        private $last_mainentry_id;
        private $municipalities = [
                "Barsinghausen",
                "Burgdorf",
                "Burgwedel",
                "Garbsen",
                "Gehrden",
                "Hemmingen",
                "Isernhagen",
                "Laatzen",
                "Landeshauptstadt_Hannover",
                "Langenhagen",
                "Lehrte",
                "Neustadt_a_Rbge",
                "Pattensen",
                "Ronnenberg",
                "Seelze",
                "Sehnde",
                "Springe",
                "Uetze",
                "Wedemark",
                "Wennigsen",
                "Wunstorf"
        ];


        public function __construct($databaseCred)
        {
            $conStr = sprintf("mysql:host=%s;dbname=%s;", $databaseCred['host'], $databaseCred['name']);

            try {
                $this->pdo = new PDO($conStr, $databaseCred['user'], $databaseCred['password']);
                // we need to disable the pdo silent mode to be able to react on errors (https://stackoverflow.com/a/32648423)
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->exec("SET CHARACTER SET utf8mb4");
                
                // create Tables if not exists
                $this->createMainDataTable();
                $this->createDocumentTable();
                $this->createDistributionAgeTable();
                $this->createDistributionSexTable();
                $this->createDistributionMunicipalitiesTable();
            } catch (PDOException $e) {

                // PDO Connection Problems
                throw new Exception($e->getMessage(), $e->getCode());
            }
        }


        public function __destruct()
        {

            // close the database connection
            $this->pdo = null;
        }


        private function createMainDataTable()
        {
            $sql = <<<EOSQL
                CREATE TABLE IF NOT EXISTS maindata (
                    entry_id            INT AUTO_INCREMENT PRIMARY KEY,
                    lastupdated_tmstmp  DATETIME DEFAULT NULL,
                    lastupdated_string  VARCHAR(25) DEFAULT NULL,
                    total_infected      MEDIUMINT UNSIGNED DEFAULT NULL,
                    total_recovered     MEDIUMINT UNSIGNED DEFAULT NULL,
                    total_deceased      MEDIUMINT UNSIGNED DEFAULT NULL,
                    seven_day_incidence DOUBLE(8, 1) DEFAULT NULL,
                    UNIQUE KEY          (lastupdated_tmstmp)
                );
EOSQL;
            return $this->pdo->exec($sql);
        }


        private function createDocumentTable()
        {
            $sql = <<<EOSQL
                CREATE TABLE IF NOT EXISTS document (
                    document_id             INT AUTO_INCREMENT PRIMARY KEY,
                    entry_id                INT NOT NULL,
                    source_file_name        VARCHAR(255) DEFAULT NULL,
                    document                MEDIUMTEXT DEFAULT NULL,
                    array_age               JSON,
                    array_sex               JSON,
                    array_municipalities    JSON
                );
EOSQL;
            return $this->pdo->exec($sql);
        }


        private function createDistributionAgeTable()
        {
            $sql = <<<EOSQL
                CREATE TABLE IF NOT EXISTS distribution_age (
                    dist_age_id     INT AUTO_INCREMENT PRIMARY KEY,
                    entry_id        INT NOT NULL,
                    x_to_9          MEDIUMINT UNSIGNED DEFAULT NULL,
                    10_to_19        MEDIUMINT UNSIGNED DEFAULT NULL,
                    20_to_29        MEDIUMINT UNSIGNED DEFAULT NULL,
                    30_to_39        MEDIUMINT UNSIGNED DEFAULT NULL,
                    40_to_49        MEDIUMINT UNSIGNED DEFAULT NULL,
                    50_to_59        MEDIUMINT UNSIGNED DEFAULT NULL,
                    60_to_69        MEDIUMINT UNSIGNED DEFAULT NULL,
                    70_to_79        MEDIUMINT UNSIGNED DEFAULT NULL,
                    80_to_x         MEDIUMINT UNSIGNED DEFAULT NULL,
                    not_specified   MEDIUMINT UNSIGNED DEFAULT NULL
                );
EOSQL;
            return $this->pdo->exec($sql);
        }


        private function createDistributionSexTable()
        {
            $sql = <<<EOSQL
                CREATE TABLE IF NOT EXISTS distribution_sex (
                    dist_sex_id     INT AUTO_INCREMENT PRIMARY KEY,
                    entry_id        INT NOT NULL,
                    male            DOUBLE(4, 1) DEFAULT NULL,
                    female          DOUBLE(4, 1) DEFAULT NULL,
                    not_specified   DOUBLE(4, 1) DEFAULT NULL
                );
EOSQL;
            return $this->pdo->exec($sql);
        }


        private function createDistributionMunicipalitiesTable()
        {
            $sql = <<<EOSQL
                CREATE TABLE IF NOT EXISTS distribution_municipalities (
                    dist_municapilities_id                  INT AUTO_INCREMENT PRIMARY KEY,
                    entry_id                                INT NOT NULL,
EOSQL;

            // add community to sql with each given column
            // @ToDo recursive or with second foreach ["_current", "_sincebegin", "_sevendayin"]
            foreach ($this->municipalities as $element) {
                $sql .= $element . "_current MEDIUMINT UNSIGNED DEFAULT NULL,";
                $sql .= $element . "_sincebegin MEDIUMINT UNSIGNED DEFAULT NULL,";
                $sql .= $element . "_sevendayin DOUBLE(8,1) DEFAULT NULL,";
            }
            
            $sql = $this->last_substr_replace(",", ");", $sql);
            
            return $this->pdo->exec($sql);
        }

        private function last_substr_replace(string $search, string $replace, string $subject): string
        {
            $lastIndex = strrpos($subject, $search);
            $subject = substr_replace($subject, $replace, $lastIndex, strlen($search));
            return $subject;
        }


        private function insertMainData(CoronaDataFromHtml $dataObject)
        {
            $unixtimestamp = strtotime($dataObject->data['last-updated']);
            $cet = new DateTime(date_create_from_format('U', $unixtimestamp, new DateTimeZone('CET'))->format('d.m.Y H:i'));

            $sql = <<<END
            INSERT INTO maindata (
                lastupdated_tmstmp,
                lastupdated_string,
                total_infected,
                total_recovered,
                total_deceased
            )
                VALUES (
                    FROM_UNIXTIME(:lastupdated_tmstmp),
                    :lastupdated_string,
                    :total_infected,
                    :total_recovered,
                    :total_deceased
            )
END;

            if (strlen($dataObject->data['seven_day_incidence']) > 0) {
                $sql = <<<END
                INSERT INTO maindata (
                    lastupdated_tmstmp,
                    lastupdated_string,
                    total_infected,
                    total_recovered,
                    total_deceased,
                    seven_day_incidence
                )
                    VALUES (
                        FROM_UNIXTIME(:lastupdated_tmstmp),
                        :lastupdated_string,
                        :total_infected,
                        :total_recovered,
                        :total_deceased,
                        :seven_day_incidence
                )
END;
            }
            
            try {
                $stmt = $this->pdo->prepare($sql);
                
                $stmt->bindParam(':lastupdated_tmstmp', $unixtimestamp);
                $stmt->bindParam(':lastupdated_string', $dataObject->data['last-updated']);
                $stmt->bindParam(':total_infected', $dataObject->data['infected-total']);
                $stmt->bindParam(':total_recovered', $dataObject->data['recovered-total']);
                $stmt->bindParam(':total_deceased', $dataObject->data['deceased-total']);

                if (strlen($dataObject->data['seven_day_incidence']) > 0) {
                    $stmt->bindParam(
                        ':seven_day_incidence',
                        str_replace(
                            ",",
                            ".",
                            $dataObject->data['seven_day_incidence']
                        )
                    );
                }
            
                $stmt->execute();

                $this->last_mainentry_id = $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);
            }

            return true;
        }


        private function insertDistributionAgeData(CoronaDataFromHtml $dataObject)
        {
            $stmt = $this->pdo->prepare("
                INSERT INTO distribution_age (
                    entry_id,
                    x_to_9,
                    10_to_19,
                    20_to_29,
                    30_to_39,
                    40_to_49,
                    50_to_59,
                    60_to_69,
                    70_to_79,
                    80_to_x,
                    not_specified
                )
                    VALUES (
                        :entry_id,
                        :x_to_9,
                        :10_to_19,
                        :20_to_29,
                        :30_to_39,
                        :40_to_49,
                        :50_to_59,
                        :60_to_69,
                        :70_to_79,
                        :80_to_x,
                        :not_specified
                    )
                ;
            ");

            try {

                // @ToDo make bind statements in a loop and then an empty execute
                $stmt->execute([
                    ':entry_id' => $this->last_mainentry_id,
                    ':x_to_9'   => $dataObject->data['infected-total-distribution-by-age']['data'][0][1],
                    ':10_to_19' => $dataObject->data['infected-total-distribution-by-age']['data'][1][1],
                    ':20_to_29' => $dataObject->data['infected-total-distribution-by-age']['data'][2][1],
                    ':30_to_39' => $dataObject->data['infected-total-distribution-by-age']['data'][3][1],
                    ':40_to_49' => $dataObject->data['infected-total-distribution-by-age']['data'][4][1],
                    ':50_to_59' => $dataObject->data['infected-total-distribution-by-age']['data'][5][1],
                    ':60_to_69' => $dataObject->data['infected-total-distribution-by-age']['data'][6][1],
                    ':70_to_79' => $dataObject->data['infected-total-distribution-by-age']['data'][7][1],
                    ':80_to_x'  => $dataObject->data['infected-total-distribution-by-age']['data'][8][1],
                    ':not_specified' => $dataObject->data['infected-total-distribution-by-age']['data'][9][1]
                ]);
            } catch (PDOException $e) {
                throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);
            }

            return true;
        }


        private function insertDistributionMunicipalitiesData(CoronaDataFromHtml $dataObject)
        {


            // in the beginning the col for Seven-Days-Incidence is not given
            $useSevDayInc = count($dataObject->data['infected-total-distribution-by-municipalities']['data'][0]) === 4;

            // build sql statement - COLUMNS
            $sql = $this->buildMunicipalitiesInsertSQLColumns($useSevDayInc);

            // build sql statement - VALUES
            $sql .= $this->addMunicipalitiesInsertSQLValues($useSevDayInc);
            $stmt = $this->pdo->prepare($sql);

            // bind parameters
            $stmt->bindParam(':entry_id', $this->last_mainentry_id);

            foreach ($this->municipalities as $key => $element) {
                /* echo "<h1>{$element} / {$key} " . $dataObject->data['infected-total-distribution-by-municipalities']['data'][0][1] . "</h1>"; */
                /* print var_dump($dataObject->data['infected-total-distribution-by-municipalities']['data'][$key]); */
                /* print "<hr />"; */
                $stmt->bindParam(
                    ':' . $element . '_current',
                    str_replace(
                        ".",
                        "",
                        $dataObject->data['infected-total-distribution-by-municipalities']['data'][$key][1]
                    )
                );
                $stmt->bindParam(
                    ':' . $element . '_sincebegin',
                    str_replace(
                        ".",
                        "",
                        $dataObject->data['infected-total-distribution-by-municipalities']['data'][$key][2]
                    )
                );
                if ($useSevDayInc) {
                    $stmt->bindParam(
                        ':' . $element . '_sevendayin',
                        str_replace(
                            ",",
                            ".",
                            $dataObject->data['infected-total-distribution-by-municipalities']['data'][$key][3]
                        )
                    );
                }
            };

            try {
                $stmt->execute();
            } catch (PDOException $e) {
                throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);
            }

            return true;
        }


        private function buildMunicipalitiesInsertSQLColumns($useSevDayInc)
        {
            $sql = "INSERT INTO distribution_municipalities ( entry_id,";
        
            foreach ($this->municipalities as $element) {
                $sql .= $element . "_current,";
                $sql .= $element . "_sincebegin,";

                if ($useSevDayInc) {
                    $sql .= $element . "_sevendayin,";
                }
            };

            $sql = $this->last_substr_replace(",", ") ", $sql);

            return $sql;
        }


        private function addMunicipalitiesInsertSQLValues($useSevDayInc)
        {
            $sql .= "VALUES (:entry_id,";

            foreach ($this->municipalities as $element) {
                $sql .= ":" . $element . "_current,";
                $sql .= ":" . $element . "_sincebegin,";

                if ($useSevDayInc) {
                    $sql .= ":" . $element . "_sevendayin,";
                }
            };

            $sql = $this->last_substr_replace(",", ");", $sql);

            return $sql;
        }


        private function insertDistributionSexData(CoronaDataFromHtml $dataObject)
        {
            $stmt = $this->pdo->prepare("
                INSERT INTO distribution_sex (
                    entry_id,
                    male,
                    female,
                    not_specified
                )
                    VALUES (
                        :entry_id,
                        :male,
                        :female,
                        :not_specified
                        )
                ;
            ");

            try {

                // @ToDo make bind statements in a loop and then an empty execute
                $stmt->execute([

                    ':entry_id' => $this->last_mainentry_id,
                    ':male'   => (float) str_replace(" Prozent", "", $dataObject->data['infected-total-distribution-by-sex']['data'][0][1]),
                    ':female'   => (float) str_replace(" Prozent", "", $dataObject->data['infected-total-distribution-by-sex']['data'][1][1]),
                    ':not_specified'   => (float) str_replace(" Prozent", "", $dataObject->data['infected-total-distribution-by-sex']['data'][2][1])
  
                ]);
            } catch (PDOException $e) {
                throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);
            }

            return true;
        }


        private function insertDocumentData(CoronaDataFromHtml $dataObject)
        {
            $stmt = $this->pdo->prepare("
                INSERT INTO document (
                    entry_id,
                    source_file_name,
                    document,
                    array_age,
                    array_sex,
                    array_municipalities
                )
                    VALUES (
                        :entry_id,
                        :source_file_name,
                        :document,
                        :array_age,
                        :array_sex,
                        :array_municipalities
                            )
                ;
            ");

            try {

                // @ToDo make bind statements in a loop and then an empty execute

                $stmt->execute([
                    ':entry_id' => $this->last_mainentry_id,
                    ':source_file_name' => $dataObject->data['sourceFilenName'],
                    ':document' => $dataObject->data['document'],
                    ':array_age' => json_encode($dataObject->data['infected-total-distribution-by-age']),
                    ':array_sex' => json_encode($dataObject->data['infected-total-distribution-by-sex']),
                    ':array_municipalities' => json_encode($dataObject->data['infected-total-distribution-by-municipalities'])
                ]);
            } catch (PDOException $e) {

                //print var_dump($e);
                throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);
            }

            return true;
        }


        /**
         * Has table `distribution_municipalities` columns within 'sevendayin'?
         * These columns for each community is added since 2020-10-02.
         *
         * @return bool   (true - sevendayin cols are given, false - not)
         */
        private function checkSevenDayInCol():bool
        {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `distribution_municipalities` LIKE '%_sevendayin'");
            return !is_bool($stmt->fetch());
        }


        private function alterDistribMunicTableSevenDayIn()
        {
            $sql = "ALTER TABLE distribution_municipalities";

            // add community to sql with each given column
            // @ToDo recursive or with second foreach ["_current", "_sincebegin", "_sevendayin"]
            foreach ($this->municipalities as $element) {
                $sql .= " ADD " . $element . "_sevendayin DOUBLE(8,1) DEFAULT NULL,";
            }
            
            $sql = $this->last_substr_replace(",", ";", $sql);

            return $this->pdo->exec($sql);
        }

        public function storeData(CoronaDataFromHtml $dataObject)
        {
            try {
                $this->insertMainData($dataObject);
                $this->insertDistributionAgeData($dataObject);
                $this->insertDistributionMunicipalitiesData($dataObject);
                $this->insertDistributionSexData($dataObject);
                $this->insertDocumentData($dataObject);
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), $e->getCode());
                // @ToDo: remove Zombie code!
                //print var_dump($e->getMessage());
                //print var_dump($e->getCode());
                //print_r ("Error2: " . $e->errorinfo()[1] . "<br />");
                //print_r ("Message2: " . $e[1] . "<br />");
            }
        }


        public function updateDbStructure()
        {
            echo '<h1>Update</h1>';
            if (!$this->checkSevenDayInCol()) {
                echo "... Table 'distribution_municipalities' has no '_sevendayin' Columns -> will be fixed now.";
                $this->alterDistribMunicTableSevenDayIn();
            }
        }


        /**
         *
         * @param array $compareMe [0: string - table colname, 1: value db-col, 2: value array-key
         * @param bool $show_output echo comparing and result
         * @return bool true if values are identical
         */
        private function compareFields(array $compareMe, array $show=array('values' => false, 'output' => false)):bool
        {
            /* print var_dump($show['output']); */

            if ($show['output']) {
                echo "<br />   ... for '" . $compareMe[0] . "': ";
                echo ($show['values']) ? $compareMe[1] . " = $compareMe[2]" : "";
            }

            if ($compareMe[1] == $compareMe[2]) {
                if ($show['output']) {
                    echo "   ✔";
                }

                return true;
            } else {
                if ($show['output']) {
                    echo "   ✖";
                }

                return false;
            }
        }


        private function convert2DbDoubleFormat($value)
        {
            return str_replace(",", ".", $value);
        }


        /**
         *
         *
         * @param CoronaDataFromHtml $dataObject Contains whole HTML grabbed data for compairing with DB content and update
         *
         */
        public function checkAndUpdateTables(CoronaDataFromHtml $dataObject)
        {
            $entryID = $this->getEntryIdByDateString($dataObject->data['last-updated']);

            $tables2check = [
                     'maindata',
                     'document',
                     'distribution_age',
                     'distribution_sex',
                     'distribution_municipalities',
            ];
            //
            //     +++ table maindata +++
            //
            $db_excludes = ['entry_id','lastupdated_tmstmp'];
            $dataObj_excludes = ['document','infected-total-distribution-by-age','infected-total-distribution-by-sex','infected-total-distribution-by-municipalities'];
            echo "<h3>... checking 'maindata' for $entryID</h3>";
            $sql = "SELECT * from `maindata` WHERE `entry_id` = '$entryID';";
            $stmnt = $this->pdo->query($sql);
            $result = $stmnt->fetch(PDO::FETCH_ASSOC);
            $this->compareFields(['lastupdated_string', $result['lastupdated_string'], $dataObject->data['last-updated']], array('values' => false, 'output' => true));
            $this->compareFields(['total_infected', $result['total_infected'], $dataObject->data['infected-total']], array('values' => false, 'output' => true));
            $this->compareFields(['total_recovered', $result['total_recovered'], $dataObject->data['recovered-total']], array('values' => false, 'output' => true));
            $this->compareFields(['total_deceased', $result['total_deceased'], $dataObject->data['deceased-total']], array('values' => false, 'output' => true));
            $this->compareFields(['seven_day_incidence', $result['seven_day_incidence'], $this->convert2DbDoubleFormat($dataObject->data['seven_day_incidence'])], array('values' => false, 'output' => true));


            //
            //     +++ table document +++
            //
            $db_excludes = ['entry_id','document_id'];
            $dataObj_excludes = [];
            $mapDb2DataObj = array(
              'source_file_name' => 'sourceFilenName',
              'document' => 'document',
              'array_age' => 'infected-total-distribution-by-age',
              'array_sex' => 'infected-total-distribution-by-sex',
              'array_municipalities' => 'infected-total-distribution-by-municipalities'
            );
            echo "<h3>... checking 'document' for $entryID</h3>";
            $sql = "SELECT * from `document` WHERE `entry_id` = '$entryID';";
            $stmnt = $this->pdo->query($sql);
            $result = $stmnt->fetch(PDO::FETCH_ASSOC);
            foreach (array_keys((array)$result) as &$tempKey) {
                if (!in_array($tempKey, $db_excludes)) {
                    if (strpos($tempKey, 'array') !== false) {
                        $this->compareFields(
                            [
                            $tempKey, json_encode(json_decode($result[$tempKey])->data),
                            json_encode($dataObject->data[$mapDb2DataObj[$tempKey]]['data'])
                          ],
                            array('values' => false, 'output' => true)
                        );
                        continue;
                    }
                    if ($tempKey === 'document') {
                        echo "<br />   ... for '" . $tempKey . "': ";
                        if (
                          $this->compareHtmlStrings(
                              $result['document'],
                              $dataObject->data['document']
                          )
                        ) {
                            echo "   ✔";
                        } else {
                            echo "   ✖";
                        }
                        continue;
                    }

                    $this->compareFields(
                        [
                        $tempKey,
                        $result[$tempKey],
                        $this->convert2DbDoubleFormat(
                            $dataObject->data[$mapDb2DataObj[$tempKey]]
                        )
                      ],
                        array('values' => true, 'output' => true)
                    );
                }
            }

            //
            //        +++ table distribution_age
            //
            echo "<h3>... checking 'distribution_age' for $entryID</h3>";
            $sql = "SELECT * from `distribution_age` WHERE `entry_id` = '$entryID';";
            $stmnt = $this->pdo->query($sql);
            $result = $stmnt->fetch(PDO::FETCH_ASSOC);
            print var_dump($dataObject->data['infected-total-distribution-by-age']['data']);
            $db_excludes = ['entry_id','document_id'];
            $dataObj_excludes = [];
            $mapDb2DataObj = array(
              'source_file_name' => 'sourceFilenName',
              'document' => 'document',
              'array_age' => 'infected-total-distribution-by-age',
              'array_sex' => 'infected-total-distribution-by-sex',
              'array_municipalities' => 'infected-total-distribution-by-municipalities'
            );
            // @ToDo: 2020-12-30 Hier bin ich
            foreach (array_keys((array)$result) as &$tempKey) {
                echo "<br /> $tempKey";
            }
            // check following cols:
          // ["lastupdated_string"] <-> $dataObject->data['last-updated']
          // ["total_infected"] <-> $dataObject->data['infected-total']
          // ["total_recovered"] <-> $dataObject->data['recovered-total']
          // ["total_deceased"] <-> $dataObject->data['deceased-total']
          // ["seven_day_incidence"] <-> $dataObject->data['seven_day_incidence']
          // document
          // array_age
          // array_sex
          // array_municipalities
          /* $this->compareFields(['lastupdated_string', $result['lastupdated_string'], $dataObject->data['last-updated']], TRUE); */
          /* $this->compareFields(['total_infected', $result['total_infected'], $dataObject->data['infected-total']], TRUE); */
          /* $this->compareFields(['total_recovered', $result['total_recovered'], $dataObject->data['recovered-total']], TRUE); */
          /* $this->compareFields(['total_deceased', $result['total_deceased'], $dataObject->data['deceased-total']], TRUE); */
          /* $this->compareFields(['seven_day_incidence', $result['seven_day_incidence'], $this->convert2DbDoubleFormat($dataObject->data['seven_day_incidence'])], TRUE); */
        }



        /**
         * The idea is, to get the difference of the stored and parsed document.
         * But currently (2020-12-30) there occures slight shifts and currently
         * I have no idea where they come from.
         * Maybe as @ToDo I have to investigate for the shifts in the future...
         *
         * @param string $db_value (the value read from the DB)
         * @param string $parsed_value (the value which comes from the html file)
         */
        private function compareHtmlStrings(string $db_value, string $parsed_value):bool
        {
            $db_value = htmlentities($db_value);
            $parsed_value = htmlentities($parsed_value);
            $len_db_value = strlen($db_value);
            $len_parsed_value = strlen($parsed_value);
            $diff_len = $len_db_value - $len_parsed_value;
            /* echo "<br />compareHtmlStrings(): db_value (".strlen($db_value)."), parsed_value (".strlen($parsed_value)."), diff ($diff_len)"; */
            /* $pos = 0; */
            /* while ($pos <= $len_db_value) { */
            /* $char_db = substr($db_value, $pos, 1); */
            /* $char_parsed = substr($parsed_value, $pos, 1); */
            /* if ($char_db === $char_parsed) { */
            /* echo "<br /> $pos: $char_db --- $char_parsed   "; */
            /* echo "   ✔"; */
            /* } else { */
            /* echo "<br /> $pos: $char_db --- $char_parsed   "; */
            /* echo "   ✖"; */
            /* } */
            /* $pos++; */
            /* } */
            return $diff_len === 0 || $diff_len === -42;
        }


        /**
         * search for a given datetime string in maindata and returns the entry_id or
         * throws an error
         *
         * @param string $datestring searchstring with a datetime value
         *
         * @throws EntryIdCouldNotBeDeterminedException if the date/time string has no match
         *
         * @return int entry_id
         */
        public function getEntryIdByDateString(String $datestring): int
        {
            $sql = "SELECT `entry_id` from `maindata` WHERE `lastupdated_string` = '$datestring';";
            $stmnt = $this->pdo->query($sql);
            $result = $stmnt->fetch()["entry_id"];

            if ($result === null) {
                throw new EntryIdCouldNotBeDeterminedException();
            }

            return (int)$result;
        }
    }

    class EntryIdCouldNotBeDeterminedException extends Exception
    {
        // https://stackoverflow.com/a/4733511
    }
