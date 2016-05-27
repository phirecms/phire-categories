<?php
/**
 * Phire Categories Module
 *
 * @link       https://github.com/phirecms/phire-categories
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Phire\Categories\Model;

use Phire\Categories\Table;
use Phire\Model\AbstractModel;
use Pop\Nav\Nav;

/**
 * Category Model class
 *
 * @category   Phire\Categories
 * @package    Phire\Categories
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
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
        $rows       = [];
        $dateFields = ['publish', 'expire', 'created', 'updated', 'uploaded'];

        if (isset($this->data['id'])) {
            $sql = Table\CategoryItems::sql();
            $sql->select([
                0 => '*',
                'content_title' => DB_PREFIX . 'content.title',
                'media_title'   => DB_PREFIX . 'media.title',
            ])->join(DB_PREFIX . 'content', [DB_PREFIX . 'category_items.content_id' => DB_PREFIX . 'content.id'])
                ->join(DB_PREFIX . 'content_types', [DB_PREFIX . 'content_types.id' => DB_PREFIX . 'content.type_id'])
                ->join(DB_PREFIX . 'media', [DB_PREFIX . 'category_items.media_id' => DB_PREFIX . 'media.id'])
                ->join(DB_PREFIX . 'media_libraries', [DB_PREFIX . 'media_libraries.id' => DB_PREFIX . 'media.library_id'])
                ->where('category_id = :category_id');

            $s = ' AND ((' . $sql->quoteId('media_id') . ' IS NOT NULL) OR ((' . $sql->quoteId('media_id') . ' IS NULL) AND (((' . $sql->quoteId('strict_publishing') .
                ' = 1) AND (' . $sql->quoteId('publish') . ' <= NOW())) OR (' . $sql->quoteId('strict_publishing') .
                ' = 0)) AND ((' . $sql->quoteId('expire') . ' IS NULL) OR (' . $sql->quoteId('expire') .
                ' > NOW())) AND (' . $sql->quoteId('status') . ' = 1)))';

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

            $s = str_replace('ORDER BY', $s . ' ORDER BY', (string)$sql);

            $rows = Table\CategoryItems::execute($s, ['category_id' => $this->id])->rows(false);
        }

        if (count($rows)) {
            foreach ($rows as $key => $value) {
                if (class_exists('Phire\Fields\Model\FieldValue')) {
                    if (!empty($value['media_id'])) {
                        $item = \Phire\Fields\Model\FieldValue::getModelObject(
                            'Phire\Media\Model\Media', [$value['media_id']], 'getById', $this->data['filters']
                        );
                    } else {
                        $item = \Phire\Fields\Model\FieldValue::getModelObject(
                            'Phire\Content\Model\Content', [$value['content_id']], 'getById', $this->data['filters']
                        );
                    }
                    $value = array_merge((array)$value, $item->toArray());
                } else if (!empty($value['media_id'])) {
                    $media = new \Phire\Media\Model\Media();
                    $media->getById($value['media_id']);
                    $value = array_merge((array)$value, $media->toArray());
                }

                foreach ($value as $ky => $vl) {
                    if (in_array($ky, $dateFields)) {
                        $dateValues = $this->formatDateAndTime($vl);
                        foreach ($dateValues as $k => $v) {
                            $value[$ky . '_' . $k] = $v;
                        }
                    }
                }

                $rows[$key] = new \ArrayObject($value, \ArrayObject::ARRAY_AS_PROPS);
            }
        }

        return $rows;
    }

    /**
     * Get category items
     *
     * @param  mixed $id
     * @param  int   $limit
     * @return array
     */
    public function getCategoryChildren($id, $limit = null)
    {
        if (!is_numeric($id)) {
            $category = Table\Categories::findBy(['title' => $id]);
            if (isset($category->id)) {
                $id = $category->id;
            }
        }

        $options = ['order' => 'order ASC'];

        if (null !== $limit) {
            $options['limit'] = (int)$limit;
        }

        $children = Table\Categories::findBy(['parent_id' => $id], $options);

        $items = [];

        if ($children->hasRows()) {
            foreach ($children->rows() as $child) {
                $c = new Category();
                $c->show_total = $this->show_total;
                $c->filters = $this->filters;
                $c->getById($child->id);
                $childItem = $c->getItems(1);
                $filtered  = [];
                if (isset($childItem[0])) {
                    foreach ($childItem[0] as $key => $value) {
                        $filtered['item_' . $key] = $value;
                    }
                }

                $items[]  = new \ArrayObject(array_merge([
                    'category_id'    => $child->id,
                    'category_title' => $child->title,
                    'category_uri'   => '/category' . $child->uri,
                    'category_total' => Table\CategoryItems::findBy(['category_id' => $child->id])->count()
                ], $filtered), \ArrayObject::ARRAY_AS_PROPS);
            }
        }

        return $items;
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
        return ($this->getCount() > $limit);
    }

    /**
     * Get count of category items
     *
     * @return int
     */
    public function getCount()
    {
        $sql = Table\CategoryItems::sql();
        $sql->select(['total_count' => 'COUNT(*)'])
            ->join(DB_PREFIX . 'content', [DB_PREFIX . 'category_items.content_id' => DB_PREFIX . 'content.id'])
            ->join(DB_PREFIX . 'media', [DB_PREFIX . 'category_items.media_id' => DB_PREFIX . 'media.id'])
            ->join(DB_PREFIX . 'media_libraries', [DB_PREFIX . 'media_libraries.id' => DB_PREFIX . 'media.library_id'])
            ->where('category_id = :category_id')
            ->where('media_id IS NOT NULL');

        $s = (string)$sql . ' OR ((' . $sql->quoteId('media_id') . ' IS NULL) AND (' . $sql->quoteId('publish') .
            ' <= NOW()) AND ((' . $sql->quoteId('expire') . ' IS NULL) OR (' . $sql->quoteId('expire') .
            ' > NOW())) AND (' . $sql->quoteId('status') . ' = 1))';

        return Table\CategoryItems::execute($s, ['category_id' => $this->id])->total_count;
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

        if (isset($this->datetime_formats) && !empty($value) && ($value != '0000-00-00 00:00:00') && ($value != '0000-00-00')) {
            // Has time
            if (strpos($value, ' ') !== false) {
                $date = substr($value, 0, strpos($value, ' '));
                $time = substr($value, (strpos($value, ' ') + 1));
            } else {
                $date = $value;
                $time = null;
            }

            $values['date']  = date($this->datetime_formats['date_format'], strtotime($date));
            $values['month'] = date($this->datetime_formats['month_format'], strtotime($date));
            $values['day']   = date($this->datetime_formats['day_format'], strtotime($date));
            $values['year']  = date($this->datetime_formats['year_format'], strtotime($date));

            if (null !== $time) {
                $values['time']   = date($this->datetime_formats['time_format'], strtotime($time));
                $values['hour']   = date($this->datetime_formats['hour_format'], strtotime($time));
                $values['minute'] = date($this->datetime_formats['minute_format'], strtotime($time));
                $values['period'] = date($this->datetime_formats['period_format'], strtotime($time));
            }
        }

        return $values;
    }

}
