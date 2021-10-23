<?php

namespace App\EventSubscriber;

use App\Exception\ApiProblem;
use App\Exception\ApiProblemExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [
                ['processException', 10],
                ['logException', 0],
                ['notifyException', -10],
            ],
        ];
    }

    public function processException(ExceptionEvent $event)
    {
        $ex = $event->getThrowable();
        if ($ex instanceof ApiProblemExceptionInterface) {
            $apiProblem = $ex->getApiProblem();
            $statusCode = $apiProblem->getStatusCode();
        } else {
            $statusCode = $ex instanceof HttpExceptionInterface ? $ex->getStatusCode() : 500;
            $apiProblem = new ApiProblem($statusCode);
        }

        if ($_SERVER['APP_ENV'] === "dev" && $_SERVER['APP_DEBUG']) {
            $request = $event->getRequest();
            $routeName = $request->get('_route');
            $apiProblem->set('route', $routeName);
            $apiProblem->set('message', $ex->getMessage());
            $apiProblem->set('exception', [
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'trace' => $ex->getTrace(),
            ]);
        }

        $response = new JsonResponse(
            $apiProblem->toArray(),
            $statusCode
        );
        $response->setStatusCode($statusCode);
        $response->headers->set('Content-Type', 'application/problem+json');

        $event->setResponse($response);
    }

    public function logException(ExceptionEvent $event)
    {
        //TODO
    }

    public function notifyException(ExceptionEvent $event)
    {
        //TODO
    }
}
