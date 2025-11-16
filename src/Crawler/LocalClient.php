<?php

declare(strict_types=1);

namespace Spatie\Export\Crawler;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Spatie\Export\Constants;
use Spatie\Export\Utils;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class LocalClient extends Client
{
    /** @var \Illuminate\Contracts\Http\Kernel */
    protected $kernel;

    protected \Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory $psrHttpFactory;

    public function __construct()
    {
        parent::__construct();

        $this->kernel = app()->get(HttpKernel::class);

        $psr17Factory = new Psr17Factory;

        $this->psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        Utils::configureExportKernel($this->kernel);

        $localRequest = Request::create((string) $request->getUri());

        $localRequest->headers->set(Constants::EXPORT_HEADER, 'true');

        $response = $this->kernel->handle($localRequest);

        $psrResponse = $this->psrHttpFactory->createResponse($response);

        return new FulfilledPromise($psrResponse);
    }
}
