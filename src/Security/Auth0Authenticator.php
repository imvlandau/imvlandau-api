<?php

namespace App\Security;

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Utility\HttpResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class Auth0Authenticator extends AbstractAuthenticator
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SdkConfiguration
     */
    protected $configuration;

    /**
     * @var Auth0
     */
    protected $auth0;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->configuration = new SdkConfiguration([
          'strategy' => 'api',
          'domain' => $_SERVER['AUTH0_DOMAIN'],
          'audience' => [$_SERVER['AUTH0_AUDIENCE']],
          'clientId' => $_SERVER['AUTH0_CLIENT_ID'],
        ]);
        $this->auth0 = new Auth0($this->configuration);
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') && substr($request->headers->get('Authorization'), 0, 7) == 'Bearer ';
    }

    public function authenticate(Request $request): PassportInterface
    {
        $jwt = substr($request->headers->get('Authorization'), 7);

        if (!$jwt || $jwt == '') {
            throw new CustomUserMessageAuthenticationException('Authorization token missing');
        }

        try {
            $token = $this->auth0->decode($jwt)->toArray();
            // $userId = $token->getSubject();
            $userId = $token['sub'];
            $permissions = $token['permissions'];

            // $response = $this->auth0->authentication()->userInfo($jwt);
            // $requestBody = json_decode($response->getBody()->__toString(), true);
            // echo "<pre>" . print_r($requestBody, true) . "</pre>";
            // if (HttpResponse::wasSuccessful($response)) {
            //     $user = HttpResponse::decodeContent($response);
            //     echo "<pre>" . print_r($user, true) . "</pre>";
            // }
            // exit;
        } catch (\Auth0\SDK\Exception\InvalidTokenException $exception) {
            $this->logger->error($e->getMessage());
            throw new CustomUserMessageAuthenticationException('Unable to decode access token');
        }

        if (!$userId) {
            throw new CustomUserMessageAuthenticationException('User ID could not be found in token');
        }

        return new SelfValidatingPassport(new UserBadge($userId, function ($userIdentifier) use ($permissions) {
            return new InMemoryUser($userIdentifier, NULL, $permissions);
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return NULL;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $exception->getMessage(),
        ], 401);
    }
}
