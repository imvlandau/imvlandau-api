<?php

namespace App\Security;

use App\Repository\UserRepository;
use App\Entity\User;
use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Utility\HttpResponse;
use Auth0\SDK\Exception\InvalidTokenException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Doctrine\ORM\EntityManagerInterface;

class Auth0Authenticator extends AbstractAuthenticator
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SdkConfiguration
     */
    protected $configuration;

    /**
     * @var Auth0
     */
    protected $auth0;

    public function __construct(LoggerInterface $logger, UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->em = $em;
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
        } catch (InvalidTokenException $e) {
            $this->logger->error($e->getMessage());
            throw new CustomUserMessageAuthenticationException('Unable to decode access token');
        }

        if (!$token['sub']) {
            throw new CustomUserMessageAuthenticationException('User ID could not be found in token');
        }

        $user = $this->userRepository->loadUserByIdentifier($token['sub']);
        // if user doesn't exist in database create a new one
        if ($user === null){
          $response = $this->auth0->authentication()->userInfo($jwt);
          // $requestBody = json_decode($response->getBody()->__toString(), true);
          if (HttpResponse::wasSuccessful($response)) {
            $userFromAuth0 = HttpResponse::decodeContent($response);
            if (!$userFromAuth0['email_verified']){
              throw new CustomUserMessageAuthenticationException('Email not yet verified. Please verify first.');
            }
            $newUser = new User();
            $newUser->setSub($userFromAuth0['sub']);
            $newUser->setEmail($userFromAuth0['email']);
            $newUser->setRoles($token['permissions']);
            $this->em->persist($newUser);
            $this->em->flush();
          } else {
            throw new CustomUserMessageAuthenticationException('User not found');
          }
        } else {
          $newUser = $user;
          $newUser->setRoles($token['permissions']);
        }

        return new SelfValidatingPassport(new UserBadge($token['sub'], function ($userId) use ($newUser) {
            return $newUser;
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
