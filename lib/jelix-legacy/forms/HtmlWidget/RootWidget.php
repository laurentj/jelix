<?php
/**
* @package     jelix
* @subpackage  forms
* @author      Laurent Jouanneau
* @copyright   2006-2012 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/
namespace jelix\forms\HtmlWidget;

class RootWidget implements ParentWidgetInterface {

    //------ ParentWidgetInterface

    protected $js = '';
    function addJs($js) {
        $this->js .= $js;
    }

    protected $finalJs = '';
    function addFinalJs($js) {
        $this->finalJs .= $js;
    }

    function controlJsChild() {
        return false;
    }

    //------ Other methods

    /**
     * @var \jelix\forms\Builder\HtmlBuilder
     */
    protected $builder;

    /**
     * @param \jelix\forms\Builder\HtmlBuilder $builder
     */
    public function outputHeader($builder) {
        $jsVarName = $builder->getjFormsJsVarName();

        $js = $jsVarName.'.tForm = new jFormsForm(\''.$builder->getName()."');\n";
        $js .= $jsVarName.'.tForm.setErrorDecorator(new '.$builder->getOption('errorDecorator')."())\n";
        $js .= $jsVarName.".declareForm(jForms.tForm);\n";
        $this->addJs($js);
        $this->builder = $builder;
    }

    public function outputFooter() {
        $js = "(function(){var c, c2;\n".$this->js.$this->finalJs."})();";
        $container = $this->builder->getForm()->getContainer();
        $container->privateData['__jforms_js'] = $js;
        $formId = $container->formId;
        $formName = $this->builder->getForm()->getSelector();
        echo '<script type="text/javascript" src="'.\jUrl::get("jelix~jforms:js",
                array('__form'=>$formName, '__fid' =>$formId)).'"></script>';
    }
}

