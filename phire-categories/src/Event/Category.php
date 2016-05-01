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
        if ($application->isRegistered('phire-templates') && ($controller instanceof \Phire\Categories\Controller\IndexController) &&
            ($controller->hasView())) {
            if (null !== $controller->view()->category_title) {
                $template = \Phire\Templates\Table\Templates::findBy(['name' => 'Category ' . $controller->view()->category_title]);
                if (!isset($template->id)) {
                    $template = \Phire\Templates\Table\Templates::findBy(['name' => 'Category']);
                }
            } else {
                $template = \Phire\Templates\Table\Templates::findBy(['name' => 'Category']);
            }

            if (isset($template->id)) {
                if (isset($template->id)) {
                    $device = \Phire\Templates\Event\Template::getDevice($controller->request()->getQuery('mobile'));
                    if ((null !== $device) && ($template->device != $device)) {
                        $childTemplate = \Phire\Templates\Table\Templates::findBy(['parent_id' => $template->id, 'device' => $device]);
                        if (isset($childTemplate->id)) {
                            $tmpl = $childTemplate->template;
                        } else {
                            $tmpl = $template->template;
                        }
                    } else {
                        $tmpl = $template->template;
                    }
                    $controller->view()->setTemplate(\Phire\Templates\Event\Template::parse($tmpl));
                }
            }
        } else if ($application->isRegistered('phire-themes') && ($controller instanceof \Phire\Categories\Controller\IndexController) &&
            ($controller->hasView())) {
            $theme = \Phire\Themes\Table\Themes::findBy(['active' => 1]);
            if (isset($theme->id)) {
                $template  = null;
                $themePath = $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/themes/' . $theme->folder . '/';
                if (null !== $controller->view()->category_slug) {
                    $catSlug = 'category-' . str_replace('/', '-', $controller->view()->category_slug);
                    if (file_exists($themePath . $catSlug . '.phtml') || file_exists($themePath . $catSlug . '.php')) {
                        $template = file_exists($themePath . $catSlug . '.phtml') ? $catSlug . '.phtml' : $catSlug . '.php';
                    } else if (file_exists($themePath . 'category.phtml') || file_exists($themePath . 'category.php')) {
                        $template = file_exists($themePath . 'category.phtml') ? 'category.phtml' : 'category.php';
                    }
                } else if (file_exists($themePath . 'category.phtml') || file_exists($themePath . 'category.php')) {
                    $template = file_exists($themePath . 'category.phtml') ? 'category.phtml' : 'category.php';
                }

                if (null !== $template) {
                    $device = \Phire\Themes\Event\Theme::getDevice($controller->request()->getQuery('mobile'));
                    if ((null !== $device) && (file_exists($themePath . $device . '/' . $template))) {
                        $template = $device . '/' . $template;
                    }
                    $controller->view()->setTemplate($themePath . $template);
                }
            }
        }
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
        if ((!$_POST) && ($controller->hasView())) {
            $category = new Model\Category();
            $category->show_total = $application->module('phire-categories')['show_total'];
            $category->filters    = $application->module('phire-categories')['filters'];
            $category->datetime_formats = $application->module('phire-categories')['datetime_formats'];
            $controller->view()->category_nav = $category->getNav($application->module('phire-categories')['nav_config']);

            if (($application->isRegistered('phire-templates')) && ($controller->view()->isStream()) &&
                ((strpos($controller->view()->getTemplate()->getTemplate(), '[{category_') !== false) ||
                    (strpos($controller->view()->getTemplate()->getTemplate(), '[{categories_') !== false))) {

                $catIds       = self::parseCategoryIds($controller->view()->getTemplate()->getTemplate());
                $catParentIds = self::parseParentCategoryIds($controller->view()->getTemplate()->getTemplate());

                if (count($catIds) > 0) {
                    foreach ($catIds as $key => $value) {
                        $category->getById($value);
                        if (($category->pagination > 0) && ($category->hasPages($category->pagination))) {
                            $limit = $category->pagination;
                            $pages = new \Pop\Paginator\Paginator($category->getCount(), $limit);
                            $pages->useInput(true);
                        } else {
                            $limit = null;
                            $pages = null;
                        }

                        $categoryName = 'category_' . $value;
                        $controller->view()->pages = $pages;
                        $controller->view()->{$categoryName} = $category->getItems($limit, $controller->request()->getQuery('page'));
                    }
                }
                if (count($catParentIds) > 0) {
                    foreach ($catParentIds as $key => $value) {
                        $categoryName = 'categories_' . $value;
                        $controller->view()->{$categoryName} = $category->getCategoryChildren($value);
                    }
                }
            } else if ((($controller instanceof \Phire\Content\Controller\IndexController) ||
                    ($controller instanceof \Phire\Categories\Controller\IndexController)) && ($controller->view()->isFile())) {
                $controller->view()->phire->category = $category;
            }
        }
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
        if (($controller->hasView()) &&
            (($controller instanceof \Phire\Categories\Controller\IndexController) ||
                ($controller instanceof \Phire\Content\Controller\IndexController))) {

            $body = $controller->response()->getBody();

            $category = new Model\Category();
            $category->show_total = $application->module('phire-categories')['show_total'];
            $category->filters    = $application->module('phire-categories')['filters'];
            $category->datetime_formats = $application->module('phire-categories')['datetime_formats'];

            $catIds       = self::parseCategoryIds($body);
            $catParentIds = self::parseParentCategoryIds($body);

            if (count($catIds) > 0) {
                foreach ($catIds as $key => $value) {
                    $category->getById($value);
                    if (($category->pagination > 0) && ($category->hasPages($category->pagination))) {
                        $limit = $category->pagination;
                        $pages = new \Pop\Paginator\Paginator($category->getCount(), $limit);
                        $pages->useInput(true);
                    } else {
                        $limit = null;
                        $pages = null;
                    }

                    $categoryName = 'category_' . $value;
                    $controller->view()->pages = $pages;
                    $controller->view()->{$categoryName} = $category->getItems($limit, $controller->request()->getQuery('page'));
                }
            }
            if (count($catParentIds) > 0) {
                foreach ($catParentIds as $key => $value) {
                    $categoryName = 'categories_' . $value;
                    $controller->view()->{$categoryName} = $category->getCategoryChildren($value);
                }
            }

            $controller->view()->setTemplate($body);
            $body = $controller->view()->render();
            $controller->response()->setBody($body);
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
     * Parse category IDs in template
     *
     * @param  string $template
     * @return array
     */
    protected static function parseCategoryIds($template)
    {
        $ids  = [];
        $cats = [];

        preg_match_all('/\[\{category_.*\}\]/', $template, $cats);

        if (isset($cats[0]) && isset($cats[0][0])) {
            foreach ($cats[0] as $cat) {
                $id    = substr($cat, (strpos($cat, '[{category_') + 11));
                $ids[] = substr($id, 0, strpos($id, '}]'));
            }
        }

        return $ids;
    }

    /**
     * Parse child categories from parent category IDs in template
     *
     * @param  string $template
     * @return array
     */
    protected static function parseParentCategoryIds($template)
    {
        $ids  = [];
        $cats = [];

        preg_match_all('/\[\{categories_.*\}\]/', $template, $cats);

        if (isset($cats[0]) && isset($cats[0][0])) {
            foreach ($cats[0] as $cat) {
                $id    = substr($cat, (strpos($cat, '[{categories_') + 13));
                $ids[] = substr($id, 0, strpos($id, '}]'));
            }
        }

        return $ids;
    }

}
