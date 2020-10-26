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
                    dist_municapilities_id                             INT AUTO_INCREMENT PRIMARY KEY,
                    entry_id                                INT NOT NULL,
                    Barsinghausen_current                   MEDIUMINT UNSIGNED DEFAULT NULL,
                    Barsinghausen_sincebegin                MEDIUMINT UNSIGNED DEFAULT NULL,
                    Burgdorf_current                        MEDIUMINT UNSIGNED DEFAULT NULL,
                    Burgdorf_sincebegin                     MEDIUMINT UNSIGNED DEFAULT NULL,
                    Burgwedel_current                       MEDIUMINT UNSIGNED DEFAULT NULL,
                    Burgwedel_sincebegin                    MEDIUMINT UNSIGNED DEFAULT NULL,
                    Garbsen_current                         MEDIUMINT UNSIGNED DEFAULT NULL,
                    Garbsen_sincebegin                      MEDIUMINT UNSIGNED DEFAULT NULL,
                    Gehrden_current                         MEDIUMINT UNSIGNED DEFAULT NULL,
                    Gehrden_sincebegin                      MEDIUMINT UNSIGNED DEFAULT NULL,
                    Hemmingen_current                       MEDIUMINT UNSIGNED DEFAULT NULL,
                    Hemmingen_sincebegin                    MEDIUMINT UNSIGNED DEFAULT NULL,
                    Isernhagen_current                      MEDIUMINT UNSIGNED DEFAULT NULL,
                    Isernhagen_sincebegin                   MEDIUMINT UNSIGNED DEFAULT NULL,
                    Laatzen_current                         MEDIUMINT UNSIGNED DEFAULT NULL,
                    Laatzen_sincebegin                      MEDIUMINT UNSIGNED DEFAULT NULL,
                    Landeshauptstadt_Hannover_current       MEDIUMINT UNSIGNED DEFAULT NULL,
                    Landeshauptstadt_Hannover_sincebegin    MEDIUMINT UNSIGNED DEFAULT NULL,
                    Langenhagen_current                     MEDIUMINT UNSIGNED DEFAULT NULL,
                    Langenhagen_sincebegin                  MEDIUMINT UNSIGNED DEFAULT NULL,
                    Lehrte_current                          MEDIUMINT UNSIGNED DEFAULT NULL,
                    Lehrte_sincebegin                       MEDIUMINT UNSIGNED DEFAULT NULL,
                    Neustadt_a_Rbge_current                 MEDIUMINT UNSIGNED DEFAULT NULL,
                    Neustadt_a_Rbge_sincebegin              MEDIUMINT UNSIGNED DEFAULT NULL,
                    Pattensen_current                       MEDIUMINT UNSIGNED DEFAULT NULL,
                    Pattensen_sincebegin                    MEDIUMINT UNSIGNED DEFAULT NULL,
                    Ronnenberg_current                      MEDIUMINT UNSIGNED DEFAULT NULL,
                    Ronnenberg_sincebegin                   MEDIUMINT UNSIGNED DEFAULT NULL,
                    Seelze_current                          MEDIUMINT UNSIGNED DEFAULT NULL,
                    Seelze_sincebegin                       MEDIUMINT UNSIGNED DEFAULT NULL,
                    Sehnde_current                          MEDIUMINT UNSIGNED DEFAULT NULL,
                    Sehnde_sincebegin                       MEDIUMINT UNSIGNED DEFAULT NULL,
                    Springe_current                         MEDIUMINT UNSIGNED DEFAULT NULL,
                    Springe_sincebegin                      MEDIUMINT UNSIGNED DEFAULT NULL,
                    Uetze_current                           MEDIUMINT UNSIGNED DEFAULT NULL,
                    Uetze_sincebegin                        MEDIUMINT UNSIGNED DEFAULT NULL,
                    Wedemark_current                        MEDIUMINT UNSIGNED DEFAULT NULL,
                    Wedemark_sincebegin                     MEDIUMINT UNSIGNED DEFAULT NULL,
                    Wennigsen_current                       MEDIUMINT UNSIGNED DEFAULT NULL,
                    Wennigsen_sincebegin                    MEDIUMINT UNSIGNED DEFAULT NULL,
                    Wunstorf_current                        MEDIUMINT UNSIGNED DEFAULT NULL,
                    Wunstorf_sincebegin                     MEDIUMINT UNSIGNED DEFAULT NULL
                );
