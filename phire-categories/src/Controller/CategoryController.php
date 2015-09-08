<?php

namespace Phire\Categories\Controller;

use Phire\Categories\Model;
use Phire\Categories\Form;
use Phire\Categories\Table;
use Phire\Controller\AbstractController;

class CategoryController extends AbstractController
{

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $this->prepareView('categories/index.phtml');
        $categories = new Model\Category();
        $categories->getAll($this->request->getQuery('sort'));

        $this->view->title      = 'Categories';
        $this->view->categories = $categories->getFlatMap();

        $this->send();
    }

    /**
     * Add action method
     *
     * @return void
     */
    public function add()
    {
        $this->prepareView('categories/add.phtml');
        $this->view->title = 'Categories : Add';

        $category = new Model\Category();
        $category->getAll();

        $fields = $this->application->config()['forms']['Phire\Categories\Form\Category'];

        $parents = [];
        foreach ($category->getFlatMap() as $c) {
            $parents[$c->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $c->depth) .
                (($c->depth > 0) ? '&rarr; ' : '') . $c->title;
        }

        $fields[0]['category_parent_id']['value']    = $fields[0]['category_parent_id']['value'] + $parents;
        $fields[1]['slug']['attributes']['onkeyup']  = "phire.changeCategoryUri();";
        $fields[1]['title']['attributes']['onkeyup'] = "phire.createSlug(this.value, '#slug'); phire.changeCategoryUri();";

        $this->view->form = new Form\Category($fields);

        if ($this->request->isPost()) {
            $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $category = new Model\Category();
                $category->save($this->view->form->getFields());
                $this->view->id = $category->id;
                $this->sess->setRequestValue('saved', true, 1);
                $this->redirect(BASE_PATH . APP_URI . '/categories/edit/'. $category->id);
            }
        }

        $this->send();
    }

    /**
     * Edit action method
     *
     * @param  int $id
     * @return void
     */
    public function edit($id)
    {
        $category = new Model\Category();
        $category->getById($id);

        if (!isset($category->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/categories');
        }

        $categories = new Model\Category();
        $categories->getAll();

        $parents = [];
        foreach ($categories->getFlatMap() as $c) {
            if ($c->id != $id) {
                $parents[$c->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $c->depth) .
                    (($c->depth > 0) ? '&rarr; ' : '') . $c->title;
            }
        }

        $this->prepareView('categories/edit.phtml');
        $this->view->title          = 'Categories';
        $this->view->category_title = $category->title;

        $fields = $this->application->config()['forms']['Phire\Categories\Form\Category'];

        $fields[0]['category_parent_id']['value'] = $fields[0]['category_parent_id']['value'] + $parents;
        $fields[1]['slug']['label']     .=
            ' [ <a href="#" class="small-link" onclick="phire.createSlug(jax(\'#title\').val(), \'#slug\');' .
            ' return phire.changeCategoryUri();">Generate URI</a> ]';

        $fields[1]['title']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';
        $fields[1]['slug']['attributes']['onkeyup']  = "phire.changeCategoryUri();";

        $this->view->form = new Form\Category($fields);
        $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
             ->setFieldValues($category->toArray());

        if ($this->request->isPost()) {
            $this->view->form->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $category = new Model\Category();

                $category->update($this->view->form->getFields());
                $this->view->id = $category->id;
                $this->sess->setRequestValue('saved', true, 1);
                $this->redirect(BASE_PATH . APP_URI . '/categories/edit/'. $category->id);
            }
        }

        $this->send();
    }

    /**
     * View action method
     *
     * @param  int $id
     * @return void
     */
    public function viewContent($id)
    {
        $category = new Model\Category();
        $category->getById($id);

        if (!isset($category->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/categories');
        }

        $category->settings       = $this->application->module('phire-categories')['settings'];
        $category->summary_length = $this->application->module('phire-categories')['summary_length'];
        $category->show_total     = $this->application->module('phire-categories')['show_total'];

        $this->prepareView('categories/view.phtml');

        $this->view->title   = 'Categories : ' . $category->title;
        $this->view->cid     = $category->id;
        $this->view->content = $category->getCategoryContent($id, null, $this->application->isRegistered('phire-fields'));
        $this->send();
    }

    /**
     * JSON action method
     *
     * @param  int    $id
     * @param  string $type
     * @return void
     */
    public function json($id, $type = null)
    {
        $json = [];
        if (null !== $type) {
            $c2c = Table\ContentToCategories::findBy(['content_id' => $id, 'type' => $type]);
            foreach ($c2c->rows() as $c) {
                if ($c->order > 0) {
                    $json['category_order_' . $c->category_id] = $c->order;
                }
            }
        } else {
            $json['parent_uri'] = '';
            $content = Table\Categories::findById($id);
            if (isset($content->id)) {
                $json['parent_uri'] = $content->uri;
            }
        }

        $this->response->setBody(json_encode($json, JSON_PRETTY_PRINT));
        $this->send(200, ['Content-Type' => 'application/json']);
    }

    /**
     * Process action method
     *
     * @return void
     */
    public function process()
    {
        if ($this->request->isPost()) {
            $category = new Model\Category();
            $category->process($this->request->getPost());
        }
        $this->sess->setRequestValue('saved', true, 1);
        $this->redirect(BASE_PATH . APP_URI . '/categories/view/' . $this->request->getPost('category_id'));
    }

    /**
     * Remove action method
     *
     * @return void
     */
    public function remove()
    {
        if ($this->request->isPost()) {
            $category = new Model\Category();
            $category->remove($this->request->getPost());
        }
        $this->sess->setRequestValue('removed', true, 1);
        $this->redirect(BASE_PATH . APP_URI . '/categories');
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
