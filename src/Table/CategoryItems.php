<?php

namespace Phire\Categories\Table;

use Pop\Db\Record;

class CategoryItems extends Record
{

    /**
     * Table prefix
     * @var string
     */
    protected $prefix = DB_PREFIX;

    /**
     * Primary keys
     * @var array
     */
    protected $primaryKeys = ['category_id', 'content_id', 'media_id'];

}