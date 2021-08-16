<?php

namespace App\Controller;

use App\Entity\Attendees;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\AttendeesRepository;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Utils\RandomStringGenerator;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCodeBundle\Response\QrCodeResponse;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Controller to manage attendees
 *
 * @Route("/api")
 */
class AttendeesController extends FOSRestController
{
    public function __construct(
        TranslatorInterface $translator,
        RandomStringGenerator $randomStringGenerator,
        BuilderInterface $customQrCodeBuilder,
        $randomStringGeneratorAlphabet = null
    ) {
        $this->translator = $translator;
        $this->customQrCodeBuilder = $customQrCodeBuilder;
        $this->randomStringGenerator = $randomStringGenerator;
        $this->randomStringGenerator->setAlphabet($randomStringGeneratorAlphabet);
    }

    /**
     * Fetch attendees
     *
     * @Rest\Get("/attendees/fetch", name="api_attendees_fetch")
     * @IsGranted("ROLE_JWT_AUTHENTICATED")
     *
     * @return Response
     */
    public function fetchTmp(AttendeesRepository $attendeesRepository)
    {
        return $attendeesRepository->findAll();
    }

    /**
     * Fetch attendees
     *
     * @Rest\Get("/participants/fetch", name="api_participants_fetch")
     * @IsGranted("ROLE_JWT_AUTHENTICATED")
     *
     * @return Response
     */
    public function fetch(AttendeesRepository $attendeesRepository)
    {
        return $attendeesRepository->findAll();
    }

    /**
     * Set hasBeenScanned flag
     *
     * @Rest\Post("/participant/{id}/setHasBeenScanned", name="api_participant_setHasBeenScanned")
     * @IsGranted("ROLE_JWT_AUTHENTICATED")
     *
     * @return Response
     */
    public function setHasBeenScanned(EntityManagerInterface $entityManager, Request $request, Attendees $attendees)
    {
        $hasBeenScanned = $request->request->getBoolean('hasBeenScanned');
        $attendees->setHasBeenScanned($hasBeenScanned);
        $entityManager->persist($attendees);
        $entityManager->flush();
        return $attendees;
    }

    /**
     * Fetch attendees
     *
     * @Rest\Get("/attendees/validate/{token}", name="api_attendees_validate")
     *
     * Responses:
     *    {"status":404,"message": "NOT FOUND"}
     *    {"status":226,"message": "IM_USED"}
     *    {"status":202,"message": "ACCEPTED"}
     *
     * @return Response
     */
    public function validateTmp(AttendeesRepository $attendeesRepository, int $token)
    {
      $attendees = $attendeesRepository->findOneByToken($token);
      if ($attendees) {
        $hasBeenScannedAmount = $attendees->getHasBeenScannedAmount();
        $companions = 0;
        $companion1 = $attendees->getCompanion1();
        $companion2 = $attendees->getCompanion2();
        $companion3 = $attendees->getCompanion3();
        $companion4 = $attendees->getCompanion4();

        if (!empty($companion1)){
          $companions++;
        }
        if (!empty($companion2)){
          $companions++;
        }
        if (!empty($companion3)){
          $companions++;
        }
        if (!empty($companion4)){
          $companions++;
        }

        if (++$hasBeenScannedAmount > 1 + $companions) {
          return new Response(Response::$statusTexts[226], Response::HTTP_IM_USED);
        } else {
          $attendees->setHasBeenScanned(true);
          $attendees->setHasBeenScannedAmount($hasBeenScannedAmount);

          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($attendees);
          $entityManager->flush();
          return new Response(Response::$statusTexts[202], Response::HTTP_ACCEPTED);
        }
      } else {
        return new Response(Response::$statusTexts[404], Response::HTTP_NOT_FOUND);
      }
    }

