<?php

namespace DagaSmart\Access\Http\Controllers;

use DagaSmart\BizAdmin\Controllers\AdminController;

class AccessController extends AdminController
{
    public function index()
    {
        $page = $this->basePage()->body('Access Extension.');

        return $this->response()->success($page);
    }
}
