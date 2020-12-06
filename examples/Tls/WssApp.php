<?php

namespace Melanef\Examples\Tls;

use Ratchet\App;
use Ratchet\Http\HttpServer;
use Ratchet\Http\Router;
use Ratchet\Server\FlashPolicy;
use Ratchet\Server\IoServer;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Socket\Server as Reactor;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class WssApp extends App
{
    const OPTION_TLS = 'tls';

    /**
     * @param string          $httpHost HTTP hostname clients intend to connect to. MUST match JS `new WebSocket('ws://$httpHost');`
     * @param int             $port Port to listen on. If 80, assuming production, Flash on 843 otherwise expecting Flash to be proxied through 8843
     * @param string          $address IP address to bind to. Default is localhost/proxy only. '0.0.0.0' for any machine.
     * @param LoopInterface   $loop Specific React\EventLoop to bind the application to. null will create one for you.
     * @param Options|null    $tlsOptions Set of options for TSL/WSS used on Reactor initialization
     */
    public function __construct(
        $httpHost = 'localhost',
        $port = 8080,
        $address = '127.0.0.1',
        LoopInterface $loop = null,
        Options $tlsOptions = null
    ) {
        if (extension_loaded('xdebug') && getenv('RATCHET_DISABLE_XDEBUG_WARN') === false) {
            trigger_error('XDebug extension detected. Remember to disable this if performance testing or going live!', E_USER_WARNING);
        }

        if (null === $loop) {
            $loop = LoopFactory::create();
        }

        $this->httpHost = $httpHost;
        $this->port = $port;

        $options = [];

        if (null !== $tlsOptions) {
            $options[self::OPTION_TLS] = $tlsOptions->toArray();
        }

        $socket = new Reactor($address . ':' . $port, $loop, $options);

        $this->routes  = new RouteCollection();
        $this->_server = new IoServer(new HttpServer(new Router(new UrlMatcher($this->routes, new RequestContext))), $socket, $loop);

        $policy = new FlashPolicy;
        $policy->addAllowedAccess($httpHost, 80);
        $policy->addAllowedAccess($httpHost, $port);

        if (80 == $port) {
            $flashUri = '0.0.0.0:843';
        } else {
            $flashUri = 8843;
        }

        $flashSock = new Reactor($flashUri, $loop);
        $this->flashServer = new IoServer($policy, $flashSock);
    }
}
