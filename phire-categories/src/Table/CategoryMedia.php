<?php

namespace Phire\Categories\Table;

use Pop\Db\Record;

class CategoryMedia extends Record
{

    /**
     * Table prefix
     * @var string
     */
    protected $prefix = DB_PREFIX;

    /**
     * Table name
     * @var string
     */
    protected $table = 'category_items';

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['category_id', 'media_id'];

}