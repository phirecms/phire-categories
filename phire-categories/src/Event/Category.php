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

            if (isset($forms['Phire\Media\Form\Batch'])) {
                $forms['Phire\Media\Form\Batch'][0]['categories'] = [
                    'type' => 'checkbox',
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
            $controller->view()->category_nav = $category->getNav($application->module('phire-categories')['nav_config']);

            if (($application->isRegistered('phire-templates')) && ($controller->view()->isStream()) &&
                ((strpos($controller->view()->getTemplate()->getTemplate(), '[{category_') !== false) ||
                (strpos($controller->view()->getTemplate()->getTemplate(), '[{categories_') !== false))) {
                $ids = self::parseCategoryIds($controller->view()->getTemplate()->getTemplate());

                if (count($ids) > 0) {
                    $category->settings       = $application->module('phire-categories')['settings'];
                    $category->summary_length = $application->module('phire-categories')['summary_length'];
                    foreach ($ids as $key => $value) {
                        if (strpos($key, 'categories') !== false) {
                            $items = $category->getChildCategory(
                                $value['id'], $value['options'], $application->isRegistered('phire-fields')
                            );
                        } else {
                            $items = $category->getCategoryContent(
                                $value['id'], $value['options'], $application->isRegistered('phire-fields')
                            );
                        }

                        if (count($items) > $controller->config()->pagination) {
                            $page  = $controller->request()->getQuery('page');
                            $limit = $controller->config()->pagination;
                            $pages = new \Pop\Paginator\Paginator(count($items), $limit);
                            $pages->useInput(true);
                            $offset = ((null !== $page) && ((int)$page > 1)) ?
                                ($page * $limit) - $limit : 0;
                            $items = array_slice($items, $offset, $limit, true);
                        } else {
                            $pages = null;
                        }

                        $controller->view()->pages  = $pages;
                        $controller->view()->{$key} = $items;
                    }
                }
            } else if ((($controller instanceof \Phire\Content\Controller\IndexController) ||
                ($controller instanceof \Phire\Categories\Controller\IndexController)) && ($controller->view()->isFile())) {
                $category->settings       = $application->module('phire-categories')['settings'];
                $category->summary_length = $application->module('phire-categories')['summary_length'];
                $controller->view()->phire->category = $category;
            }

            if (($controller instanceof \Phire\Categories\Controller\IndexController) && ($controller->getTemplate() == -1)) {
                if ($application->isRegistered('phire-templates')) {
                    $template = \Phire\Templates\Table\Templates::findBy(['name' => 'Error']);
                    if (isset($template->id)) {
                        if ((null !== $template) && isset($template->id)) {
                            if (isset($template->id)) {
                                $device = \Phire\Templates\Event\Template::getDevice($controller->request()->getQuery('mobile'));
                                if ((null !== $device) && ($template->device != $device)) {
                                    $childTemplate = Table\Templates::findBy(['parent_id' => $template->id, 'device' => $device]);
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
                    }
                } else if (($application->isRegistered('phire-themes')) && ($controller->view()->isFile())) {
                    $theme = \Phire\Themes\Table\Themes::findBy(['active' => 1]);
                    if (isset($theme->id)) {
                        $themePath = $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . CONTENT_PATH . '/themes/' . $theme->folder . '/';
                        if (file_exists($themePath . 'error.phtml') || file_exists($themePath . 'error.php')) {
                            $template = file_exists($themePath . 'error.phtml') ? $themePath . 'error.phtml' : $themePath . 'error.php';
                            $controller->view()->setTemplate($template);
                        }
                    }
                }
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
            $ids  = self::parseCategoryIds($body);

            if (count($ids) > 0) {
                $category = new Model\Category();
                $category->show_total     = $application->module('phire-categories')['show_total'];
                $category->settings       = $application->module('phire-categories')['settings'];
                $category->summary_length = $application->module('phire-categories')['summary_length'];
                $category->settings       = $application->module('phire-categories')['settings'];
                $category->summary_length = $application->module('phire-categories')['summary_length'];

                foreach ($ids as $key => $value) {
                    if (strpos($key, 'categories') !== false) {
                        $items = $category->getChildCategory(
                            $value['id'], $value['options'], $application->isRegistered('phire-fields')
                        );
                    } else {
                        $items = $category->getCategoryContent(
                            $value['id'], $value['options'], $application->isRegistered('phire-fields')
                        );
                    }

                    if (count($items) > $controller->config()->pagination) {
                        $page  = $controller->request()->getQuery('page');
                        $limit = $controller->config()->pagination;
                        $pages = new \Pop\Paginator\Paginator(count($items), $limit);
                        $pages->useInput(true);
                        $offset = ((null !== $page) && ((int)$page > 1)) ?
                            ($page * $limit) - $limit : 0;
                        $items = array_slice($items, $offset, $limit, true);
                    } else {
                        $pages = null;
                    }


                    $controller->view()->pages  = $pages;
                    $controller->view()->{$key} = $items;
                }

                $controller->view()->setTemplate($body);
                $controller->response()->setBody($controller->view()->render());
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
                    $c2c = new Table\ContentToCategories();
                    $c2c->delete(['content_id' => $id, 'type' => $type]);
                }

                if (is_array($categories) && (count($categories) > 0)) {
                    foreach ($categories as $category) {
                        foreach ($contentId as $id) {
                            $c2c = new Table\ContentToCategories([
                                'content_id'  => $id,
                                'category_id' => $category,
                                'type'        => $type,
                                'order'       => (int)$_POST['category_order_' . $category]
                            ]);
                            $c2c->save();
                        }
                    }
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

    /**
     * Parse category IDs from template
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
                $c = str_replace('}]', '', substr($cat, (strpos($cat, '_') + 1)));
                if (($c != 'nav') && ($c != 'uri') && ($c != 'title') && ($c != 'total') && (strpos($c, '[{') === false)) {
                    $key = str_replace(['[{', '}]'], ['', ''], $cat);
                    if (strpos($c, '_') !== false) {
                        $cAry  = explode('_', $c);
                        $id    = $cAry[0];
                        $order = (isset($cAry[1])) ? $cAry[1] : 'order ASC';
                        $limit = (isset($cAry[2])) ? $cAry[2] : null;
                    } else {
                        $id    = $c;
                        $order = 'order ASC';
                        $limit = null;
                    }
                    $ids[$key] = [
                        'id'      => $id,
                        'options' => [
                            'order' => $order,
                            'limit' => $limit
                        ]
                    ];
                }
            }
        }

        $cats = [];

        preg_match_all('/\[\{categories_.*\}\]/', $template, $cats);

        if (isset($cats[0]) && isset($cats[0][0])) {
            foreach ($cats[0] as $cat) {
                $c   = str_replace('}]', '', substr($cat, (strpos($cat, '_') + 1)));
                $key = str_replace(['[{', '}]'], ['', ''], $cat);
                if (strpos($c, '_') !== false) {
                    $cAry  = explode('_', $c);
                    $id    = $cAry[0];
                    $order = (isset($cAry[1])) ? $cAry[1] : 'order ASC';
                    $limit = (isset($cAry[2])) ? $cAry[2] : null;
                } else {
                    $id    = $c;
                    $order = 'order ASC';
                    $limit = null;
                }
                $ids[$key] = [
                    'id'      => $id,
                    'options' => [
                        'order' => $order,
                        'limit' => $limit
                    ]
                ];
            }
        }

        return $ids;
    }

}
