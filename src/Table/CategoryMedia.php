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
namespace Phire\Categories\Table;

use Pop\Db\Record;

/**
 * Category Media Table class
 *
 * @category   Phire\Categories
 * @package    Phire\Categories
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
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