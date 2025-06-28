<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CORS Event Listener
 * Handles Cross-Origin Resource Sharing headers for all API requests
 */
class CorsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9999],
            KernelEvents::RESPONSE => ['onKernelResponse', 9999],
        ];
    }

    /**
     * Handle preflight OPTIONS requests
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Don't do anything if it's not the master request
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getRealMethod();

        // Handle preflight OPTIONS requests
        if ('OPTIONS' === $method) {
            $response = new Response();
            $this->addCorsHeaders($response, $request);
            $event->setResponse($response);
        }
    }

    /**
     * Add CORS headers to all responses
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        // Don't do anything if it's not the master request
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Add CORS headers to all responses (not just API routes)
        $this->addCorsHeaders($response, $request);
    }

    /**
     * Add CORS headers to response based on request origin
     */
    private function addCorsHeaders(Response $response, $request): void
    {
        $origin = $request->headers->get('Origin');
        
        // List of allowed origins
        $allowedOrigins = [
            'http://localhost:9000',
            'http://127.0.0.1:9000',
            'http://staff.local',
            'http://api.staff.local',
            'https://api-triangle.unaux.com'
        ];
        
        // Check if the origin is allowed
        if ($origin && in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } else {
            // Fallback to wildcard for unknown origins (without credentials)
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }
        
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }
}