    /**
     * Validate participant
     *
     * @Rest\Get("/participant/validate/{token}", name="api_participant_validate")
     *
     * Responses:
     *    {"status":404,"message": "NOT FOUND"}
     *    {"status":226,"message": "IM_USED"}
     *    {"status":202,"message": "ACCEPTED"}
     *
     * @return Response
     */
    public function validate(AttendeesRepository $attendeesRepository, int $token)
    {
      $attendees = $attendeesRepository->findOneByToken($token);
      if ($attendees) {
        $hasBeenScannedAmount = $attendees->getHasBeenScannedAmount();
        $companions = 0;
        $companion1 = $attendees->getCompanion1();
        $companion2 = $attendees->getCompanion2();
        $companion3 = $attendees->getCompanion3();
        $companion4 = $attendees->getCompanion4();

        if (!empty($companion1)){
          $companions++;
        }
        if (!empty($companion2)){
          $companions++;
        }
        if (!empty($companion3)){
          $companions++;
        }
        if (!empty($companion4)){
          $companions++;
        }

        if (++$hasBeenScannedAmount > 1 + $companions) {
          return new Response(Response::$statusTexts[226], Response::HTTP_IM_USED);
        } else {
          $attendees->setHasBeenScanned(true);
          $attendees->setHasBeenScannedAmount($hasBeenScannedAmount);

          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($attendees);
          $entityManager->flush();
          return new Response(Response::$statusTexts[202], Response::HTTP_ACCEPTED);
        }
      } else {
        return new Response(Response::$statusTexts[404], Response::HTTP_NOT_FOUND);
      }
    }

