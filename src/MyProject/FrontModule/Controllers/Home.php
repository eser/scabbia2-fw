<?php
/**
 * Scabbia2 PHP Framework
 * https://github.com/eserozvataf/scabbia2
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        https://github.com/eserozvataf/scabbia2 for the canonical source repository
 * @copyright   2010-2016 Eser Ozvataf. (http://eser.ozvataf.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

namespace MyProject\FrontModule\Controllers;

use Scabbia\Config\Config;
use Scabbia\Helpers\String;
use Scabbia\Router\Router;
use Scabbia\Views\Views;
use MyProject\FrontModule\Controllers\BaseController;
use MyProject\FrontModule\Processors\FacadeTest;

/**
 * Home controller
 *
 * @package     MyProject\FrontModule\Controllers
 * @author      Eser Ozvataf <eser@ozvataf.com>
 * @since       2.0.0
 */
class Home extends BaseController
{
    /**
     * GET / action
     *
     * @route {method: [get, post], name: "home", path: "/"}
     *
     * @return void
    */
    public function getIndex()
    {
        $this->bind("MyProject\\FrontModule\\Models\\HomeModel");

        $tFiles = get_included_files();
        echo '<pre>';
        var_dump($tFiles);

        $this->vars->set("moduleName", $this->moduleConfig["fancyName"]);
        $this->vars->set("welcomeText", $this->homeModel->getWelcomeMessage());

        $this->view("Pages\\Home\\index.view.php");
    }

    /**
     * GET /link action
     *
     * @route {method: get, name: "home/link", path: "/link"}
     *
     * @return void
     */
    public function getLink()
    {
        echo Router::path("home/user", ["id" => "eser"]);
    }

    /**
     * GET /user/? action
     *
     * @route {method: get, name: "home/user", path: "/user/{id:[0-9]+}"}
     *
     * @param int $uUserId user id
     *
     * @return void
     */
    public function getUser($uUserId)
    {
        echo "helo: {$uUserId}<br />";
    }

    /**
     * GET /config action
     *
     * @route {method: get, name: "home/config", path: "/config"}
     *
     * @return void
     */
    public function getConfig()
    {
        // $tConfig = new Config();
        // $tConfig->add("/var/www/test.yml");
        // $tConfig->add("/var/www/test2.yml");

        // var_dump($tConfig->get());

        echo "application configuration:<br />";
        String::vardump($this->application->config);
        echo "<br />";

        echo "module configuration:<br />";
        String::vardump($this->moduleConfig);
        echo "<br />";
    }

    /**
     * GET /facades action
     *
     * @route {method: get, name: "home/facades", path: "/facades"}
     *
     * @return void
     */
    public function getFacades()
    {
        echo FacadeTest::slug('Deneme yazisi');
    }
}
