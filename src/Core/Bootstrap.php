<?php
/**
 * @author         Pierre-Henry Soria <hello@lifyzer.com>
 * @copyright      (c) 2018, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; <https://www.gnu.org/licenses/gpl-3.0.en.html>
 * @link           https://lifyzer.com
 */

declare(strict_types=1);

use Dotenv\Dotenv;
use Lifyzer\Server\Core\Container\Container;
use Lifyzer\Server\Core\Container\Provider\Database as DatabaseContainer;
use Lifyzer\Server\Core\Container\Provider\HttpRequest as HttpRequestContainer;
use Lifyzer\Server\Core\Container\Provider\Monolog as MonologContainer;
use Lifyzer\Server\Core\Container\Provider\SwiftMailer as SwiftMailerContainer;
use Lifyzer\Server\Core\Container\Provider\Twig as TwigContainer;
use Lifyzer\Server\Core\Debug;
use Lifyzer\Server\Core\Uri\Router;
use PierreHenry\Container\Container;
use PierreHenry\Container\Providable;
use Symfony\Component\HttpFoundation\Request;
use Whoops\Handler\PrettyPageHandler;

namespace SparroWave\Kernel\Core;

class Bootstrap
{
    /** @var string */
    private $basePath;

    /**
     * @param string $basePath The base app path.
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;

        $this->initializeExceptionHandler();
    }

    public function initializeConfiguration(): void
    {
        (new Dotenv($this->basePath))->load();
        define('SITE_NAME', getenv('SITE_NAME'));
        define('SITE_URL', getenv('SITE_URL'));
        Debug::initializeMode();
    }

    public function initializeContainers(): void
    {
        $container = new Container();

        $container->register(
            TwigContainer::class,,
            new class implements Providable
            {
                private const VIEW_FOLDER = '/App/View/';
                private const CACHE_FOLDER = '/cache/';

                public function getService(): Twig_Environment
                {
                    $rootPath = dirname(__DIR__, 3);

                    $loader = new Twig_Loader_Filesystem($rootPath . self::VIEW_FOLDER);
                    $cacheStatus = filter_var(getenv('CACHE'), FILTER_VALIDATE_BOOLEAN);

                    return new Twig_Environment($loader, [
                        'cache' => $cacheStatus ? $rootPath . self::CACHE_FOLDER : false,
                        'debug' => DEBUG_MODE
                    ]);
                }
            }
        );

        $container->register(
            Monolog\Logger::class,
            new class implements Providable
            {
                private const LOG_DIR = '/log/';
                private const LOG_EXT = '.log';

                /** @var string */
                private $name;

                public function __construct()
                {
                    $this->name = getenv('LOGGING_CHANNEL');
                }

                /**
                 * @return LoggerInterface
                 *
                 * @throws Exception
                 */
                public function getService(): LoggerInterface
                {
                    $rootPath = dirname(__DIR__, 3);
                    $streamHandler = new StreamHandler(
                        $rootPath . self::LOG_DIR . $this->name . self::LOG_EXT,
                        Logger::DEBUG
                    );

                    $log = new Logger($this->name);
                    $log->pushHandler($streamHandler);

                    return $log;
                }
            }
        );

        $container->register(
            Request::class,
            new class implements Providable
            {
                public function getService(): Request
                {
                    return Request::createFromGlobals();
                }
            }
        );

        $container->register(
            Request::class,
            new class implements Providable
            {
                public function getService(): Swift_Mailer
                {
                    $transport = new Swift_SendmailTransport();

                    return new Swift_Mailer($transport);
                }
            }
        );

        $dispatcher = require __DIR__ . '/src/config/routes.php';
        $router = new Router($dispatcher, $container);
        $router->dispatch();
    }

    private function initializeExceptionHandler(): void
    {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new PrettyPageHandler);
        $whoops->register();
    }
}
