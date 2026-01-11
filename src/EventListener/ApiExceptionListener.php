<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsEventListener(event: 'kernel.exception')]
class ApiExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Only handle exceptions for the /api path
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        // Custom message for 429
        if ($statusCode === 429) {
            $message = 'Too many requests. Please try again later.';
        } else {
            $message = $exception->getMessage();
        }

        // For 500 errors in production, you might want to hide the real message
        // if ($statusCode === 500 && $_ENV['APP_ENV'] === 'prod') {
        //     $message = 'Internal Server Error';
        // }

        $response = new JsonResponse([
            'success' => false,
            'code' => $statusCode,
            'message' => $message
        ], $statusCode);

        $event->setResponse($response);
    }
}
