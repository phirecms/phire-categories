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
namespace Phire\Categories\Form;

use Pop\Form\Form;
use Pop\Validator;
use Phire\Categories\Table;

/**
 * Category Form class
 *
 * @category   Phire\Categories
 * @package    Phire\Categories
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
class Category extends Form
{

    /**
     * Constructor
     *
     * Instantiate the form object
     *
     * @param  array  $fields
     * @param  string $action
     * @param  string $method
     * @return Category
     */
    public function __construct(array $fields, $action = null, $method = 'post')
    {
        parent::__construct($fields, $action, $method);
        $this->setAttribute('id', 'category-form');
        $this->setIndent('    ');
    }

    /**
     * Set the field values
     *
     * @param  array $values
     * @return Category
     */
    public function setFieldValues(array $values = null)
    {
        parent::setFieldValues($values);

        if (($_POST) && (null !== $this->uri)) {
            // Check for dupe name
            $content = Table\Categories::findBy(['uri' => $this->uri]);
            if (isset($content->id) && ($this->id != $content->id)) {
                $this->getElement('uri')
                     ->addValidator(new Validator\NotEqual($this->uri, 'That URI already exists.'));
            }
        }

        return $this;
    }

}