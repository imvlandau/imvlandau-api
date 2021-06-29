<?php

namespace App\Controller;

use App\Entity\KeyPair;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Repository\KeyPairRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller to manage SSH key pairs
 *
 * @Route("/api/key/pair")
 */
class KeyPairController extends FOSRestController
{

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * Fetch key pairs
     *
     * @Rest\Get("/", name="api_key_pairs_fetch")
     *
     * @return Response
     */
    public function fetch(KeyPairRepository $keyPairRepository)
    {
        return $keyPairRepository->findAll();
    }

    /**
     * Create key pair
     *
     * @Rest\Post("/create", name="api_key_pair_create")
     *
     * @return Response
     */
    public function create(Request $request, KeyPairRepository $keyPairRepository, ValidatorInterface $validator)
    {
        $keyPair = new KeyPair();
        $data = $request->request->all();
        $name = trim($request->request->get('name'));
        $publicKey = $request->request->get('publicKey');
        $filename = "$name.pem";
        $delimeter = "-----END RSA PRIVATE KEY-----";

        $keyPair->setName($name);

        $errors = [];
        $constraintValidator = $validator->validate($keyPair, null, ['create']);
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

        if (empty($publicKey)) {
            // create a private key
            $keyPairContent = $this->generateKeyPair($name);
            $keyPairExploded = explode('-----END RSA PRIVATE KEY-----', $keyPairContent);
            $privateKey = $keyPairExploded[0] . $delimeter;
            $publicKey = trim($keyPairExploded[1]);

            // get fingerprint
            $fingerprintMd5Hash = $this->getFingerprint($publicKey);

            // save the newly generated public key
            $keyPair->setPublicKey($publicKey);
            $keyPair->setFingerprint($fingerprintMd5Hash);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($keyPair);
            $entityManager->flush();

            // return the private key as downloadable file
            return [
                "privateKey" => $privateKey,
                "keyPair" => [
                    "id" => $keyPair->getId(),
                    "name" => $keyPair->getName(),
                    "fingerprint" => $keyPair->getFingerprint(),
                ],
                "keyPairs" => $keyPairRepository->findAll(),
            ];
        } else {
            // save user provided public key
            $fingerprintMd5Hash = $this->getFingerprint($publicKey);
            $keyPair->setPublicKey($publicKey);
            $keyPair->setFingerprint($fingerprintMd5Hash);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($keyPair);
            $entityManager->flush();

            return new JsonResponse(['status' => 'ok'], Response::HTTP_CREATED);
        }
    }

    private function generateKeyPair($name)
    {
        $projectDir = $this->get('kernel')->getProjectDir();
        $scriptsDir = $projectDir . '/scripts';
        $cmd = ["sudo", "bash", "pmb-keypair.sh", $name];
        $process = new Process($cmd);
        $process->setWorkingDirectory($scriptsDir);
        $process->setTimeout(null);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ApiProblemException(new ApiProblem(500, $process->getErrorOutput()));
        }
        return $process->getErrorOutput();
    }

    private function getFingerprint($publicKey)
    {
        $projectDir = $this->get('kernel')->getProjectDir();
        $scriptsDir = $projectDir . '/scripts';
        $cmd = ["sudo", "bash", "pmb-fingerprint.sh", $publicKey];
        $process = new Process($cmd);
        $process->setWorkingDirectory($scriptsDir);
        $process->setTimeout(null);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ApiProblemException(new ApiProblem(500, $process->getErrorOutput()));
        }
        $fingerprint = $process->getOutput();
        $fingerprintSplitted = preg_split("/(MD5:|\s)/", $fingerprint, -1, PREG_SPLIT_NO_EMPTY);
        if (!isset($fingerprintSplitted[1])) {
            throw new ApiProblemException(new ApiProblem(500, $process->getErrorOutput()));
        }
        return $fingerprintSplitted[1];
    }
    /**
     * Delete key pair entry
     *
     * @Rest\Delete("/{id}", name="api_key_pair_delete")
     *
     * @return Response
     */
    public function delete(Request $request, KeyPair $keyPair)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($keyPair);
        $entityManager->flush();
    }
}
