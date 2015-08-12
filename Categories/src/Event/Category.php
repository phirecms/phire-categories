<?php

namespace Categories\Event;

use Categories\Model;
use Categories\Table;
use Pop\Application;
use Phire\Controller\AbstractController;

class Category
{

    /**
     * Bootstrap the module
     *
     * @param  Application $application
     * @return void
     */
    public static function bootstrap(Application $application)
    {
        $forms      = $application->config()['forms'];
        $categories = $application->module('Categories')['categories'];

        $cat = new Model\Category();
        $cat->getAll();

        $categoryValues = [];

        foreach ($cat->getFlatMap() as $c) {
            $categoryValues[$c->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $c->depth) . (($c->depth > 0) ? '&rarr; ' : '') .
                '<span class="category-checkbox-value">' . $c->title . '</span>';
        }

        foreach ($categories as $object => $category) {
            if (isset($forms[$category['form']['name']])) {
                $forms[$category['form']['name']][$category['form']['group']]['categories'] = [
                    'type'  => 'checkbox',
                    'label' => 'Categories',
                    'value' => $categoryValues
                ];
                $forms[$category['form']['name']][$category['form']['group']]['category_type'] = [
                    'type'  => 'hidden',
                    'value' => $object
                ];
            }
        }

        $application->mergeConfig(['forms' => $forms], true);
    }

    /**
     * Get all category values for the form object
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function getAll(AbstractController $controller, Application $application)
    {
        if ((!$_POST) && ($controller->hasView()) && (null !== $controller->view()->form) &&
            ((int)$controller->view()->form->id != 0) && (null !== $controller->view()->form) &&
            ($controller->view()->form instanceof \Pop\Form\Form)) {
            $type       = $controller->view()->form->category_type;
            $contentId  = $controller->view()->form->id;
            $values     = [];

            if (null !== $type) {
                $c2c = Table\ContentToCategories::findBy(['content_id' => $contentId, 'type' => $type]);
                if ($c2c->hasRows()) {
                    foreach ($c2c->rows() as $c) {
                        $values[] = $c->category_id;
                    }
                }
            }
            if (count($values) > 0) {
                $controller->view()->form->categories = $values;
            }
        }
    }

    /**
     * Save category relationships
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function save(AbstractController $controller, Application $application)
    {
        if (($_POST) && ($controller->hasView()) && (null !== $controller->view()->id) &&
            (null !== $controller->view()->form) && ($controller->view()->form instanceof \Pop\Form\Form)) {
            $categories = $controller->view()->form->categories;
            $type       = $controller->view()->form->category_type;
            $contentId  = $controller->view()->id;

            // Clear categories
            if ((null !== $type) && (null !== $contentId)) {
                $c2c = new Table\ContentToCategories();
                $c2c->delete(['content_id' => $contentId, 'type' => 'content']);
            }

            if (is_array($categories) && (count($categories) > 0)) {
                foreach ($categories as $category) {
                    $c2c = new Table\ContentToCategories([
                        'content_id'  => $contentId,
                        'category_id' => $category,
                        'type'        => $type
                    ]);
                    $c2c->save();
                }
            }
        }
    }

    /**
     * Delete category relationships
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function delete(AbstractController $controller, Application $application)
    {
        if ($_POST) {
            $categories = $application->module('Categories')['categories'];
            foreach ($categories as $object => $category) {
                if (($category['remove'] != 'process_content') ||
                    (($category['remove'] == 'process_content') && isset($_POST['content_process_action']) && ($_POST['content_process_action'] == -3))) {
                    if (isset($_POST[$category['remove']])) {
                        foreach ($_POST[$category['remove']] as $id) {
                            $c2c = new Table\ContentToCategories();
                            $c2c->delete(['content_id' => (int)$id]);
                        }
                    }
                }
            }
        }
    }

}