    /**
     * Deploy attendees
     *
     * @Rest\Post("/participant/create", name="api_attendees_create")
     * @IsGranted("ROLE_JWT_AUTHENTICATED")
     *
     * @return Response
     */
    public function create(
      Request $request,
      ValidatorInterface $validator,
      EntityManagerInterface $entityManager,
      AttendeesRepository $attendeesRepository,
      SettingsRepository $settingsRepository,
      MailerInterface $mailer
    ) {
        try {
            $settings = $settingsRepository->getFirst();
            if (empty($settings)) {
              $error = [
                "key" => "settings.no.settings.found",
                "message" => $this->translator->trans("settings.no.settings.found"),
                "type" => "error"
              ];
              return new JsonResponse([$error], Response::HTTP_BAD_REQUEST);
            }
            $eventMaximumAmount = $settings->getEventMaximumAmount();
            $eventDate = $settings->getEventDate();
            $eventDateStr = (new \IntlDateFormatter($request->getLocale(), \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE))->format($eventDate);
            $eventTime1 = $settings->getEventTime1();
            $eventTime2 = $settings->getEventTime2();
            $eventTime1Str = (new \IntlDateFormatter($request->getLocale(), \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT))->format($eventTime1);
            if ($eventTime2) {
              $eventTime2Str = (new \IntlDateFormatter($request->getLocale(), \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT))->format($eventTime2);
            }
            $eventTopic = $settings->getEventTopic();
            $eventLocation = $settings->getEventLocation();
            $eventEmailSubject = $settings->getEventEmailSubject();
            $eventEmailTemplate = $settings->getEventEmailTemplate();

            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $token = $this->randomStringGenerator->generate(5);
            $mobile = $request->request->get('mobile');
            $companions = 0;
            $companion1 = $request->request->get('companion1');
            $companion2 = $request->request->get('companion2');
            $companion3 = $request->request->get('companion3');
            $companion4 = $request->request->get('companion4');

            if (!empty($companion1)){
              $companions++;
            }
            if (!empty($companion2)){
              $companions++;
            }
            if (!empty($companion3)){
              $companions++;
            }
            if (!empty($companion4)){
              $companions++;
            }

            $count = $attendeesRepository->countAttendees();
            if ($count + 1 + $companions > $eventMaximumAmount){
              $error = [
                  "key" => "attendees.max.attendees.reached",
                  "message" => $this->translator->trans("attendees.max.attendees.reached"),
                  "type" => "error"
              ];
              return new JsonResponse([$error], Response::HTTP_BAD_REQUEST);
            }

            $attendees = new Attendees();
            $attendees->setName($name);
            $attendees->setEmail($email);
            $attendees->setToken($token);
            $attendees->setMobile($mobile);
            $attendees->setCompanion1($companion1);
            $attendees->setCompanion2($companion2);
            $attendees->setCompanion3($companion3);
            $attendees->setCompanion4($companion4);
            $attendees->setHasBeenScanned(false);

            $halfAmountReached = function () use ($count, $companions, $eventMaximumAmount) {
              return $count + 1 + $companions > $eventMaximumAmount / 2;
            };

            $attendeesEmailSubject = function ($eventTimeStr) use ($eventEmailSubject, $eventTopic, $eventDateStr, $eventLocation, $request) {
              $pattern = array();
              $pattern[0] = '/{{\s*eventTopic\s*}}/';
              $pattern[1] = '/{{\s*eventTime\s*}}/';
              $pattern[2] = '/{{\s*eventDate\s*}}/';
              $pattern[3] = '/{{\s*eventLocation\s*}}/';

              $replacement = array();
              $replacement[0] = $eventTopic;
              $replacement[1] = $eventTimeStr;
              $replacement[2] = $eventDateStr;
              $replacement[3] = $eventLocation;
              return preg_replace($pattern, $replacement,$eventEmailSubject);
            };

            $attendeesEmailTemplate = function ($eventTimeStr) use ($eventEmailTemplate, $eventTopic, $eventDateStr, $eventLocation, $request, $name) {
              $pattern = array();
              $pattern[0] = '/{{\s*eventTopic\s*}}/';
              $pattern[1] = '/{{\s*eventTime\s*}}/';
              $pattern[2] = '/{{\s*eventDate\s*}}/';
              $pattern[3] = '/{{\s*eventLocation\s*}}/';
              $pattern[4] = '/{{\s*name\s*}}/';

              $replacement = array();
              $replacement[0] = $eventTopic;
              $replacement[1] = $eventTimeStr;
              $replacement[2] = $eventDateStr;
              $replacement[3] = $eventLocation;
              $replacement[4] = $name;
              return preg_replace($pattern, $replacement,$eventEmailTemplate);
            };

            $errors = [];
            $constraintValidator = $validator->validate($attendees, null, ['create']);
            if (count($constraintValidator) > 0) {
                foreach ($constraintValidator->getIterator() as $key => $error) {
                    $errors[$key] = [
                      "key" => $error->getMessageTemplate(),
                      "message" => $this->translator->trans($error->getMessage()),
                      "type" => "error"
                    ];
                }
                return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            $result = $this->customQrCodeBuilder
              ->data($token)
              ->labelText($token . " - " . ($eventTime2 && $halfAmountReached() && $eventTime2 !== $eventTime1 ? "$eventTime2Str Uhr" : "$eventTime1Str Uhr") . " - " . substr($name, 0, 20) . " - Anzahl: " . (1 + $companions))
              ->labelFont(new NotoSans(10))
              ->labelAlignment(new LabelAlignmentCenter())
              ->build();
            $newFileName = tempnam(sys_get_temp_dir(), 'imv-qrcode-'). '.png';
            $result->saveToFile($newFileName);

            try {
              $email = (new TemplatedEmail())
                   ->from('no-reply@imv-landau.de')
                   ->to(new Address($email))
                   ->subject($eventTime2 && $halfAmountReached() && $eventTime2 !== $eventTime1  ? $attendeesEmailSubject($eventTime2Str) : $attendeesEmailSubject($eventTime1Str))
                   ->embedFromPath($newFileName, 'QrCode')
                   ->html($eventTime2 && $halfAmountReached() && $eventTime2 !== $eventTime1  ? $attendeesEmailTemplate($eventTime2Str) : $attendeesEmailTemplate($eventTime1Str))
                   ->context(['name' => $name]);
                   // this header tells auto-repliers ("email holiday mode") to not
                   // reply to this message because it's an automated email
                   $email->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
                   $mailer->send($email);

            } catch (TransportExceptionInterface $e) {
              // some error prevented the email sending
              return new JsonResponse($e, Response::HTTP_BAD_REQUEST);
            }

            $entityManager->persist($attendees);
            $entityManager->flush();

            return new QrCodeResponse($result);

        } catch (\Throwable $e) {
            throw new ApiProblemException(new ApiProblem(500, $e->getMessage()));
        }
    }

    /**
     * Delete participant entry
     *
     * @Rest\Delete("/participant/delete/{id}", name="api_participant_delete")
     * @IsGranted("ROLE_JWT_AUTHENTICATED")
     *
     * @return Response
     */
    public function delete(Request $request, Attendees $attendees)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($attendees);
        $entityManager->flush();
    }

    // /**
    //  * Delete attendees entry
    //  *
    //  * @Rest\Get("/attendees/delete", name="api_attendees_delete_all")
    //  * @IsGranted("ROLE_JWT_AUTHENTICATED")
    //  *
    //  * @return Response
    //  */
    // public function deleteAll(AttendeesRepository $attendeesRepository)
    // {
    //   $attendeesRepository->truncate();
    // }
}
