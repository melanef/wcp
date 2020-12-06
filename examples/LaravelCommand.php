<?php

namespace Melanef\Examples;

use Melanef\Examples\Components\FamilyComponent;
use Melanef\Examples\Components\FamilyDomainComponent;
use Melanef\Examples\Tls\Options;
use Melanef\Examples\Tls\WssApp;
use Melanef\Wcp\ServerProtocol;

class LaravelCommand extends \Illuminate\Console\Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initializing Websocket server to receive and manage connections';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle()
    {
        if ($this->laravel->environment() !== 'PRODUCTION') {
            putenv('RATCHET_DISABLE_XDEBUG_WARN=true');
        }

        $server = new WssApp(
            config('websockets.host'),
            config('websockets.port'),
            '0.0.0.0',
            null,
            new Options(
                config('websockets.encryption_certificate'),
                config('websockets.encryption_key'),
                true,
                false,
                false
            )
        );
        $server->route(
            FamilyDomainComponent::ROUTE,
            new ServerProtocol($this->laravel->make(FamilyDomainComponent::class)),
            ['*']
        );

        $server->run();
    }
}
