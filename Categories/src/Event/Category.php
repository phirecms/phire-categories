<?php

namespace Categories\Event;

use Categories\Table;
use Pop\Application;
use Pop\Web\Mobile;
use Pop\Web\Session;
use Phire\Controller\AbstractController;

class Category
{

    /**
     * Bootstrap the module
     *
     * @param  Application $application
     * @return void
     */
    public static function bootstrap(Application $application)
    {
        if ($application->isRegistered('Content')) {

        }
        if ($application->isRegistered('Media')) {

        }
    }

}
