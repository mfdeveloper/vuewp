<?php

    namespace WpAutomation;
    
    use Composer\Script\Event;
    
    class Tests {

        protected static $baseDir = 'scripts/WpAutomation';
        
        public static function install(Event $event)
        {
            $rootDir = str_replace(self::$baseDir , '', dirname(__FILE__));
            $pluginsDir = new \DirectoryIterator($rootDir.'wp-content/plugins');

            foreach ($pluginsDir as $dir) {
                if($dir->isDot()) continue;

                if ($dir->isDir() && file_exists($dir->getRealPath().'/bin/install-wp-tests.sh')) {
                    // install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
                    echo 'Installing tests of: '.$pluginsDir->getBasename(). " plugin\n";
                    $command = $dir->getRealPath()."/bin/install-wp-tests.sh {$_ENV['DB_TEST_NAME']} root {$_ENV['DB_ROOT_PASSWORD']} {$_ENV['DB_TEST_HOST']}";
                    echo shell_exec($command);
        
                }
            }
        }
    }
