<?php

namespace Phire\Categories\Model;

use Phire\Categories\Table;
use Phire\Model\AbstractModel;
use Pop\Nav\Nav;

class Category extends AbstractModel
{

    protected $flatMap = [];

    /**
     * Constructor
     *
     * Instantiate a model object
     *
     * @param  array $data
     * @return AbstractModel
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $contentConfig = include __DIR__ . '/../../../phire-content/config/module.php';

        $this->date_format   = $contentConfig['phire-content']['date_format'];
        $this->month_format  = $contentConfig['phire-content']['month_format'];
        $this->day_format    = $contentConfig['phire-content']['day_format'];
        $this->year_format   = $contentConfig['phire-content']['year_format'];
        $this->time_format   = $contentConfig['phire-content']['time_format'];
        $this->hour_format   = $contentConfig['phire-content']['hour_format'];
        $this->minute_format = $contentConfig['phire-content']['minute_format'];
        $this->period_format = $contentConfig['phire-content']['period_format'];
    }

    /**
     * Get all categories
     *
     * @param  string $sort
     * @param  int    $pid
     * @return array
     */
    public function getAll($sort = null, $pid = null)
    {
        $order         = (null !== $sort) ? $this->getSortOrder($sort) : 'order ASC';
        $categories    = Table\Categories::findBy(['parent_id' => $pid], ['order' => $order]);
        $categoriesAry = [];

        foreach ($categories->rows() as $category) {
            $this->flatMap[] = new \ArrayObject([
                'id'    => $category->id,
                'title' => $category->title,
                'uri'   => $category->uri,
                'total' => Table\ContentToCategories::findBy(['category_id' => $category->id])->count(),
                'order' => $category->order,
                'depth' => 0
            ], \ArrayObject::ARRAY_AS_PROPS);
            $category->depth    = 0;
            $category->children = $this->getChildren($category, $order);
            $categoriesAry[]    = $category;
        }

        return $categoriesAry;
    }

    /**
     * Get category by ID
     *
     * @param  int $id
     * @return void
     */
    public function getById($id)
    {
        $category = Table\Categories::findById($id);
        if (isset($category->id)) {
            $data = $category->getColumns();
            $data['category_parent_id'] = $data['parent_id'];
            unset($data['parent_id']);
            $this->data = array_merge($this->data, $data);
        }
    }

    /**
     * Get category for template
     *
     * @param  string              $uri
     * @param  \Pop\Module\Manager $modules
     * @return void
     */
    public function getByUri($uri, \Pop\Module\Manager $modules = null)
    {
        $category = Table\Categories::findBy(['uri' => $uri]);
        if (isset($category->id)) {
            $this->getCategory($category, $modules);
        }
    }

