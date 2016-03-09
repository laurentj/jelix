<?php
/**
* @package    jelix-scripts
* @author     Laurent Jouanneau
* @copyright  2011-2015 Laurent Jouanneau
* @link       http://jelix.org
* @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

class JelixScript {

    static $debugMode = false;

    /**
     * load the configuration of jelix-scripts
     * @param string $appname the application name
     * @return Jelix\DevHelper\CommandConfig
     */
    static function loadConfig($appname='') {
        $config = new \Jelix\DevHelper\CommandConfig();

        if ($appname === '')
            $appname = $config->loadFromProject();
        else if ($appname === false) // don't load from project..
            $appname = '';

        // try to find a .jelix-scripts.ini in the current directory or parent directories
        $dir = getcwd();
        $found = false;
        do {
            if (file_exists($dir.DIRECTORY_SEPARATOR.'.jelix-scripts.ini')) {
                $config->loadFromIni($dir.DIRECTORY_SEPARATOR.'.jelix-scripts.ini', $appname);
                $found = true;
            }
            else if (file_exists($dir.DIRECTORY_SEPARATOR.'jelix-scripts.ini')) {
                $config->loadFromIni($dir.DIRECTORY_SEPARATOR.'jelix-scripts.ini', $appname); // windows users don't often use dot files.
                $found = true;
            }
            $previousdir = $dir;
            $dir = dirname($dir);
        }
        while($dir != '.' && $dir != $previousdir && !$found);

        // we didn't find a .jelix-scripts, try to read one from the home directory
        if (!$found) {
            $home = '';
            if (isset($_SERVER['HOME'])) {
                $home = $_SERVER['HOME'];
            }
            else if (isset($_ENV['HOME'])) {
                $home = $_ENV['HOME'];
            }
            else if (isset($_SERVER['USERPROFILE'])) { // windows
                $home = $_SERVER['USERPROFILE'];
            }
            else if (isset($_SERVER['HOMEDRIVE']) && isset($_SERVER['HOMEPATH'])) { // windows
                $home = $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
            }

            if ($home) {
                if (file_exists($home.DIRECTORY_SEPARATOR.'.jelix-scripts.ini'))
                    $config->loadFromIni($home.DIRECTORY_SEPARATOR.'.jelix-scripts.ini', $appname);
                else
                    $config->loadFromIni($home.DIRECTORY_SEPARATOR.'jelix-scripts.ini', $appname); // windows users don't often use dot files.
            }
        }

        self::$debugMode = $config->debugMode;

        if (function_exists('date_default_timezone_set')){
           date_default_timezone_set($config->infoTimezone);
        }

        $config->appName = $appname;
        return $config;
    }

    static function commandList() {
        $list=array();
        $dir = JELIX_SCRIPTS_PATH.'commands/';
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if(is_file($dir . $file) && strpos($file,'.cmd.php') !==false){
                   $list[]=substr($file,0, -8);
                }
            }
            closedir($dh);
        }
        return $list;
    }

    static function checkTempPath() {
        $tempBasePath = jApp::tempBasePath();

        // we always clean the temp directory. But first, let's check the temp path (see ticket #840)...

        if ($tempBasePath == DIRECTORY_SEPARATOR || $tempBasePath == '' || $tempBasePath == '/') {
            throw new Exception("Error: bad path in jApp::tempBasePath(), it is equals to '".$tempBasePath."' !!\n".
                                "       Jelix cannot clear the content of the temp directory.\n".
                                "       Correct the path for the temp directory or create the directory you\n".
                                "       indicated with jApp in your application.init.php.\n");
        }
        jFile::removeDir(jApp::tempPath(), false, array('.svn', '.dummy'));
    }

    static function showError($errcode, $errmsg, $filename, $linenum, $trace, $errno=E_ERROR) {
        $codeString = array(
            E_ERROR         => 'error',
            E_RECOVERABLE_ERROR => 'error',
            E_WARNING       => 'warning',
            E_NOTICE        => 'notice',
            E_DEPRECATED    => 'deprecated',
            E_USER_ERROR    => 'error',
            E_USER_WARNING  => 'warning',
            E_USER_NOTICE   => 'notice',
            E_USER_DEPRECATED => 'deprecated',
            E_STRICT        => 'strict'
         );

        if (isset ($codeString[$errno])){
           $codestr = $codeString[$errno];
        }
        else {
           $codestr = 'error';
        }

        if (self::$debugMode) {
            $messageLog = strtr("\n[%typeerror%:%code%]\t%msg%\t%file%\t%line%\n", array(
               '%date%' => date("Y-m-d H:i:s"),
               '%typeerror%'=>$codestr,
               '%code%' => $errcode,
               '%msg%'  => $errmsg,
               '%file%' => $filename,
               '%line%' => $linenum,
            ));
            $traceLog = '';
            $messageLog.="\ttrace:";
            foreach($trace as $k=>$t){
                $traceLog.="\n\t$k\t".(isset($t['class'])?$t['class'].$t['type']:'').$t['function']."()\t";
                $traceLog.=(isset($t['file'])?$t['file']:'[php]').' : '.(isset($t['line'])?$t['line']:'');
            }
            $messageLog.=$traceLog."\n";
        }
        else {
            $messageLog = strtr("\n[%typeerror%:%code%]\t%msg%\n", array(
                '%typeerror%'=>$codestr,
                '%code%' => $errcode,
                '%msg%'  => $errmsg,
             ));
        }

        echo $messageLog;
        if ($codestr == 'error')
           exit(1);
    }

}


function JelixScriptsErrorHandler($errno, $errmsg, $filename, $linenum, $errcontext){

    if (error_reporting() == 0)
        return;

    if (preg_match('/^\s*\((\d+)\)(.+)$/',$errmsg,$m)) {
       $code = $m[1];
       $errmsg = $m[2];
    }
    else {
       $code=1;
    }

    $trace = debug_backtrace();
    array_shift($trace);

    JelixScript::showError($code, $errmsg, $filename, $linenum, $trace, $errno);
}

function JelixScriptsExceptionHandler($e){

    JelixScript::showError($e->getCode(), $e->getMessage(), $e->getFile(),
                          $e->getLine(), $e->getTrace());

}
