<?php

namespace Categories\Controller;

use Categories\Model;
use Categories\Table;
use Phire\Controller\AbstractController;

class IndexController extends AbstractController
{

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $uri = substr($this->request->getRequestUri(), 9);

        if ($uri != '/') {
            $category = new Model\Category();
            $category->show_total = $this->application->module('Categories')['show_total'];
            $category->getByUri($uri, $this->application->modules()->isRegistered('Fields'));

            if (isset($category->id)) {
                $this->prepareView('categories-public/category.phtml');
                $this->view->title = 'Category : ' . $category->title;
                $this->view->merge($category->toArray());
                $this->send();
            } else {
                $this->error();
            }
        } else {
            $this->redirect(((BASE_PATH == '') ? '/' : BASE_PATH));
        }
    }

    /**
     * Error action method
     *
     * @return void
     */
    public function error()
    {
        $this->prepareView('categories-public/error.phtml');
        $this->view->title = '404 Error';
        $this->template    = -1;
        $this->send(404);
    }

    /**
     * Prepare view
     *
     * @param  string $category
     * @return void
     */
    protected function prepareView($category)
    {
        $this->viewPath = __DIR__ . '/../../view';
        parent::prepareView($category);
    }

}
