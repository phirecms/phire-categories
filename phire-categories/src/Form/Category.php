<?php

namespace Phire\Categories\Form;

use Pop\Form\Form;
use Pop\Validator;
use Phire\Categories\Table;

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