    /**
     * Get category content
     *
     * @param  mixed               $id
     * @param  array               $options
     * @param  boolean             $override
     * @param  \Pop\Module\Manager $modules
     * @return array
     */
    public function getCategoryContent($id, array $options = null, $override = false, $modules = null)
    {
        if (!is_numeric($id)) {
            $category = Table\Categories::findBy(['title' => $id]);
            if (isset($category->id)) {
                $id = $category->id;
            }
        }

        if (null === $options) {
            $options  = ['order' => 'order ASC'];
        }

        $items   = [];
        $orderBy = [];
        $type    = null;

        $c2c   = Table\ContentToCategories::findBy(['category_id' => $id], ['order' => 'order ASC']);
        if ($c2c->hasRows()) {
            foreach ($c2c->rows() as $c) {
                $type  = $c->type;
                $order = $c->order;
                if ((null !== $modules) && ($modules->isRegistered('phire-fields'))) {
                    $filters = ['strip_tags' => null];
                    if ($this->summary_length > 0) {
                        $filters['substr'] = [0, $this->summary_length];
                    };
                    $item = \Phire\Fields\Model\FieldValue::getModelObject(
                        $this->settings[$c->type]['model'], [$c->content_id], $this->settings[$c->type]['method'], $filters
                    );
                } else {
                    $class = $this->settings[$c->type]['model'];
                    $model = new $class();
                    call_user_func_array([
                        $model, $this->settings[$c->type]['method']], [$c->content_id]
                    );
                    $item = $model;
                }

                $allowed = true;
                if (isset($this->settings[$c->type]['required'])) {
                    foreach ($this->settings[$c->type]['required'] as $k => $v) {
                        if ($item[$k] != $v) {
                            $allowed = false;
                        }
                    }
                }

                if ($allowed) {
                    $item->type  = $type;
                    $item->order = $order;
                    if (isset($this->settings[$c->type]['order'])) {
                        $by = (($override) && isset($options['order'])) ?
                            substr($options['order'], 0, strpos($options['order'], ' ')) :
                            substr($this->settings[$c->type]['order'], 0, strpos($this->settings[$c->type]['order'], ' '));
                        if (isset($item[$by])) {
                            $orderBy[] = $item[$by];
                        }
                    }

                    $i = $item->toArray();
                    foreach ($i as $key => $value) {
                        if (in_array($key, $this->date_fields)) {
                            $dateValues = $this->formatDateAndTime($value);
                            foreach ($dateValues as $k => $v) {
                                $i[$key . '_' . $k] = $v;
                            }
                        }
                    }

                    $items[] = new \ArrayObject($i, \ArrayObject::ARRAY_AS_PROPS);
                }
            }
        }

        if (!($override) && (count($orderBy) > 0) && (null !== $type) && isset($this->settings[$type]['order'])) {
            $order = trim(substr($this->settings[$type]['order'], (strpos($this->settings[$type]['order'], ' ') + 1)));
            if ($order == 'DESC') {
                array_multisort($orderBy, SORT_DESC, $items);
            } else if ($order == 'ASC') {
                array_multisort($orderBy, SORT_ASC, $items);
            }
        } else if (($override) && (count($orderBy) > 0) && isset($options['order'])) {
            $order = trim(substr($options['order'], (strpos($options['order'], ' ') + 1)));
            if ($order == 'DESC') {
                array_multisort($orderBy, SORT_DESC, $items);
            } else if ($order == 'ASC') {
                array_multisort($orderBy, SORT_ASC, $items);
            }
        }

        if (isset($options['limit']) && ((int)$options['limit'] > 0)) {
            $items = array_slice($items, 0, (int)$options['limit']);
        }

        return $items;
    }

    /**
     * Get child category
     *
     * @param  mixed   $id
     * @param  array   $options
     * @param  boolean $override
     * @param  \Pop\Module\Manager $modules
     * @return array
     */
    public function getChildCategory($id, array $options = null, $override = false, \Pop\Module\Manager $modules = null)
    {
        if (!is_numeric($id)) {
            $category = Table\Categories::findBy(['title' => $id]);
            if (isset($category->id)) {
                $id = $category->id;
            }
        }

        if (null === $options) {
            $options = ['order' => 'order ASC'];
        }

        $children = Table\Categories::findBy(['parent_id' => $id], $options);

        $items = [];

        if ($children->hasRows()) {
            foreach ($children->rows() as $child) {
                $childItems = $this->getCategoryContent($child->id, $options, $override, $modules);
                $item       = (count($childItems) > 0) ? (array)array_shift($childItems) : [];
                $filtered   = [];

                foreach ($item as $key => $value) {
                    $filtered['item_' . $key] = $value;
                }

                $items[]    = new \ArrayObject(array_merge([
                    'category_id'    => $child->id,
                    'category_title' => $child->title,
                    'category_uri'   => '/category' . $child->uri,
                    'category_total' => count($childItems)
                ], $filtered), \ArrayObject::ARRAY_AS_PROPS);
            }
        }

        return $items;
    }

    /**
     * Save new category
     *
     * @param  array $fields
     * @return void
     */
    public function save(array $fields)
    {
        $parentId = ((isset($fields['category_parent_id']) && ($fields['category_parent_id'] != '----')) ?
            (int)$fields['category_parent_id'] : null);

        $category = new Table\Categories([
            'parent_id' => $parentId,
            'title'     => $fields['title'],
            'uri'       => $fields['uri'],
            'slug'      => $fields['slug'],
            'order'     => (int)$fields['order'],
            'hierarchy' => $this->getHierarchy($parentId)
        ]);
        $category->save();

        $this->data = array_merge($this->data, $category->getColumns());
    }

