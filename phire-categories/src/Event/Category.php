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
        $forms = $application->config()['forms'];
        $cat   = new Model\Category();
        $cat->getAll();

        if (count($cat->getFlatMap()) > 0) {
            $categoryValues = $cat->getCategoryValues();

            if (isset($forms['Phire\Content\Form\Content'])) {
                $forms['Phire\Content\Form\Content'][0]['categories'] = [
                    'type'  => 'checkbox',
                    'label' => 'Categories',
                    'value' => $categoryValues
                ];
                $forms['Phire\Content\Form\Content'][0]['category_type'] = [
                    'type'  => 'hidden',
                    'value' => 'content'
                ];
            }

            if (isset($forms['Phire\Media\Form\Media'])) {
                $forms['Phire\Media\Form\Media'][0]['categories'] = [
                    'type'  => 'checkbox',
                    'label' => 'Categories',
                    'value' => $categoryValues
                ];
                $forms['Phire\Media\Form\Media'][0]['category_type'] = [
                    'type'  => 'hidden',
                    'value' => 'media'
                ];
            }

            if (isset($forms['Phire\Media\Form\Batch'])) {
                $forms['Phire\Media\Form\Batch'][0]['categories'] = [
                    'type'  => 'checkbox',
                    'label' => 'Categories',
                    'value' => $categoryValues
                ];
                $forms['Phire\Media\Form\Batch'][0]['category_type'] = [
                    'type' => 'hidden',
                    'value' => 'media'
                ];
            }

            $application->mergeConfig(['forms' => $forms], true);
        }
    }

    /**
     * Set the category template
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function setTemplate(AbstractController $controller, Application $application)
    {

    }

    /**
     * Init category nav and categories
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function init(AbstractController $controller, Application $application)
    {

    }

    /**
     * Get all category values for the form object
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function parseCategories(AbstractController $controller, Application $application)
    {

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
                $catItems = ($type == 'media') ?
                   Table\CategoryMedia::findBy(['media_id' => $contentId]) :
                   Table\CategoryContent::findBy(['content_id' => $contentId]);

                if ($catItems->hasRows()) {
                    foreach ($catItems->rows() as $c) {
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
        $type      = null;
        $contentId = null;

        if (($_POST) && ($controller->hasView()) && (null !== $controller->view()->id) &&
            (null !== $controller->view()->form) && ($controller->view()->form instanceof \Pop\Form\Form)) {
            $categories = $controller->view()->form->categories;
            $type       = $controller->view()->form->category_type;
            $contentId  = $controller->view()->id;

            // Clear categories
            if ((null !== $type) && (null !== $contentId)) {
                if (!is_array($contentId)) {
                    $contentId = [$contentId];
                }
                foreach ($contentId as $id) {
                    $itemId = ($type == 'media') ? 'media_id' : 'content_id';
                    $c2c = new Table\CategoryItems();
                    $c2c->delete([$itemId => $id]);
                }

                if (is_array($categories) && (count($categories) > 0)) {
                    foreach ($categories as $category) {
                        foreach ($contentId as $id) {
                            if ($type == 'media') {
                                $fields = [
                                    'category_id' => $category,
                                    'content_id'  => null,
                                    'media_id'    => $id,
                                    'order'       => (int)$_POST['category_order_' . $category]
                                ];
                            } else {
                                $fields = [
                                    'category_id' => $category,
                                    'content_id'  => $id,
                                    'media_id'    => null,
                                    'order'       => (int)$_POST['category_order_' . $category]
                                ];
                            }
                            $catItem = new Table\CategoryItems($fields);
                            $catItem->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Parse category IDs from template
     *
     * @param  string $template
     * @return array
     */
    protected static function parseCategoryIds($template)
    {

    }

}
