<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\ParticipantRepository;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Controller to manage event settings
 *
 * @Route("/api")
 */
class SettingsController extends AbstractFOSRestController
{
    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    /**
     * Fetch settings
     *
     * @Rest\Get("/settings/fetch", name="api_settings_fetch")
     *
     * @return Response
     */
    public function fetch(SettingsRepository $settingsRepository)
    {
        return $settingsRepository->getFirst();
    }

    /**
     * Save settings
     *
     * @Rest\Post("/settings/save", name="api_settings_save")
     *
     * @return Response
     */
    public function create(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        SettingsRepository $settingsRepository,
        ParticipantRepository $participantRepository
    ) {
        try {
            $eventMaximumAmount = $request->request->get('eventMaximumAmount');
            $eventDate = $request->request->get('eventDate');
            $eventTime1 = $request->request->get('eventTime1');
            $eventTime2 = $request->request->get('eventTime2');
            $eventTopic = $request->request->get('eventTopic');
            $eventLocation = $request->request->get('eventLocation');
            $eventEmailSubject = $request->request->get('eventEmailSubject');
            $eventEmailTemplate = $request->request->get('eventEmailTemplate');

            $settings = $settingsRepository->getFirst();
            if (empty($settings)) {
              $settings = new Settings();
            }
            $settings->setEventMaximumAmount($eventMaximumAmount);
            $settings->setEventDate($eventDate ? new \DateTime($eventDate) : null);
            $settings->setEventTime1($eventTime1 ? new \DateTime($eventTime1) : null);
            $settings->setEventTime2($eventTime2 ? new \DateTime($eventTime2) : null);
            $settings->setEventTopic($eventTopic);
            $settings->setEventLocation($eventLocation);
            $settings->setEventEmailSubject($eventEmailSubject);
            $settings->setEventEmailTemplate($eventEmailTemplate);

            $errors = [];
            $constraintValidator = $validator->validate($settings, null, ['save']);
            if (count($constraintValidator) > 0) {
                foreach ($constraintValidator->getIterator() as $key => $error) {
                    $errors[$key] = [
                        "key" => $error->getMessageTemplate(),
                        "message" => $this->translator->trans($error->getMessage()),
                        "type" => "error",
                    ];
                }
                return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
            }
            $entityManager->persist($settings);
            $entityManager->flush();

            $participantRepository->truncate();

            return new JsonResponse(Response::$statusTexts[200], Response::HTTP_OK);
        } catch (\Throwable $e) {
            throw new ApiProblemException(new ApiProblem(500, $e->getMessage()));
        }
    }

}
