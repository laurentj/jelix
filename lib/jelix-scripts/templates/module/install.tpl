<?php
/**
* @package   %%appname%%
* @subpackage %%module%%
* @author    %%default_creator_name%%
* @copyright %%default_copyright%%
* @link      %%default_website%%
* @license   %%default_license_url%% %%default_license%%
*/


class %%module%%ModuleInstaller extends jInstallerModule2 {

    function install() {
        //$this->execSQLScript('sql/install');

        /*
        jAcl2DbManager::addSubject('my.subject', '%%module%%~acl.my.subject', 'subject.group.id');
        jAcl2DbManager::addRight('admins', 'my.subject'); // for admin group
        */
    }
}