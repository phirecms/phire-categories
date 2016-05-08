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
            $category->getByUri($uri);

            if (isset($category->id)) {
                $category->filters    = ($category->filter) ? $this->application->module('phire-categories')['filters'] : [];
                $category->show_total = $this->application->module('phire-categories')['show_total'];
                $category->datetime_formats = $this->application->module('phire-categories')['datetime_formats'];
                if (($category->pagination > 0) && ($category->hasPages($category->pagination))) {
                    $limit = $category->pagination;
                    $pages = new Paginator($category->getCount(), $limit);
                    $pages->useInput(true);
                } else {
                    $limit = null;
                    $pages = null;
                }

                $this->prepareView('categories-public/category.phtml');
                $this->template = 'category.phtml';
                $this->view->title          = 'Category : ' . $category->title;
                $this->view->category_id    = $category->id;
                $this->view->category_title = $category->title;
                $this->view->category_slug  = $category->slug;
                $this->view->category_nav   = $category->getNav($this->application->module('phire-categories')['nav_config']);
                $this->view->pages          = $pages;

                $this->view->category_breadcrumb = $category->getBreadcrumb(
                    $category->id, $this->application->module('phire-categories')['separator']
                );

                $this->view->items = $category->getItems($limit, $this->request->getQuery('page'));

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

        $this->view->date_format   = $this->application->module('phire-categories')['datetime_formats']['date_format'];
        $this->view->month_format  = $this->application->module('phire-categories')['datetime_formats']['month_format'];
        $this->view->day_format    = $this->application->module('phire-categories')['datetime_formats']['day_format'];
        $this->view->year_format   = $this->application->module('phire-categories')['datetime_formats']['year_format'];
        $this->view->time_format   = $this->application->module('phire-categories')['datetime_formats']['time_format'];
        $this->view->hour_format   = $this->application->module('phire-categories')['datetime_formats']['hour_format'];
        $this->view->minute_format = $this->application->module('phire-categories')['datetime_formats']['minute_format'];
        $this->view->minute_format = $this->application->module('phire-categories')['datetime_formats']['minute_format'];
        $this->view->separator     = $this->application->module('phire-categories')['separator'];
    }

}