    /**
     * Update an existing category
     *
     * @param  array $fields
     * @return void
     */
    public function update(array $fields)
    {
        $category = Table\Categories::findById((int)$fields['id']);
        if (isset($category->id)) {

            $parentId = ((isset($fields['category_parent_id']) && ($fields['category_parent_id'] != '----')) ?
                (int)$fields['category_parent_id'] : null);

            $category->parent_id = $parentId;
            $category->title     = $fields['title'];
            $category->uri       = $fields['uri'];
            $category->slug      = $fields['slug'];
            $category->order     = (int)$fields['order'];
            $category->hierarchy = $this->getHierarchy($parentId);
            $category->save();

            $this->changeDescendantUris($category->id, $category->uri);

            $this->data = array_merge($this->data, $category->getColumns());
        }
    }

    /**
     * Process category contents
     *
     * @param  array $post
     * @return void
     */
    public function process(array $post)
    {
        foreach ($post as $key => $value) {
            if (substr($key, 0, 6) == 'order_') {
                $id  = substr($key, (strrpos($key, '_') + 1));
                $c2c = Table\ContentToCategories::findById([(int)$id, (int)$post['category_id']]);
                if (isset($c2c->content_id)) {
                    $c2c->order = (int)$value;
                    $c2c->save();
                }
            }
        }

        if (isset($post['process_categories'])) {
            foreach ($post['process_categories'] as $id) {
                $c2c = Table\ContentToCategories::findById([(int)$id, (int)$post['category_id']]);
                if (isset($c2c->content_id)) {
                    $c2c->delete();
                }
            }
        }
    }

    /**
     * Remove a category
     *
     * @param  array $fields
     * @return void
     */
    public function remove(array $fields)
    {
        if (isset($fields['rm_categories'])) {
            foreach ($fields['rm_categories'] as $id) {
                $category = Table\Categories::findById((int)$id);
                if (isset($category->id)) {
                    $category->delete();
                }
            }
        }
    }

    /**
     * Method to get category breadcrumb
     *
     * @param  int     $id
     * @param  string  $sep
     * @return string
     */
    public function getBreadcrumb($id, $sep = '&gt;')
    {
        $breadcrumb = null;
        $category   = Table\Categories::findById($id);
        if (isset($category->id)) {
            $pId        = $category->parent_id;
            $breadcrumb = $category->title . ((isset($this->show_total) && ($this->show_total)) ?
                ' (' . $this->getTotal($category->id) . ')' : null);

            while (null !== $pId) {
                $category = Table\Categories::findById($pId);
                if (isset($category->id)) {
                    $breadcrumb = '<a href="' . BASE_PATH . '/category' . $category->uri . '">' . $category->title . '</a>' .
                        ' <span>' . $sep . '</span> ' . $breadcrumb;
                    $pId = $category->parent_id;
                }
            }
        }

        return $breadcrumb;
    }

    /**
     * Get total number of items in category
     *
     * @param  int $id
     * @param  int $depth
     * @return int
     */
    public function getTotal($id, $depth = 0)
    {
        $count    = Table\ContentToCategories::findBy(['category_id' => $id])->count();
        $children = Table\Categories::findBy(['parent_id' => $id]);

        foreach ($children->rows() as $child) {
            $count += $this->getTotal($child->id, ($depth + 1));
        }

        return $count;
    }

    /**
     * Method to get category navigation
     *
     * @param  array $config
     * @return Nav
     */
    public function getNav($config)
    {
        $categoriesAry = $this->getAll();
        $tree          = [];

        foreach ($categoriesAry as $category) {
            $tree[] = [
                'name'     => $category->title,
                'href'     => '/category' . $category->uri,
                'children' => $this->getNavChildren($category)
            ];
        }

        $nav = new Nav($tree, $config);
        return $nav;
    }

