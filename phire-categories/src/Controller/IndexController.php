<?php

namespace Phire\Categories\Controller;

use Phire\Categories\Model;
use Phire\Categories\Table;
use Phire\Controller\AbstractController;
use Pop\Paginator\Paginator;

class IndexController extends AbstractController
{

    /**
     * Current template reference
     * @var mixed
     */
    protected $template = null;

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $uri = substr($this->request->getRequestUri(), 9);

        if ($uri != '/') {
            if (substr($uri, -1) == '/') {
                $uri = substr($uri, 0, -1);
            }
            $category = new Model\Category();
            $category->settings       = $this->application->module('phire-categories')['settings'];
            $category->summary_length = $this->application->module('phire-categories')['summary_length'];
            $category->date_fields    = $this->application->module('phire-categories')['date_fields'];
            $category->show_total     = $this->application->module('phire-categories')['show_total'];
            $category->nav_config     = $this->application->module('phire-categories')['nav_config'];
            $category->getByUri($uri, $this->application->modules());

            if (isset($category->id)) {
                if (count($category->items) > $this->config->pagination) {
                    $page  = $this->request->getQuery('page');
                    $limit = $this->config->pagination;
                    $pages = new Paginator(count($category->items), $limit);
                    $pages->useInput(true);
                    $offset = ((null !== $page) && ((int)$page > 1)) ?
                        ($page * $limit) - $limit : 0;
                    $category->items = array_slice($category->items, $offset, $limit, true);
                } else {
                    $pages = null;
                }

                $this->prepareView('categories-public/category.phtml');
                $this->template = 'category.phtml';
                $this->view->title          = 'Category : ' . $category->title;
                $this->view->category_id    = $category->id;
                $this->view->category_title = $category->title;
                $this->view->category_slug  = $category->slug;

                $this->view->pages          = $pages;
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
     * Get current template
     *
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set current template
     *
     * @param  string $template
     * @return IndexController
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
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
