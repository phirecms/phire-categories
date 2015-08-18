<?php

namespace Phire\Categories\Event;

use Phire\Categories\Model;
use Phire\Categories\Table;
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
        $forms    = $application->config()['forms'];
        $settings = $application->module('phire-categories')['settings'];

        $cat = new Model\Category();
        $cat->getAll();

        $categoryValues = [];

        if (count($cat->getFlatMap()) > 0) {
            foreach ($cat->getFlatMap() as $c) {
                $categoryValues[$c->id] = '<input class="category-order-value" type="text" value="0" size="2" name="category_order_' .
                    $c->id . '" id="category_order_' . $c->id . '"/>' .
                    str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $c->depth) . (($c->depth > 0) ? '&rarr; ' : '') .
                    '<span class="category-checkbox-value">' . $c->title . '</span>';
            }

            foreach ($settings as $name => $setting) {
                if (isset($forms[$setting['form']['name']])) {
                    $forms[$setting['form']['name']][$setting['form']['group']]['categories'] = [
                        'type' => 'checkbox',
                        'label' => 'Categories',
                        'value' => $categoryValues
                    ];
                    $forms[$setting['form']['name']][$setting['form']['group']]['category_type'] = [
                        'type' => 'hidden',
                        'value' => $name
                    ];
                }
            }

            $application->mergeConfig(['forms' => $forms], true);
        }
    }

    /**
     * Get all category values for the form object
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function getNav(AbstractController $controller, Application $application)
    {
        if ((!$_POST) && ($controller->hasView())) {
            $category = new Model\Category();
            $category->show_total = $application->module('phire-categories')['show_total'];
            $controller->view()->category_nav = $category->getNav($application->module('phire-categories')['nav_config']);
        }
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
                        'type'        => $type,
                        'order'       => (int)$_POST['category_order_' . $category]
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
            $settings = $application->module('phire-categories')['settings'];
            foreach ($settings as $name => $setting) {
                if (($setting['remove'] != 'process_content') ||
                    (($setting['remove'] == 'process_content') && isset($_POST['content_process_action']) && ($_POST['content_process_action'] == -3))) {
                    if (isset($_POST[$setting['remove']])) {
                        foreach ($_POST[$setting['remove']] as $id) {
                            $c2c = new Table\ContentToCategories();
                            $c2c->delete(['content_id' => (int)$id]);
                        }
                    }
                }
            }
        }
    }

}