    /**
     * Determine if list of categories has pages
     *
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($limit)
    {
        return (Table\Categories::findAll()->count() > $limit);
    }

    /**
     * Get count of categories
     *
     * @return int
     */
    public function getCount()
    {
        return Table\Categories::findAll()->count();
    }

    /**
     * Get category flat map
     *
     * @return array
     */
    public function getFlatMap()
    {
        return $this->flatMap;
    }

    /**
     * Get category children
     *
     * @param  \ArrayObject|array $category
     * @param  string             $order
     * @param  int                $depth
     * @return array
     */
    protected function getChildren($category, $order, $depth = 0)
    {
        $children = [];
        $child    = Table\Categories::findBy(['parent_id' => $category->id], ['order' => $order]);

        if ($child->hasRows()) {
            foreach ($child->rows() as $c) {
                $this->flatMap[] = new \ArrayObject([
                    'id'    => $c->id,
                    'title' => $c->title,
                    'uri'   => $c->uri,
                    'total' => Table\ContentToCategories::findBy(['category_id' => $c->id])->count(),
                    'order' => $c->order,
                    'depth' => $depth + 1
                ], \ArrayObject::ARRAY_AS_PROPS);
                $c->depth    = $depth + 1;
                $c->children = $this->getChildren($c, $order, ($depth + 1));
                $children[]  = $c;
            }
        }

        return $children;
    }

    /**
     * Get category navigation children
     *
     * @param  \ArrayObject|array $category
     * @param  int                $depth
     * @return array
     */
    protected function getNavChildren($category, $depth = 0)
    {
        $children = [];
        $child    = Table\Categories::findBy(['parent_id' => $category->id], ['order' => 'order ASC']);

        if ($child->hasRows()) {
            foreach ($child->rows() as $c) {
                $children[]  = [
                    'name'     => $c->title . ((isset($this->show_total) && ($this->show_total) &&
                        (!Table\Categories::findBy(['parent_id' => $c->id])->hasRows())) ?
                        ' (' . $this->getTotal($c->id) . ')' : null),
                    'href'     => '/category' . $c->uri,
                    'children' => $this->getNavChildren($c, ($depth + 1))
                ];
            }
        }

        return $children;
    }

    /**
     * Change the descendant URIs
     *
     * @param  int $id
     * @param  string $uri
     * @return mixed
     */
    protected function changeDescendantUris($id, $uri)
    {
        $children = Table\Categories::findBy(['parent_id' => $id]);

        while ($children->count() > 0) {
            foreach ($children->rows() as $child) {
                $c = Table\Categories::findById($child->id);
                if (isset($c->id)) {
                    $c->uri = $uri . '/' . $c->slug;
                    $c->save();
                }
                $children = $this->changeDescendantUris($c->id, $c->uri);
            }
        }

        return $children;
    }

    /**
     * Get parental hierarchy
     *
     * @param  int $parentId
     * @return string
     */
    protected function getHierarchy($parentId = null)
    {
        $parents = [];

        while (null !== $parentId) {
            array_unshift($parents, $parentId);
            $category = Table\Categories::findById($parentId);
            if (isset($category->id)) {
                $parentId = $category->parent_id;
            }
        }

        return (count($parents) > 0) ? implode('|', $parents) : '';
    }

