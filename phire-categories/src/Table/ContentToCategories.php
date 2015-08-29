<?php

namespace Phire\Categories\Table;

use Pop\Db\Record;

class ContentToCategories extends Record
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
    protected $primaryKeys = ['content_id', 'category_id'];

}