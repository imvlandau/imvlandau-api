<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\ParticipantRepository;
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
 * Controller to manage participant
 *
 * @Route("/api")
 */
class ParticipantController extends FOSRestController
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
     * Fetch participant
     *
     * @Rest\Get("/participants/fetch", name="api_participants_fetch")
     * @IsGranted("ROLE_JWT_AUTHENTICATED")
     *
     * @return Response
     */
    public function fetch(ParticipantRepository $participantRepository)
    {
        return $participantRepository->findAll();
    }

    /**
     * Set hasBeenScanned flag
     *
     * @Rest\Post("/participant/{id}/setHasBeenScanned", name="api_participant_setHasBeenScanned")
     * @IsGranted("ROLE_JWT_AUTHENTICATED")
     *
     * @return Response
     */
    public function setHasBeenScanned(EntityManagerInterface $entityManager, Request $request, Participant $participant)
    {
        $hasBeenScanned = $request->request->getBoolean('hasBeenScanned');
        $participant->setHasBeenScanned($hasBeenScanned);
        $entityManager->persist($participant);
        $entityManager->flush();
        return $participant;
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
    public function validate(ParticipantRepository $participantRepository, int $token)
    {
      $participant = $participantRepository->findOneByToken($token);
      if ($participant) {
        $hasBeenScannedAmount = $participant->getHasBeenScannedAmount();
        $companions = 0;
        $companion1 = $participant->getCompanion1();
        $companion2 = $participant->getCompanion2();
        $companion3 = $participant->getCompanion3();
        $companion4 = $participant->getCompanion4();

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
          $participant->setHasBeenScanned(true);
          $participant->setHasBeenScannedAmount($hasBeenScannedAmount);

          $entityManager = $this->getDoctrine()->getManager();
          $entityManager->persist($participant);
          $entityManager->flush();
          return new Response(Response::$statusTexts[202], Response::HTTP_ACCEPTED);
        }
      } else {
        return new Response(Response::$statusTexts[404], Response::HTTP_NOT_FOUND);
      }
    }

    /**
     * Register participant
     *
     * @Rest\Post("/participant/create", name="api_participant_create")
     *
     * @return Response
     */
    public function create(
      Request $request,
      ValidatorInterface $validator,
      EntityManagerInterface $entityManager,
      ParticipantRepository $participantRepository,
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

            $count = $participantRepository->countParticipants();
            if ($count + 1 + $companions > $eventMaximumAmount){
              $error = [
                  "key" => "participant.max.participant.reached",
                  "message" => $this->translator->trans("participant.max.participant.reached"),
                  "type" => "error"
              ];
              return new JsonResponse([$error], Response::HTTP_BAD_REQUEST);
            }

            $participant = new Participant();
            $participant->setName($name);
            $participant->setEmail($email);
            $participant->setToken($token);
            $participant->setMobile($mobile);
            $participant->setCompanion1($companion1);
            $participant->setCompanion2($companion2);
            $participant->setCompanion3($companion3);
            $participant->setCompanion4($companion4);
            $participant->setHasBeenScanned(false);

            $halfAmountReached = function () use ($count, $companions, $eventMaximumAmount) {
              return $count + 1 + $companions > $eventMaximumAmount / 2;
            };

            $participantEmailSubject = function ($eventTimeStr) use ($eventEmailSubject, $eventTopic, $eventDateStr, $eventLocation, $request) {
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

            $participantEmailTemplate = function ($eventTimeStr) use ($eventEmailTemplate, $eventTopic, $eventDateStr, $eventLocation, $request, $name) {
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
            $constraintValidator = $validator->validate($participant, null, ['create']);
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
                   ->subject($eventTime2 && $halfAmountReached() && $eventTime2 !== $eventTime1  ? $participantEmailSubject($eventTime2Str) : $participantEmailSubject($eventTime1Str))
                   ->embedFromPath($newFileName, 'QrCode')
                   ->html($eventTime2 && $halfAmountReached() && $eventTime2 !== $eventTime1  ? $participantEmailTemplate($eventTime2Str) : $participantEmailTemplate($eventTime1Str))
                   ->context(['name' => $name]);
                   // this header tells auto-repliers ("email holiday mode") to not
                   // reply to this message because it's an automated email
                   $email->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
                   $mailer->send($email);

            } catch (TransportExceptionInterface $e) {
              // some error prevented the email sending
              return new JsonResponse($e, Response::HTTP_BAD_REQUEST);
            }

            $entityManager->persist($participant);
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
    public function delete(Request $request, Participant $participant)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($participant);
        $entityManager->flush();
    }
}