    /**
     * Get content
     *
     * @param  Table\Categories    $category
     * @param  \Pop\Module\Manager $modules
     * @return void
     */
    protected function getCategory(Table\Categories $category, \Pop\Module\Manager $modules = null)
    {
        if ((null !== $modules) && (($modules->isRegistered('phire-fields')))) {
            $c    = \Phire\Fields\Model\FieldValue::getModelObject('Phire\Categories\Model\Category', [$category->id]);
            $data = $c->toArray();
        } else {
            $data = $category->getColumns();
        }

        $categories = [
            new \ArrayObject([
                'id'    => $category->id,
                'title' => $category->title,
                'uri'   => $category->uri,
                'depth' => 0
            ], \ArrayObject::ARRAY_AS_PROPS)
        ];

        $this->getAll(null, $category->id);

        foreach ($this->flatMap as $c) {
            $c->depth++;
            $categories[] = $c;
        }

        $items   = [];
        $orderBy = [];
        $type    = null;

        foreach ($categories as $cat) {
            $c2c = Table\ContentToCategories::findBy(['category_id' => $cat->id], ['order' => 'order ASC']);
            if ($c2c->hasRows()) {
                foreach ($c2c->rows() as $c) {
                    $type = $c->type;
                    if ((null !== $modules) && (($modules->isRegistered('phire-fields')))) {
                        $filters = ['strip_tags' => null];
                        if ($this->summary_length > 0) {
                            $filters['substr'] = [0, $this->summary_length];
                        };
                        $item = \Phire\Fields\Model\FieldValue::getModelObject(
                            $this->settings[$c->type]['model'], [$c->content_id], $this->settings[$c->type]['method'], $filters
                        );
                    } else {
                        $class = $this->settings[$c->type]['model'];
                        $model = new $class();
                        call_user_func_array([
                            $model, $this->settings[$c->type]['method']], [$c->content_id]
                        );
                        $item = $model;
                    }

                    $allowed = true;
                    if (isset($this->settings[$c->type]['required'])) {
                        foreach ($this->settings[$c->type]['required'] as $k => $v) {
                            if ($item[$k] != $v) {
                                $allowed = false;
                            }
                        }
                    }

                    if ($allowed) {
                        if (isset($this->settings[$c->type]['order'])) {
                            $by = substr($this->settings[$c->type]['order'], 0, strpos($this->settings[$c->type]['order'], ' '));
                            if (isset($item[$by])) {
                                $orderBy[$item->id] = $item[$by];
                            }
                        }

                        $i = $item->toArray();
                        foreach ($i as $key => $value) {
                            if (in_array($key, $this->date_fields)) {
                                $dateValues = $this->formatDateAndTime($value);
                                foreach ($dateValues as $k => $v) {
                                    $i[$key . '_' . $k] = $v;
                                }
                            }
                        }

                        $items[$item->id] = new \ArrayObject($i, \ArrayObject::ARRAY_AS_PROPS);
                    }

                }
            }
        }

        if ((count($orderBy) > 0) && (null !== $type) && isset($this->settings[$type]['order'])) {
            $order = trim(substr($this->settings[$type]['order'], (strpos($this->settings[$type]['order'], ' ') + 1)));
            if ($order == 'DESC') {
                array_multisort($orderBy, SORT_DESC, $items);
            } else if ($order == 'ASC') {
                array_multisort($orderBy, SORT_ASC, $items);
            }
        }

        $data['items'] = $items;

        $data['category_nav']             = $this->getNav($this->nav_config);
        $data['category_breadcrumb']      = $this->getBreadcrumb($data['id'], ((null !== $this->separator) ? $this->separator : '&gt;'));
        $data['category_breadcrumb_text'] = strip_tags($data['category_breadcrumb'], 'span');

        $this->data = array_merge($this->data, $data);
    }

    /**
     * Format date and time values
     *
     * @param  string $value
     * @return array
     */
    protected function formatDateAndTime($value)
    {
        $values = [];

        // Has time
        if (strpos($value, ' ') !== false) {
            $date = substr($publish, 0, strpos($value, ' '));
            $time = substr($publish, (strpos($value, ' ') + 1));
        } else {
            $date = $value;
            $time = null;
        }

        $values['month'] = date($this->month_format, strtotime($date));
        $values['day']   = date($this->day_format, strtotime($date));
        $values['year']  = date($this->year_format, strtotime($date));

        if (null !== $time) {
            $values['hour']   = date($this->hour_format, strtotime($time));
            $values['minute'] = date($this->minute_format, strtotime($time));
            $values['period'] = date($this->period_format, strtotime($time));
        } else {
            $values['hour']   = null;
            $values['minute'] = null;
            $values['period'] = null;
        }

        return $values;
    }

}
