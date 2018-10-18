<?php
/**
 * @author         Pierre-Henry Soria <hi@ph7.me>
 * @copyright      (c) 2018, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; <https://www.gnu.org/licenses/gpl-3.0.en.html>
 */

namespace SparroWave\Kernel\Config;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use SparroWave\Homepage\Http\Action\Homepage;
use function FastRoute\simpleDispatcher;

class Routes
{
    public static function retrieve(): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $route) {
            $route->get('/', Homepage::class);
        });
    }
}
