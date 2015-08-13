<?php

namespace Categories\Model;

use Categories\Table;
use Phire\Model\AbstractModel;

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
        $categories    = Table\Categories::findBy(['parent_id' => $pid], null, ['order' => $order]);
        $categoriesAry = [];

        foreach ($categories->rows() as $category) {
            $this->flatMap[] = new \ArrayObject([
                'id'    => $category->id,
                'title' => $category->title,
                'uri'   => $category->uri,
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
     * Get category by URI
     *
     * @param  string $uri
     * @param  boolean $fields
     * @return void
     */
    public function getByUri($uri, $fields = false)
    {
        $category = Table\Categories::findBy(['uri' => $uri]);
        if (isset($category->id)) {
            $this->getCategory($category, $fields);
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
        $category = new Table\Categories([
            'parent_id' => ((isset($fields['category_parent_id']) && ($fields['category_parent_id'] != '----')) ?
                (int)$fields['category_parent_id'] : null),
            'title'     => $fields['title'],
            'uri'       => $fields['uri'],
            'slug'      => $fields['slug'],
            'order'     => (int)$fields['order'],
            'hierarchy' => null
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
            $category->parent_id = ((isset($fields['category_parent_id']) && ($fields['category_parent_id'] != '----')) ?
                (int)$fields['category_parent_id'] : null);
            $category->title     = $fields['title'];
            $category->uri       = $fields['uri'];
            $category->slug      = $fields['slug'];
            $category->order     = (int)$fields['order'];
            $category->hierarchy = null;
            $category->save();

            $this->changeDescendantUris($category->id, $category->uri);

            $this->data = array_merge($this->data, $category->getColumns());
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
     * Method to get content breadcrumb
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
                    $breadcrumb = '<a href="' . BASE_PATH . '/category' . $category->uri . '">' . $category->title .
                        ((isset($this->show_total) && ($this->show_total)) ? ' (' . $this->getTotal($category->id) . ')' : null) . '</a>' .
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
     * @param  int     $id
     * @return int
     */
    public function getTotal($id)
    {
        $count = Table\ContentToCategories::findBy(['category_id' => $id])->count();
        /*
        $count = (Table\ContentToCategories::findBy(['category_id' => $id])->count() > 0) ? 1 : 0;

        if ($this->recursive) {
            $child = Table\Categories::findBy(['parent_id' => $id]);
            while (isset($child->id)) {
                $count += (Table\ContentToCategories::findBy(['category_id' => $child->id])->count() > 0) ? 1 : 0;
                $child = Table\Categories::findBy(['parent_id' => $child->id]);
            }
        }
        */

        return $count;
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
        $child    = Table\Categories::findBy(['parent_id' => $category->id], null, ['order' => $order]);

        if ($child->hasRows()) {
            foreach ($child->rows() as $c) {
                $this->flatMap[] = new \ArrayObject([
                    'id'    => $c->id,
                    'title' => $c->title,
                    'uri'   => $c->uri,
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
     * Get content
     *
     * @param  Table\Categories $category
     * @param  boolean          $fields
     * @return void
     */
    protected function getCategory(Table\Categories $category, $fields = false)
    {
        if ($fields) {
            $c    = \Fields\Model\FieldValue::getModelObject('Content\Model\Content', [$category->id]);
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

        //print_r($categories);

        $items = [];
        foreach ($categories as $cat) {
            $c2c = Table\ContentToCategories::findBy(['category_id' => $cat->id], null, ['order' => 'order ASC']);
            if ($c2c->hasRows()) {
                foreach ($c2c->rows() as $c) {
                    if ($fields) {
                        $filters = ['strip_tags' => null];
                        if ($this->summary_length > 0) {
                            $filters['substr'] = [0, $this->summary_length];
                        };
                        $item = \Fields\Model\FieldValue::getModelObject(
                            $this->settings[$c->type]['model'], [$c->content_id], $this->settings[$c->type]['method']
                        );
                    } else {
                        $class = $this->settings[$c->type]['model'];
                        $model = new $class();
                        call_user_func_array([
                            $model, $this->settings[$c->type]['method']], [$c->content_id]
                        );
                        $item = $model;
                    }
                    $items[$item->id] = new \ArrayObject($item->toArray(), \ArrayObject::ARRAY_AS_PROPS);
                }
            }
        }

        $data['items']           = $items;
        $data['breadcrumb']      = $this->getBreadcrumb($data['id'], ((null !== $this->separator) ? $this->separator : '&gt;'));
        $data['breadcrumb_text'] = strip_tags($data['breadcrumb'], 'span');

        $this->data = array_merge($this->data, $data);
    }
}
