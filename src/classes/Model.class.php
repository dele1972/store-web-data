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

    class Model {

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


        function __construct($databaseCred) {

            $conStr = sprintf("mysql:host=%s;dbname=%s;", $databaseCred['host'], $databaseCred['name']);

            try {

                $this->pdo = new PDO($conStr, $databaseCred['user'], $databaseCred['password']);
                // we need to disable the pdo silent mode to be able to react on errors (https://stackoverflow.com/a/32648423)
                $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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


        public function __destruct() {

            // close the database connection
            $this->pdo = null;

        }


        private function createMainDataTable() {
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


        private function createDocumentTable() {
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


        private function createDistributionAgeTable() {
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


        private function createDistributionSexTable() {
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


        private function createDistributionMunicipalitiesTable() {
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

        private function last_substr_replace(string $search, string $replace, string $subject): string {
            
            $lastIndex = strrpos($subject, $search);
            $subject = substr_replace($subject, $replace, $lastIndex, strlen($search));
            return $subject;

        }


        private function insertMainData(CoronaDataFromHtml $dataObject) {

            $unixtimestamp = strtotime($dataObject->data['last-updated']);
            $cet = new DateTime(date_create_from_format('U',$unixtimestamp,new DateTimeZone('CET'))->format('d.m.Y H:i'));

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

            return TRUE;

        }


        private function insertDistributionAgeData(CoronaDataFromHtml $dataObject) {

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

            return TRUE;

        }


        private function insertDistributionMunicipalitiesData(CoronaDataFromHtml $dataObject) {

            // sql statement - head
            $sql = "INSERT INTO distribution_municipalities ( entry_id,";

            // @ToDo: HIER BIN ICH - bei älteren Datensätzen darf _sevendayin nicht im Statement vorkommen
            // [ ] col
            // [ ] values
            // [ ] bind
                echo "<h1>Hier bin ich dran: insertDistributionMunicipalitiesData()" . $dataObject->data['infected-total-distribution-by-municipalities']['data'][0][1] . "</h1>";
                print var_dump($dataObject->data['infected-total-distribution-by-municipalities']['data'][0]);
                // use this array count, if 3 then prevent use of _sevendayin
                print "<br/>array count = " . count($dataObject->data['infected-total-distribution-by-municipalities']['data'][0]);
                print "<hr />"; 
            // sql statement - COLUMNS 
            foreach ($this->municipalities as $element) {
                $sql .= $element . "_current,";
                $sql .= $element . "_sincebegin,";
                $sql .= $element . "_sevendayin,";
            };

            $sql = $this->last_substr_replace(",", ") ", $sql);

            // sql statement - VALUES 
            $sql .= "VALUES (:entry_id,";

            foreach ($this->municipalities as $element) {
                $sql .= ":" . $element . "_current,";
                $sql .= ":" . $element . "_sincebegin,";
                $sql .= ":" . $element . "_sevendayin,";
            };

            $sql = $this->last_substr_replace(",", ");", $sql);
            $stmt = $this->pdo->prepare($sql);

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
                $stmt->bindParam(
                    ':' . $element . '_sevendayin',
                    str_replace(
                      ",",
                      ".",
                      $dataObject->data['infected-total-distribution-by-municipalities']['data'][$key][3]
                    )
                );

            };

            try {

                $stmt->execute();

            } catch (PDOException $e) {

                throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);

            }

            return TRUE;

        }


        private function insertDistributionSexData(CoronaDataFromHtml $dataObject) {

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
                    ':male'   => (float) str_replace(" Prozent","", $dataObject->data['infected-total-distribution-by-sex']['data'][0][1]),
                    ':female'   => (float) str_replace(" Prozent","", $dataObject->data['infected-total-distribution-by-sex']['data'][1][1]),
                    ':not_specified'   => (float) str_replace(" Prozent","", $dataObject->data['infected-total-distribution-by-sex']['data'][2][1])
                ]);

            } catch (PDOException $e) {

                throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);

            }

            return TRUE;

        }


        private function insertDocumentData(CoronaDataFromHtml $dataObject) {

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

            return TRUE;

        }


        public function storeData(CoronaDataFromHtml $dataObject) {

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
    }
?>
