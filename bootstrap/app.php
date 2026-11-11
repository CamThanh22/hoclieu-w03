<?php
use Illuminate\Foundation\Application;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php')
    ->withMiddleware(fn ($middleware) => null)
    ->withExceptions(fn ($exceptions) => null)
    ->create();
