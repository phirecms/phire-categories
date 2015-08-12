<?php

namespace Categories\Controller;

use Categories\Model;
use Categories\Form;
use Categories\Table;
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

        $fields = $this->application->config()['forms']['Categories\Form\Category'];

        $parents = [];
        foreach ($category->getFlatMap() as $c) {
            $parents[$c->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $c->depth) .
                (($c->depth > 0) ? '&rarr; ' : '') . $c->title;
        }

        $fields[0]['category_parent_id']['value']   = $fields[0]['category_parent_id']['value'] + $parents;
        $fields[1]['slug']['attributes']['onkeyup'] = "phire.changeCategoryUri();";
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
                $this->redirect(BASE_PATH . APP_URI . '/categories/edit/'. $category->id . '?saved=' . time());
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
        $this->view->title         = 'Categories';
        $this->view->category_title = $category->title;

        $fields = $this->application->config()['forms']['Categories\Form\Category'];

        $fields[0]['category_parent_id']['value'] = $fields[0]['category_parent_id']['value'] + $parents;
        $fields[1]['slug']['label']     .=
            ' [ <a href="#" class="small-link" onclick="phire.createSlug(jax(\'#title\').val(), \'#slug\');' .
            ' return phire.changeCategoryUri();">Generate URI</a> ]';

        $fields[1]['title']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';

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
                $this->redirect(BASE_PATH . APP_URI . '/categories/edit/'. $category->id . '?saved=' . time());
            }
        }

        $this->send();
    }

    /**
     * JSON action method
     *
     * @param  int $id
     * @return void
     */
    public function json($id)
    {
        $json = ['parent_uri' => ''];

        $content = Table\Categories::findById($id);
        if (isset($content->id)) {
            $json['parent_uri'] = $content->uri;
        }

        $this->response->setBody(json_encode($json, JSON_PRETTY_PRINT));
        $this->send(200, ['Content-Type' => 'application/json']);
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
        $this->redirect(BASE_PATH . APP_URI . '/categories?removed=' . time());
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
