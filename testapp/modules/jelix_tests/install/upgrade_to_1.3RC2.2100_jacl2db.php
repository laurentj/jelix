<?php
/**
* @package     jelix
* @subpackage  jacl2db module
* @author      Laurent Jouanneau
* @copyright   2011 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

class jelix_testsModuleUpgrader_jacl2db extends jInstallerModule2 {

    protected $defaultDbProfile = 'testapp_pgsql';

    function installEntrypoint(\Jelix\Installer\EntryPoint $entryPoint) {
        if (!$this->firstDbExec())
            return;
        try {
            $dbprofile = jProfiles::get('jdb', 'testapp_pgsql', true);
            $this->useDbProfile('testapp_pgsql');
        }
        catch(Exception $e) {
            // no profile for pgsql, don't install tables in pgsql
            return;
        }
        $this->execSQLScript('install_jacl2.schema', 'jacl2db');
    }
}