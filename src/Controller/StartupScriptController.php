<?php

namespace App\Controller;

use App\Entity\StartupScript;
use App\Repository\StartupScriptRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller to manage initial startup shell scripts
 *
 * @Route("/api/startup/script")
 */
class StartupScriptController extends FOSRestController
{

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * Fetch startup scripts
     *
     * @Rest\Get("/", name="api_startup_scripts_fetch")
     *
     * @return Response
     */
    public function fetch(StartupScriptRepository $startupScriptRepository)
    {
        return $startupScriptRepository->findAll();
    }

    /**
     * Create startup script
     *
     * @Rest\Post("/create", name="api_startup_script_create")
     *
     * @return Response
     */
    public function create(Request $request, StartupScriptRepository $startupScriptRepository, ValidatorInterface $validator)
    {
        $startupScript = new StartupScript();
        $name = trim($request->request->get('name'));
        $content = trim($request->request->get('content'));
        $filename = "$name.sh";

        $startupScript->setName($name);
        $startupScript->setContent($content);

        $errors = [];
        $constraintValidator = $validator->validate($startupScript, null, ['create']);
        if (count($constraintValidator) > 0) {
            foreach ($constraintValidator->getIterator() as $error) {
                $errors[] = [
                    "key" => $error->getMessageTemplate(),
                    "message" => $this->translator->trans($error->getMessage()),
                    "type" => "error",
                ];
            }
            return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($startupScript);
        $entityManager->flush();

        return [
            "startupScript" => [
                "id" => $startupScript->getId(),
                "name" => $startupScript->getName(),
                "content" => $startupScript->getContent(),
            ],
            "startupScripts" => $startupScriptRepository->findAll(),
        ];
    }

    /**
     * Delete startup script entry
     *
     * @Rest\Delete("/{id}", name="api_startup_script_delete")
     *
     * @return Response
     */
    public function delete(Request $request, StartupScript $startupScript)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($startupScript);
        $entityManager->flush();
    }
}
