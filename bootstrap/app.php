<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            
            if($request != null)
            {
                $data = $request->all();

                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                    'data' => $data
                ];
            } else
            {
                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'errors' => $e->errors()
                ];
            }

            return response()->json($response, 422);
        });
    })->create();
