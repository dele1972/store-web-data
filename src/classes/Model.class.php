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
        const DB_HOST = "localhost";
        const DB_NAME = "store-web-data";
        const DB_USER = "root";
        const DB_PASSWORD = "";
        const DB_COLLATION = "utf8mb4_unicode_ci";


        function __construct() {

            $conStr = sprintf("mysql:host=%s;dbname=%s", self::DB_HOST, self::DB_NAME);

            try {

                $this->pdo = new PDO($conStr, self::DB_USER, self::DB_PASSWORD);
                // we need to disable the pdo silent mode to be able to react on errors (https://stackoverflow.com/a/32648423)
                $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                
                // create Tables if not exists
                $this->createMainDataTable();
                $this->createDocumentTable();
                $this->createDistributionAgeTable();
                $this->createDistributionSexTable();
                $this->createDistributionMunicipalitiesTable();

            } catch (PDOException $e) {
                
                echo $e->getMessage();
                
                throw new Exception("PDO Exception");
                // @ToDo: do a log if a query could not find the xpath in the document - e.g.:
                //        Argument 1 passed to CoronaDataFromHtml::getNodeValue() must be an instance of DOMNodeList, bool given, called in /home/lederich/dev/web/domains/lederich.de/htdocs/store-web-data/src/CoronaDataFromHtml.php on line 28
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
                    document                MEDIUMTEXT DEFAULT NULL,
                    array_age               JSON DEFAULT NULL,
                    array_sex               JSON DEFAULT NULL,
                    array_municipalities    JSON DEFAULT NULL
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
                    20_to_19        MEDIUMINT UNSIGNED DEFAULT NULL,
                    30_to_19        MEDIUMINT UNSIGNED DEFAULT NULL,
                    40_to_19        MEDIUMINT UNSIGNED DEFAULT NULL,
                    50_to_19        MEDIUMINT UNSIGNED DEFAULT NULL,
                    60_to_19        MEDIUMINT UNSIGNED DEFAULT NULL,
                    70_to_19        MEDIUMINT UNSIGNED DEFAULT NULL,
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
                    male            MEDIUMINT UNSIGNED DEFAULT NULL,
                    female          MEDIUMINT UNSIGNED DEFAULT NULL,
                    not_specified   MEDIUMINT UNSIGNED DEFAULT NULL
                );
                EOSQL;
            return $this->pdo->exec($sql);
        }


        private function createDistributionMunicipalitiesTable() {
            $sql = <<<EOSQL
                CREATE TABLE IF NOT EXISTS distribution_municipalities (
                    dist_sex_id                             INT AUTO_INCREMENT PRIMARY KEY,
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

        
        private function privateDummy(DOMNodeList $resultingDOMNodeList, bool $tableWithColHeader = FALSE): array {

            return [];

        }


        public function publicDummy(bool $printWithTableDump = TRUE) {

            echo "<p>Model</p>";

        }



        private function insertMainData(CoronaDataFromHtml $dataObject) {
            $stmt = $this->pdo->prepare("
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
                ;
            ");
            
            $unixtimestamp = strtotime($dataObject->data['last-updated']);
            $cet = new DateTime(date_create_from_format('U',$unixtimestamp,new DateTimeZone('CET'))->format('d.m.Y H:i'));
            
            try {
                print $stmt->execute([
                    ':lastupdated_tmstmp' => $unixtimestamp,
                    ':lastupdated_string' => $dataObject->data['last-updated'],
                    ':total_infected' => $dataObject->data['infected-total'],
                    ':total_recovered' => $dataObject->data['recovered-total'],
                    ':total_deceased' => $dataObject->data['deceased-total']
                ]);
            } catch (PDOException $e) {
                // print "<h2>PDO Exception ".$e->getCode()." --- ". $this->pdo->errorCode() . "</h2>";
                // 23000 sql state failure on insert
                // 1062 error, duplicate entry
                // print_r ("Error: " . $stmt->errorInfo()[1] . "<br />");
                // print_r ("Message: " . $stmt->errorInfo()[2] . "<br />");
                
                // @ToDo: throw an error for the main calling instance of this model class
                throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);

            } catch (Exception $e) {
                // print "<h2>Exception</h2>";
                // print var_dump($e);
            } catch (Error $e) {
                // print "<h2>Error</h2>";
                // print var_dump($e);
            }

            return TRUE;

        }


        public function storeData(CoronaDataFromHtml $dataObject) {

            try {

                $this->insertMainData($dataObject);

            } catch (Exception $e) {

                throw new Exception($e->getMessage(), $e->getCode());
                //print var_dump($e->getMessage());
                //print var_dump($e->getCode());
                //print_r ("Error2: " . $e->errorinfo()[1] . "<br />");
                //print_r ("Message2: " . $e[1] . "<br />");

            }

        }
    }
?>
