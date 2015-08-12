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
     * @return array
     */
    public function getAll($sort = null)
    {
        $order         = (null !== $sort) ? $this->getSortOrder($sort) : 'order ASC';
        $categories    = Table\Categories::findBy(['parent_id' => null], null, ['order' => $order]);
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
            'title' => $fields['title'],
            'uri'   => $fields['uri'],
            'slug'  => $fields['slug'],
            'order' => (int)$fields['order']
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
            $category->title = $fields['title'];
            $category->uri   = $fields['uri'];
            $category->slug  = $fields['slug'];
            $category->order = (int)$fields['order'];
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
     * @param  boolean $total
     * @return string
     */
    public function getBreadcrumb($id, $sep = '&gt;', $total = false)
    {
        $breadcrumb = null;
        $categories    = Table\Categories::findById($id);
        if (isset($categories->id)) {
            $breadcrumb = $categories->title;
            $pId        = $categories->parent_id;

            while (null !== $pId) {
                $categories = Table\Categories::findById($pId);
                if (isset($categories->id)) {
                    if ($categories->status == 1) {
                        $breadcrumb = '<a href="' . BASE_PATH . $categories->uri . '">' . $categories->title . '</a>' .
                            '<span>' . $sep . '</span>' . $breadcrumb;
                    }
                    $pId = $categories->parent_id;
                }
            }
        }

        return $breadcrumb;
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

}
