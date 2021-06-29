<?php

namespace App\Controller;

use App\Entity\Server;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\ServerRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller to manage servers
 *
 * @Route("/api")
 */
class ServerController extends FOSRestController
{

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * Fetch servers
     *
     * @Rest\Get("/get/servers", name="api_servers_get")
     *
     * @return Response
     */
    public function fetch(ServerRepository $serverRepository)
    {
        return $serverRepository->findAll();
    }

    /**
     * Deploy server
     *
     * @Rest\Post("/server/deploy", name="api_server_deploy")
     *
     * @return Response
     */
    public function deploy(Request $request, ValidatorInterface $validator)
    {
        try {
            $server = new Server();
            $server->setName($pmb_label);

            $errors = [];
            $constraintValidator = $validator->validate($server, null, ['create']);
            if (count($constraintValidator) > 0) {
                foreach ($constraintValidator->getIterator() as $error) {
                    $errors[] = $this->translator->trans($error->getMessage());
                }
                return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // save user provided public key
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($server);
            $entityManager->flush();

            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
        } catch (\Throwable $e) {
            throw new ApiProblemException(new ApiProblem(500, $e->getMessage()));
        }
    }

    /**
     * Delete server entry
     *
     * @Rest\Delete("/{id}", name="api_server_delete")
     *
     * @return Response
     */
    public function delete(Request $request, Server $server)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($server);
        $entityManager->flush();
    }
}
