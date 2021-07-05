<?php

namespace App\Controller;

use App\Entity\Attendees;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\AttendeesRepository;
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
        BuilderInterface $customQrCodeBuilder
    ) {
        $this->translator = $translator;
        $this->customQrCodeBuilder = $customQrCodeBuilder;
        $this->randomStringGenerator = $randomStringGenerator;
        $this->randomStringGenerator->setAlphabet('2346789');
    }

    /**
     * Fetch attendees
     *
     * @Rest\Get("/attendees/fetch", name="api_attendees_fetch")
     *
     * @return Response
     */
    public function fetch(AttendeesRepository $attendeesRepository)
    {
        return $attendeesRepository->findAll();
    }

    /**
     * Fetch attendees
     *
     * @Rest\Get("/attendees/validate/{token}", name="api_attendees_validate")
     *
     * @return Response
     */
    public function validate(AttendeesRepository $attendeesRepository, Attendees $attendees)
    {
      if ($attendees->getHasBeenScanned()) {
        return new Response(Response::$statusTexts[226], Response::HTTP_IM_USED);
      } else {
        $attendees->setHasBeenScanned(true);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($attendees);
        $entityManager->flush();
        return new Response(Response::$statusTexts[202], Response::HTTP_ACCEPTED);
      }
    }

    /**
     * Deploy attendees
     *
     * @Rest\Post("/attendees/create", name="api_attendees_create")
     *
     * @return Response
     */
    public function create(
      Request $request,
      ValidatorInterface $validator,
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

            $attendees = new Attendees();
            $attendees->setName($name);
            $attendees->setEmail($email);
            $attendees->setToken($token);
            $attendees->setMobile($mobile);
            $attendees->setCompanion1($companion1);
            $attendees->setCompanion2($companion2);
            $attendees->setCompanion3($companion3);
            $attendees->setCompanion4($companion4);

            $errors = [];
            $constraintValidator = $validator->validate($attendees, null, ['create']);
            if (count($constraintValidator) > 0) {
                foreach ($constraintValidator->getIterator() as $error) {
                    $errors[$error->getMessageTemplate()] = $this->translator->trans($error->getMessage());
                }
                return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
            }

            $label = "";
            if (!empty($companions)){
              $label = "+ $companions";
            }
            $result = $this->customQrCodeBuilder
              ->data($token)
              ->labelText($token . " - " . substr($name, 0, 19) . " " . $label)
              ->labelFont(new NotoSans(13))
              ->labelAlignment(new LabelAlignmentCenter())
              ->build();
            $newFileName = tempnam(sys_get_temp_dir(), 'imv-qrcode-'). '.png';
            $result->saveToFile($newFileName);

            try {
              $email = (new TemplatedEmail())
                   ->from('no-reply@imv-landau.de')
                   ->to(new Address($email))
                   ->subject('QR-Code - Eid al-Adha - 19.07.2021 - Sporthalle IGS Landau')
                   ->embedFromPath($newFileName, 'QrCode')
                   ->htmlTemplate('emails/attendees.html.twig')
                   ->context(['name' => $name]);
                   // this header tells auto-repliers ("email holiday mode") to not
                   // reply to this message because it's an automated email
                   $email->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
                   $mailer->send($email);

            } catch (TransportExceptionInterface $e) {
              // some error prevented the email sending
              return new JsonResponse($e, Response::HTTP_BAD_REQUEST);
            }

            $entityManager = $this->getDoctrine()->getManager();
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