EOSQL;
            return $this->pdo->exec($sql);
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

            $stmt = $this->pdo->prepare("
            INSERT INTO distribution_municipalities (
                    entry_id,
                    Barsinghausen_current,
                    Barsinghausen_sincebegin,
                    Burgdorf_current,
                    Burgdorf_sincebegin,
                    Burgwedel_current,
                    Burgwedel_sincebegin,
                    Garbsen_current,
                    Garbsen_sincebegin,
                    Gehrden_current,
                    Gehrden_sincebegin,
                    Hemmingen_current,
                    Hemmingen_sincebegin,
                    Isernhagen_current,
                    Isernhagen_sincebegin,
                    Laatzen_current,
                    Laatzen_sincebegin,
                    Landeshauptstadt_Hannover_current,
                    Landeshauptstadt_Hannover_sincebegin,
                    Langenhagen_current,
                    Langenhagen_sincebegin,
                    Lehrte_current,
                    Lehrte_sincebegin,
                    Neustadt_a_Rbge_current,
                    Neustadt_a_Rbge_sincebegin,
                    Pattensen_current,
                    Pattensen_sincebegin,
                    Ronnenberg_current,
                    Ronnenberg_sincebegin,
                    Seelze_current,
                    Seelze_sincebegin,
                    Sehnde_current,
                    Sehnde_sincebegin,
                    Springe_current,
                    Springe_sincebegin,
                    Uetze_current,
                    Uetze_sincebegin,
                    Wedemark_current,
                    Wedemark_sincebegin,
                    Wennigsen_current,
                    Wennigsen_sincebegin,
                    Wunstorf_current,
                    Wunstorf_sincebegin
                )
                    VALUES (
                        :entry_id,
                        :Barsinghausen_current,
                        :Barsinghausen_sincebegin,
                        :Burgdorf_current,
                        :Burgdorf_sincebegin,
                        :Burgwedel_current,
                        :Burgwedel_sincebegin,
                        :Garbsen_current,
                        :Garbsen_sincebegin,
                        :Gehrden_current,
                        :Gehrden_sincebegin,
                        :Hemmingen_current,
                        :Hemmingen_sincebegin,
                        :Isernhagen_current,
                        :Isernhagen_sincebegin,
                        :Laatzen_current,
                        :Laatzen_sincebegin,
                        :Landeshauptstadt_Hannover_current,
                        :Landeshauptstadt_Hannover_sincebegin,
                        :Langenhagen_current,
                        :Langenhagen_sincebegin,
                        :Lehrte_current,
                        :Lehrte_sincebegin,
                        :Neustadt_a_Rbge_current,
                        :Neustadt_a_Rbge_sincebegin,
                        :Pattensen_current,
                        :Pattensen_sincebegin,
                        :Ronnenberg_current,
                        :Ronnenberg_sincebegin,
                        :Seelze_current,
                        :Seelze_sincebegin,
                        :Sehnde_current,
                        :Sehnde_sincebegin,
                        :Springe_current,
                        :Springe_sincebegin,
                        :Uetze_current,
                        :Uetze_sincebegin,
                        :Wedemark_current,
                        :Wedemark_sincebegin,
                        :Wennigsen_current,
                        :Wennigsen_sincebegin,
                        :Wunstorf_current,
                        :Wunstorf_sincebegin
                    )
                ;
            ");

            try {

                // @ToDo make bind statements in a loop and then an empty execute
                $stmt->execute([
                    ':entry_id' => $this->last_mainentry_id,
                    ':Barsinghausen_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][0][1],
                    ':Barsinghausen_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][0][2],
                    ':Burgdorf_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][1][1],
                    ':Burgdorf_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][1][2],
                    ':Burgwedel_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][2][1],
                    ':Burgwedel_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][2][2],
                    ':Garbsen_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][3][1],
                    ':Garbsen_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][3][2],
                    ':Gehrden_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][4][1],
                    ':Gehrden_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][4][2],
                    ':Hemmingen_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][5][1],
                    ':Hemmingen_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][5][2],
                    ':Isernhagen_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][6][1],
                    ':Isernhagen_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][6][2],
                    ':Laatzen_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][7][1],
                    ':Laatzen_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][7][2],
                    ':Landeshauptstadt_Hannover_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][8][1],
                    ':Landeshauptstadt_Hannover_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][8][2],
                    ':Langenhagen_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][9][1],
                    ':Langenhagen_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][9][2],
                    ':Lehrte_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][10][1],
                    ':Lehrte_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][10][2],
                    ':Neustadt_a_Rbge_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][11][1],
                    ':Neustadt_a_Rbge_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][1][2],
                    ':Pattensen_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][12][1],
                    ':Pattensen_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][12][2],
                    ':Ronnenberg_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][13][1],
                    ':Ronnenberg_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][13][2],
                    ':Seelze_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][14][1],
                    ':Seelze_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][14][2],
                    ':Sehnde_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][15][1],
                    ':Sehnde_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][15][2],
                    ':Springe_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][16][1],
                    ':Springe_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][16][2],
                    ':Uetze_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][17][1],
                    ':Uetze_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][17][2],
                    ':Wedemark_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][18][1],
                    ':Wedemark_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][18][2],
                    ':Wennigsen_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][19][1],
                    ':Wennigsen_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][19][2],
                    ':Wunstorf_current' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][20][1],
                    ':Wunstorf_sincebegin' => $dataObject->data['infected-total-distribution-by-municipalities']['data'][20][2]
            ]);

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
