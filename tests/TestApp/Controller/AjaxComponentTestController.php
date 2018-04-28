<?php
namespace TestApp\Controller;

use Tools\Controller\Controller;

/**
 * Use Controller instead of AppController to avoid conflicts
 *
 * @property \Tools\Controller\Component\CommonComponent $Common
 */
class AjaxComponentTestController extends Controller {

    /**
     * @var array
     */
    public $components = ['Tools.Ajax'];

    /**
     * @var array
     */
    public $autoRedirectActions = ['allowed'];

}
