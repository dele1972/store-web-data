<?php
    class AppConfig {
        
        protected static $_instance = null;
        
        static $htmlFileInputPath = '../samples/';
        static $htmlFileOutPath = '../processed/';
        static $htmlFileOutDuplicatePath = '../processed/duplicate/';
        static $htmlFileOutErrorPath = '../processed/error/';

        static $db_host = "localhost";
        static $db_name = "store-web-data";
        static $db_user = "root";
        static $db_password = "";
        static $db_collation = "utf8mb4_unicode_ci";
        
        
        public static function getInstance() {
            print "GET INSTANCE";
            if (null === self::$_instance)
            {
                self::$_instance = new self;
            }
            return self::$_instance;
        }
        
        
        protected function __clone() {}
   
        /**
         * constructor
         *
         * externe Instanzierung verbieten
         */
        protected function __construct() {}
    }
?>