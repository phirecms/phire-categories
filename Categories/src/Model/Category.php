<?php

namespace Categories\Model;

use Categories\Table;
use Phire\Model\AbstractModel;

class Category extends AbstractModel
{

    /**
     * Get all categories
     *
     * @param  string $sort
     * @return array
     */
    public function getAll($sort = null)
    {
        $order = (null !== $sort) ? $this->getSortOrder($sort) : 'id ASC';

        $categoriesAry = [];
        $categories    = Table\Categories::findBy(['parent_id' => null], null, ['order' => $order]);

        foreach ($categories->rows() as $category) {
            $categoriesAry[] = $category;
            $children = Table\Categories::findBy(['parent_id' => $category->id], null, ['order' => $order]);
            foreach ($children->rows() as $child) {
                $child->name = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&gt; ' . $child->name;
                $categoriesAry[] = $child;
            }
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
            'name'  => $fields['name'],
            'uri'   => $fields['uri'],
            'slug'  => $fields['slug'],
            'order' => (int)$fields['slug']
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
            $category->name  = $fields['name'];
            $category->uri   = $fields['uri'];
            $category->slug  = $fields['slug'];
            $category->order = (int)$fields['slug'];
            $category->save();

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
     * Get parents
     *
     * @param  int $id
     * @return array
     */
    public function getParents($id = null)
    {
        $parents = [];

        $categories = Table\Categories::findAll();
        foreach ($categories->rows() as $c) {
            if ($c->id != $id) {
                $pid   = $c->parent_id;
                $depth = 0;
                $isAncestor = false;
                while (null !== $pid) {
                    if ($pid ==  $id) {
                        $isAncestor = true;
                        break;
                    }
                    $parent = Table\Categories::findById($pid);
                    if (isset($parent->id)) {
                        $pid = $parent->parent_id;
                        $depth++;
                    }
                }

                if (!$isAncestor) {
                    $parents[$c->id] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth) .
                        (($depth > 0) ? ' - ' : '') . $c->name;
                }
            }
        }

        return $parents;
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
            $breadcrumb = $categories->name;
            $pId        = $categories->parent_id;

            while (null !== $pId) {
                $categories = Table\Categories::findById($pId);
                if (isset($categories->id)) {
                    if ($categories->status == 1) {
                        $breadcrumb = '<a href="' . BASE_PATH . $categories->uri . '">' . $categories->name . '</a>' .
                            '<span>' . $sep . '</span>' . $breadcrumb;
                    }
                    $pId = $categories->parent_id;
                }
            }
        }

        return $breadcrumb;
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

}
