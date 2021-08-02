<?php

namespace App\Controller;

use App\Entity\Attendees;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\AttendeesRepository;
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
        $randomStringGeneratorAlphabet = null,
        $maxAttendeesCount = 0,
        $attendeesEmailSubject1 = null,
        $attendeesEmailSubject2 = null,
        $attendeesEmailTemplatePath1 = null,
        $attendeesEmailTemplatePath2 = null
    ) {
        $this->translator = $translator;
        $this->customQrCodeBuilder = $customQrCodeBuilder;
        $this->randomStringGenerator = $randomStringGenerator;
        $this->randomStringGenerator->setAlphabet($randomStringGeneratorAlphabet);
        $this->maxAttendeesCount = $maxAttendeesCount;
        $this->attendeesEmailSubject1 = $attendeesEmailSubject1;
        $this->attendeesEmailSubject2 = $attendeesEmailSubject2;
        $this->attendeesEmailTemplatePath1 = $attendeesEmailTemplatePath1;
        $this->attendeesEmailTemplatePath2 = $attendeesEmailTemplatePath2;
    }

    /**
     * Fetch attendees
     *
     * @Rest\Get("/attendees/fetch", name="api_attendees_fetch")
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
     * @Rest\Post("/attendees/{id}/setHasBeenScanned", name="api_attendees_setHasBeenScanned")
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
        if ($attendees->getHasBeenScanned()) {
          return new Response(Response::$statusTexts[226], Response::HTTP_IM_USED);
        } else {
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

          if (++$hasBeenScannedAmount >= 1 + $companions){
            $attendees->setHasBeenScanned(true);
            $attendees->setHasBeenScannedAmount($hasBeenScannedAmount);
          } else {
            $attendees->setHasBeenScannedAmount($hasBeenScannedAmount);
          }

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
        if ($attendees->getHasBeenScanned()) {
          return new Response(Response::$statusTexts[226], Response::HTTP_IM_USED);
        } else {
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

          if (++$hasBeenScannedAmount >= 1 + $companions){
            $attendees->setHasBeenScanned(true);
            $attendees->setHasBeenScannedAmount($hasBeenScannedAmount);
          } else {
            $attendees->setHasBeenScannedAmount($hasBeenScannedAmount);
          }

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
     *
     * @return Response
     */
    public function create(
      Request $request,
      ValidatorInterface $validator,
      EntityManagerInterface $entityManager,
      AttendeesRepository $attendeesRepository,
      MailerInterface $mailer
    ) {
        try {
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
            if ($count + 1 + $companions > $this->maxAttendeesCount){
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
              ->labelText($token . " - " . ($count + 1 + $companions <= $this->maxAttendeesCount / 2 ? "13:45 Uhr" : "15:00 Uhr") . " - " . substr($name, 0, 20) . " - Anzahl: " . (1 + $companions))
              ->labelFont(new NotoSans(10))
              ->labelAlignment(new LabelAlignmentCenter())
              ->build();
            $newFileName = tempnam(sys_get_temp_dir(), 'imv-qrcode-'). '.png';
            $result->saveToFile($newFileName);

            try {
              $email = (new TemplatedEmail())
                   ->from('no-reply@imv-landau.de')
                   ->to(new Address($email))
                   ->subject(($count + 1 + $companions <= $this->maxAttendeesCount / 2) ? $this->attendeesEmailSubject1 : $this->attendeesEmailSubject2)
                   ->embedFromPath($newFileName, 'QrCode')
                   ->htmlTemplate(($count + 1 + $companions <= $this->maxAttendeesCount / 2) ? $this->attendeesEmailTemplatePath1 : $this->attendeesEmailTemplatePath2)
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
     * Delete attendees entry
     *
     * @Rest\Delete("/attendees/delete/{id}", name="api_attendees_delete")
     *
     * @return Response
     */
    public function delete(Request $request, Attendees $attendees)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($attendees);
        $entityManager->flush();
    }

    /**
     * Delete attendees entry
     *
     * @Rest\Get("/attendees/delete", name="api_attendees_delete_all")
     *
     * @return Response
     */
    public function deleteAll(Request $request)
    {
      $entityManager = $this->getDoctrine()->getManager();
      $connection = $entityManager->getConnection();
      $platform   = $connection->getDatabasePlatform();
      $connection->executeUpdate($platform->getTruncateTableSQL('attendees', true));
    }
}
