<?php
/**
* @author     Laurent Jouanneau
* @copyright  2014 Laurent Jouanneau
* @link       http://jelix.org
* @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/
namespace Jelix\Core\Infos;

abstract class InfosAbstract {

    /** @var string the path to the module/app */
    protected $path = '';
    protected $isXml = false;
    protected $_exists = false;

    /** @var string the name of the module, used as identifier in jelix selectors or other part of the code */
    public $name = '';

    /** @var string the birth date of the module/app. optional */
    public $createDate = '';

    /** @var string version of the module/app. required for modules */
    public $version = '';
    /** @var string the release date of the module/app. required for modules */
    public $versionDate = '';
    
    public $versionStability = '';

    public $label = '';

    public $description = '';

    /**
     * @var array of array('name'=>'', 'email'=>'', 'role'=>'', 'homepage"=>'', 'nickname'=>'')
     */
    public $authors = array();
    public $notes = '';
    public $homepageURL = '';
    public $updateURL = '';
    public $license = '';
    public $licenseURL = '';
    public $copyright = '';


    /**
     * @return string the path of the component, with trailing slash
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function isXmlFile() {
        return $this->isXml;
    }

    /**
     * @return bool
     */
    public function exists() {
        return $this->_exists;
    }

    /**
     * @return string the file name
     */
    public abstract function getFile();
}