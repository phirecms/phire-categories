<?php

namespace Phire\Categories\Model;

use Phire\Categories\Table;
use Phire\Model\AbstractModel;
use Pop\Nav\Nav;

class Category extends AbstractModel
{

    protected $flatMap = [];

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
                'total' => Table\CategoryItems::findBy(['category_id' => $category->id])->count(),
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
     * Get category items
     *
     * @param  int    $limit
     * @param  int    $page
     * @return array
     */
    public function getItems($limit = null, $page = null)
    {
        $rows = [];

        if (isset($this->data['id'])) {
            $sql = Table\CategoryItems::sql();

            $sql->select()
                ->join(DB_PREFIX . 'content', [DB_PREFIX . 'category_items.content_id' => DB_PREFIX . 'content.id'])
                ->join(DB_PREFIX . 'media', [DB_PREFIX . 'category_items.media_id' => DB_PREFIX . 'media.id'])
                ->join(DB_PREFIX . 'media_libraries', [DB_PREFIX . 'media_libraries.id' => DB_PREFIX . 'media.library_id'])
                ->where('category_id = :category_id');

            if (null !== $limit) {
                $page = ((null !== $page) && ((int)$page > 1)) ?
                    ($page * $limit) - $limit : null;

                $sql->select()->limit($limit)->offset($page);
            }

            if (isset($this->data['order_by_field']) && isset($this->data['order_by_field'])) {
                $by    = $this->data['order_by_field'];
                $order = $this->data['order_by_order'];
            } else {
                $by    = DB_PREFIX . 'category_items.order';
                $order = 'ASC';
            }
            $sql->select()->orderBy($by, $order);

            $rows = Table\CategoryItems::execute((string)$sql, ['category_id' => $this->id])->rows();
        }

        if (count($rows) && class_exists('Phire\Fields\Model\FieldValue')) {
            foreach ($rows as $key => $value) {
                if (!empty($value['media_id'])) {
                    $item = \Phire\Fields\Model\FieldValue::getModelObject(
                        'Phire\Media\Model\Media', [$value['media_id']], 'getById', $this->data['filters']
                    );
                } else {
                    $item = \Phire\Fields\Model\FieldValue::getModelObject(
                        'Phire\Content\Model\Content', [$value['content_id']], 'getById', $this->data['filters']
                    );
                }
                $rows[$key] = new \ArrayObject(array_merge((array)$value, $item->toArray()), \ArrayObject::ARRAY_AS_PROPS);
            }
        }

        return $rows;
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
            $data = $category->getColumns();
            $data['category_parent_id'] = $data['parent_id'];
            unset($data['parent_id']);
            $this->data = array_merge($this->data, $data);
        }
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
            'parent_id'      => $parentId,
            'title'          => $fields['title'],
            'uri'            => $fields['uri'],
            'slug'           => $fields['slug'],
            'order'          => (int)$fields['order'],
            'order_by_field' => $fields['order_by_field'],
            'order_by_order' => $fields['order_by_order'],
            'filter'         => (int)$fields['filter'],
            'pagination'     => (int)$fields['pagination'],
            'hierarchy'      => $this->getHierarchy($parentId)
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

            $category->parent_id      = $parentId;
            $category->title          = $fields['title'];
            $category->uri            = $fields['uri'];
            $category->slug           = $fields['slug'];
            $category->order          = (int)$fields['order'];
            $category->order_by_field = $fields['order_by_field'];
            $category->order_by_order = $fields['order_by_order'];
            $category->filter         = (int)$fields['filter'];
            $category->pagination     = (int)$fields['pagination'];
            $category->hierarchy      = $this->getHierarchy($parentId);
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
                $key      = substr($key, (strpos($key, '_') + 1));
                $orderAry = explode('_', $key);
                $catItem  = ($orderAry[0] == 'media') ?
                    Table\CategoryMedia::findById([(int)$post['category_id'], (int)$orderAry[1]]) :
                    Table\CategoryContent::findById([(int)$post['category_id'], (int)$orderAry[1]]);

                if (isset($catItem->category_id)) {
                    $catItem->order = (int)$value;
                    $catItem->save();
                }
            }
        }

        if (isset($post['rm_category_items'])) {
            foreach ($post['rm_category_items'] as $item) {
                $idAry   = explode('_', $item);
                $catItem = ($idAry[0] == 'media') ?
                    Table\CategoryMedia::findById([(int)$post['category_id'], (int)$idAry[1]]) :
                    Table\CategoryContent::findById([(int)$post['category_id'], (int)$idAry[1]]);
                if (isset($catItem->category_id)) {
                    $catItem->delete();
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
        $count    = Table\CategoryItems::findBy(['category_id' => $id])->count();
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
     * Determine if list of category items has pages
     *
     * @param  int $limit
     * @return boolean
     */
    public function hasPages($limit)
    {
        return (Table\CategoryItems::findBy(['category_id' => $this->id])->count() > $limit);
    }

    /**
     * Get count of category items
     *
     * @return int
     */
    public function getCount()
    {
        return Table\CategoryItems::findBy(['category_id' => $this->id])->count();
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
     * Get category view content
     *
     * @param  mixed $id
     * @return array
     */
    public function getCategoryViewItems($id)
    {
        $items    = [];
        $catItems = Table\CategoryItems::findBy(['category_id' => $id], ['order' => 'order ASC']);

        if ($catItems->hasRows()) {
            foreach ($catItems->rows() as $c) {
                if ($c->media_id != 0) {
                    $media   = \Phire\Media\Table\Media::findById($c->media_id);
                    $title   = (isset($media->id)) ? $media->title : '[N/A]';
                    $item_id = $c->media_id;
                    $type    = 'media';
                } else {
                    $content = \Phire\Content\Table\Content::findById($c->content_id);
                    $title   = (isset($content->id)) ? $content->title : '[N/A]';
                    $item_id = $c->content_id;
                    $type    = 'content';
                }
                $items[] = [
                    'title'   => $title,
                    'item_id' => $item_id,
                    'type'    => $type,
                    'order'   => $c->order
                ];
            }
        }

        return $items;
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
                    'total' => Table\CategoryItems::findBy(['category_id' => $c->id])->count(),
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
