<?php

namespace Categories\Controller;

use Categories\Model;
use Categories\Form;
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
        $this->prepareView('categories/index.phtml');
        $categories = new Model\Category();

        $this->view->title     = 'Categories';
        $this->view->categories = $categories->getAll($this->request->getQuery('sort'));

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

        $fields = $this->application->config()['forms']['Categories\Form\Category'];

        $fields[0]['category_parent_id']['value']   = $fields[0]['category_parent_id']['value'] + $category->getParents();
        $fields[1]['slug']['attributes']['onkeyup'] = "phire.changeCategoryUri();";
        $fields[1]['name']['attributes']['onkeyup'] = "phire.createSlug(this.value, '#slug'); phire.changeCategoryUri();";

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

        $this->prepareView('categories/edit.phtml');
        $this->view->title         = 'Categories';
        $this->view->category_name = $category->name;

        $fields = $this->application->config()['forms']['Categories\Form\Category'];

        $categories = Table\Categories::findAll();
        foreach ($categories->rows() as $cat) {
            if ($cat->id != $id) {
                $fields[0]['category_parent_id']['value'][$cat->id] = $cat->name;
            }
        }

        $fields[0]['category_parent_id']['value'] = $fields[0]['category_parent_id']['value'] + $category->getParents($id);
        $fields[1]['slug']['label']     .=
            ' [ <a href="#" class="small-link" onclick="phire.createSlug(jax(\'#name\').val(), \'#slug\');' .
            ' return phire.changeCategoryUri();">Generate URI</a> ]';

        $fields[1]['name']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';

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
