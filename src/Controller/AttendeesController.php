<?php

namespace App\Controller;

use App\Entity\Attendees;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\AttendeesRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller to manage attendees
 *
 * @Route("/api")
 */
class AttendeesController extends FOSRestController
{

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * Fetch attendees
     *
     * @Rest\Get("/get/attendees", name="api_attendees_get")
     *
     * @return Response
     */
    public function fetch(AttendeesRepository $attendeesRepository)
    {
        return $attendeesRepository->findAll();
    }

    /**
     * Deploy attendees
     *
     * @Rest\Post("/attendees/create", name="api_attendees_create")
     *
     * @return Response
     */
    public function create(Request $request, ValidatorInterface $validator)
    {
        try {
            $attendees = new Attendees();
            $attendees->setName($request->request->get('name'));
            $attendees->setEmail($request->request->get('email'));
            $attendees->setMobile($request->request->get('mobile'));
            $attendees->setCompanion_1($request->request->get('companion_1'));
            $attendees->setCompanion_2($request->request->get('companion_2'));
            $attendees->setCompanion_3($request->request->get('companion_3'));
            $attendees->setCompanion_4($request->request->get('companion_4'));

            $errors = [];
            $constraintValidator = $validator->validate($attendees, null, ['create']);
            if (count($constraintValidator) > 0) {
                foreach ($constraintValidator->getIterator() as $error) {
                    $errors[] = $this->translator->trans($error->getMessage());
                }
                return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            // save user provided public key
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($attendees);
            $entityManager->flush();

            return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));
        } catch (\Throwable $e) {
            throw new ApiProblemException(new ApiProblem(500, $e->getMessage()));
        }
    }

    /**
     * Delete attendees entry
     *
     * @Rest\Delete("/{id}", name="api_attendees_delete")
     *
     * @return Response
     */
    public function delete(Request $request, Attendees $attendees)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($attendees);
        $entityManager->flush();
    }
}
