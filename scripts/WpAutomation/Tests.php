<?php

    namespace WpAutomation;
    
    use Composer\Script\Event;
    
    class Tests {

        protected static $baseDir = 'scripts/WpAutomation';
        protected static $pluginsWithTests = [];
        
        public static function install(Event $event)
        {
            $rootDir = str_replace(self::$baseDir, '', dirname(__FILE__));
            $pluginsDir = new \DirectoryIterator($rootDir . 'wp-content/plugins');

            foreach ($pluginsDir as $dir) {
                if($dir->isDot()) continue;

                if ($dir->isDir() && file_exists($dir->getRealPath().'/bin/install-wp-tests.sh')) {
                    // install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
                    echo 'Installing tests of: '.$pluginsDir->getBasename(). " plugin\n";
                    $command = $dir->getRealPath()."/bin/install-wp-tests.sh {$_ENV['DB_TEST_NAME']} root {$_ENV['DB_ROOT_PASSWORD']} {$_ENV['DB_TEST_HOST']}";
                    echo shell_exec($command);

                    self::$pluginsWithTests[$dir->getBasename()] = true;
        
                }
            }

            if ( count(self::$pluginsWithTests) === 0 ) {
                echo "No scripts /bin/install-wp-tests.sh for install phpunit into plugins folder were found. Please, run: 'wp scaffold plugin-tests' before";
            }
        }
    }
