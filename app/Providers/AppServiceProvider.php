<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->routes(function (Route $route) {
                return Str::startsWith($route->uri, 'api/');
            });

        \Storage::extend('azure', function ($app, $config) {
            $client = BlobRestProxy::createBlobService(
                sprintf(
                    'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
                    $config['name'],
                    $config['key']
                )
            );

            $adapter = new AzureBlobStorageAdapter($client, $config['container']);

            return new Filesystem($adapter);
        });
    }
}
