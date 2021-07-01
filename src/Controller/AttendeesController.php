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
    public function create(Request $request,ValidatorInterface $validator) {
        try {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $mobile = $request->request->get('mobile');
            $companions = 0;
            $companion1 = $request->request->get('companion1');
            $companion2 = $request->request->get('companion2');
            $companion3 = $request->request->get('companion3');
            $companion4 = $request->request->get('companion4');

return $this->randomStringGenerator->generate(5);

$attendeesRepository = $this->getDoctrine()->getRepository(Attendees::class);
$ret = $attendeesRepository->findOneByEmailHash("989abcfa23e291f53caaaf059c81a8e0");
return $ret;
echo "<pre>" . print_r($ret, 1) . "</pre>";
exit;
// return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_CREATED));

// return $attendeesRepository->findAll();

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
            $attendees->setMobile($mobile);
            $attendees->setCompanion1($companion1);
            $attendees->setCompanion2($companion2);
            $attendees->setCompanion3($companion3);
            $attendees->setCompanion4($companion4);

//             $errors = [];
//             $constraintValidator = $validator->validate($attendees, null, ['create']);
//             if (count($constraintValidator) > 0) {
//                 foreach ($constraintValidator->getIterator() as $error) {
//                     $errors[] = $this->translator->trans($error->getMessage());
//                 }
//                 return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
//             }
//
//             // save user provided public key
//             $entityManager = $this->getDoctrine()->getManager();
//             $entityManager->persist($attendees);
//             $entityManager->flush();
//
// $label = "";
// if (!empty($companions)){
//   $label = "+ $companions";
// }
// $result = $this->customQrCodeBuilder
//   ->data("$name $label")
//   ->labelText("$name $label")
//   ->labelFont(new NotoSans(13))
//   ->labelAlignment(new LabelAlignmentCenter())
//   ->build();
// $response = new QrCodeResponse($result);
// return $response;

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
