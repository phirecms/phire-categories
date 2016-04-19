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
     * @param  mixed $config
     * @return self
     */
    public function __construct(array $data = [], $config = null)
    {
        parent::__construct($data);

        if ((null !== $config) && isset($config['date_format'])) {
            $this->date_format   = $config['date_format'];
            $this->month_format  = $config['month_format'];
            $this->day_format    = $config['day_format'];
            $this->year_format   = $config['year_format'];
            $this->time_format   = $config['time_format'];
            $this->hour_format   = $config['hour_format'];
            $this->minute_format = $config['minute_format'];
            $this->period_format = $config['period_format'];
        }
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
     * @param  string $uri
     * @return void
     */
    public function getByUri($uri)
    {
        $category = Table\Categories::findBy(['uri' => $uri]);
        if (isset($category->id)) {
            $this->getCategory($category);
        }
    }

    /**
     * Get category content
     *
     * @param  mixed   $id
     * @param  array   $options
     * @param  boolean $override
     * @return array
     */
    public function getCategoryContent($id, array $options = null, $override = false)
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

        $items      = [];
        $orderBy    = [];
        $type       = null;
        $preOrdered = false;

        if (isset($options['order']) && (substr($options['order'], 0, 5) !== 'order')) {
            $c2c = Table\ContentToCategories::findBy(['category_id' => $id]);
            if ($c2c->hasRows()) {
                $type = $c2c->rows()[0]->type;
                $all  = true;
                foreach ($c2c->rows() as $c) {
                    if ($c->type != $type) {
                        $all = false;
                        break;
                    }
                }
                if (($all) && isset($this->data['settings'][$type])) {
                    $order = ($options['order'] != $this->data['settings'][$type]['order']) ? $options['order'] : $this->data['settings'][$type]['order'];
                    $orderAry = explode(' ', $order);
                    $sql = Table\ContentToCategories::sql();
                    $sql->select([
                        'content_id'  => DB_PREFIX . 'content_to_categories.content_id',
                        'category_id' => DB_PREFIX . 'content_to_categories.category_id',
                        'type'        => DB_PREFIX . 'content_to_categories.type',
                        'order'       => DB_PREFIX . 'content_to_categories.order',
                        $orderAry[0]  => DB_PREFIX . $this->data['settings'][$type]['table'] . '.' . $orderAry[0],
                    ])->join(
                        DB_PREFIX . $this->data['settings'][$type]['table'],
                        [DB_PREFIX . 'content_to_categories.content_id' => DB_PREFIX . $this->data['settings'][$type]['table'] . '.id']
                    )->orderBy($orderAry[0], $orderAry[1]);

                    $c2c        = Table\ContentToCategories::query((string)$sql);
                    $preOrdered = true;
                } else {
                    $c2c = Table\ContentToCategories::findBy(['category_id' => $id], ['order' => 'order ASC']);
                }
            } else {
                $c2c = Table\ContentToCategories::findBy(['category_id' => $id], ['order' => 'order ASC']);
            }
        } else {
            $c2c = Table\ContentToCategories::findBy(['category_id' => $id], ['order' => 'order ASC']);
        }
        if ($c2c->hasRows()) {
            foreach ($c2c->rows() as $c) {
                $ct      = Table\Categories::findById($c->category_id);
                $type    = $c->type;
                $order   = $c->order;
                $filters = ($ct->filter) ? $this->filters : [];
                if (class_exists('Phire\Fields\Model\FieldValue')) {
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
                        if (substr($k, -1) == '=') {
                            $op = substr($k, -2);
                            $k  = substr($k, 0, -2);
                            if (null !== $item[$k]) {
                                $isDate = (date('Y-m-d H:i:s', strtotime($item[$k])) == $item[$k]);
                                if ($op == '>=') {
                                    if ($isDate) {
                                        if (!(strtotime($item[$k]) >= strtotime($v))) {
                                            $allowed = false;
                                        }
                                    } else {
                                        if (!($item[$k] >= $v)) {
                                            $allowed = false;
                                        }
                                    }
                                } else {
                                    if ($isDate) {
                                        if (!(strtotime($item[$k]) <= strtotime($v))) {
                                            $allowed = false;
                                        }
                                    } else {
                                        if (!($item[$k] <= $v)) {
                                            $allowed = false;
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($item[$k] != $v) {
                                $allowed = false;
                            }
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

                    if ((!isset($i['expire'])) || (isset($i['expire']) && !empty($i['expire']) && (strtotime($i['expire']) >= time()))) {
                        $items[] = new \ArrayObject($i, \ArrayObject::ARRAY_AS_PROPS);
                    }
                }
            }
        }

        if (!($preOrdered)) {
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
     * @return array
     */
    public function getChildCategory($id, array $options = null, $override = false)
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
                $childItems = $this->getCategoryContent($child->id, $options, $override);
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
     * Get category view content
     *
     * @param  mixed $id
     * @return array
     */
    public function getCategoryViewContent($id)
    {
        $items   = [];
        $orderBy = [];
        $type    = null;
        $c2c     = Table\ContentToCategories::findBy(['category_id' => $id], ['order' => 'order ASC']);
        if ($c2c->hasRows()) {
            foreach ($c2c->rows() as $c) {
                $type  = $c->type;
                $order = $c->order;
                $class = $this->settings[$c->type]['model'];
                $model = new $class();
                call_user_func_array([
                    $model, $this->settings[$c->type]['method']], [$c->content_id]
                );
                $item = $model;

                $item->type  = $type;
                $item->order = $order;
                if (isset($this->settings[$c->type]['order'])) {
                    $by = substr($this->settings[$c->type]['order'], 0, strpos($this->settings[$c->type]['order'], ' '));
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

                /*
                $meetsReq = true;

                if (isset($this->settings[$c->type]['required'])) {
                    foreach ($this->settings[$c->type]['required'] as $req => $reqValue) {
                        if ((isset($i[$req]) && ($i[$req] != $reqValue)) || (!isset($i[$req]))) {
                            $meetsReq = false;
                        }
                    }
                }
                */

                $allowed = true;
                if (isset($this->settings[$c->type]['required'])) {
                    foreach ($this->settings[$c->type]['required'] as $k => $v) {
                        if (substr($k, -1) == '=') {
                            $op = substr($k, -2);
                            $k  = substr($k, 0, -2);
                            if (null !== $i[$k]) {
                                $isDate = (date('Y-m-d H:i:s', strtotime($i[$k])) == $i[$k]);
                                if ($op == '>=') {
                                    if ($isDate) {
                                        if (!(strtotime($i[$k]) >= strtotime($v))) {
                                            $allowed = false;
                                        }
                                    } else {
                                        if (!($i[$k] >= $v)) {
                                            $allowed = false;
                                        }
                                    }
                                } else {
                                    if ($isDate) {
                                        if (!(strtotime($i[$k]) <= strtotime($v))) {
                                            $allowed = false;
                                        }
                                    } else {
                                        if (!($i[$k] <= $v)) {
                                            $allowed = false;
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($i[$k] != $v) {
                                $allowed = false;
                            }
                        }
                    }
                }

                if (!$allowed) {
                    $i['item_status'] = -1;
                } else if (isset($i['publish']) && (strtotime($i['publish']) > time())) {
                    $i['item_status'] = 2;
                } else if ((!isset($i['expire'])) || (isset($i['expire']) && !empty($i['expire']) && (strtotime($i['expire']) >= time()))) {
                    $i['item_status'] = 1;
                } else {
                    $i['item_status'] = 0;
                }

                $items[] = new \ArrayObject($i, \ArrayObject::ARRAY_AS_PROPS);
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
            'filter'    => (int)$fields['filter'],
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
            $category->filter    = (int)$fields['filter'];
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
     * Get category values for form field
     *
     * @return array
     */
    public function getCategoryValues()
    {
        $categoryValues = [];

        foreach ($this->flatMap as $c) {
            $categoryValues[$c->id] = '<input class="category-order-value" type="text" value="0" size="2" name="category_order_' .
                $c->id . '" id="category_order_' . $c->id . '"/>' .
                str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $c->depth) . (($c->depth > 0) ? '&rarr; ' : '') .
                '<span class="category-checkbox-value">' . $c->title . '</span>';
        }

        return $categoryValues;
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
     * @param  Table\Categories $category
     * @return void
     */
    protected function getCategory(Table\Categories $category)
    {
        if (class_exists('Phire\Fields\Model\FieldValue')) {
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
                    $ct   = Table\Categories::findById($c->category_id);
                    $type = $c->type;
                    $filters = ($ct->filter) ? $this->filters : [];
                    if (class_exists('Phire\Fields\Model\FieldValue')) {
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
                            if (substr($k, -1) == '=') {
                                $op = substr($k, -2);
                                $k  = substr($k, 0, -2);
                                if (null !== $item[$k]) {
                                    $isDate = (date('Y-m-d H:i:s', strtotime($item[$k])) == $item[$k]);
                                    if ($op == '>=') {
                                        if ($isDate) {
                                            if (!(strtotime($item[$k]) >= strtotime($v))) {
                                                $allowed = false;
                                            }
                                        } else {
                                            if (!($item[$k] >= $v)) {
                                                $allowed = false;
                                            }
                                        }
                                    } else {
                                        if ($isDate) {
                                            if (!(strtotime($item[$k]) <= strtotime($v))) {
                                                $allowed = false;
                                            }
                                        } else {
                                            if (!($item[$k] <= $v)) {
                                                $allowed = false;
                                            }
                                        }
                                    }
                                }
                            } else {
                                if ($item[$k] != $v) {
                                    $allowed = false;
                                }
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
        $values = [
            'date'   => null,
            'month'  => null,
            'day'    => null,
            'year'   => null,
            'time'   => null,
            'hour'   => null,
            'minute' => null,
            'period' => null
        ];

        if (isset($this->date_format) && !empty($value) && ($value != '0000-00-00 00:00:00') && ($value != '0000-00-00')) {
            // Has time
            if (strpos($value, ' ') !== false) {
                $date = substr($value, 0, strpos($value, ' '));
                $time = substr($value, (strpos($value, ' ') + 1));
            } else {
                $date = $value;
                $time = null;
            }

            $values['date']  = date($this->date_format, strtotime($date));
            $values['month'] = date($this->month_format, strtotime($date));
            $values['day']   = date($this->day_format, strtotime($date));
            $values['year']  = date($this->year_format, strtotime($date));

            if (null !== $time) {
                $values['time']   = date($this->time_format, strtotime($time));
                $values['hour']   = date($this->hour_format, strtotime($time));
                $values['minute'] = date($this->minute_format, strtotime($time));
                $values['period'] = date($this->period_format, strtotime($time));
            }
        }

        return $values;
    }

}
