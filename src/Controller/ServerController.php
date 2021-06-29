<?php

namespace App\Controller;

use App\Entity\Server;
use App\Exception\ApiProblem;
use App\Exception\ApiProblemException;
use App\Message\ProvisionServer;
use App\Repository\KeyPairRepository;
use App\Repository\ServerRepository;
use App\Repository\StartupScriptRepository;
use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;
use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
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
    public function deploy(Request $request, KeyPairRepository $keyPairRepository, ServerRepository $serverRepository, ValidatorInterface $validator)
    {
        $pmb_ec2_assign_public_ip = $request->request->get('pmb_ec2_assign_public_ip', true);
        $pmb_ec2_count = $request->request->get('pmb_ec2_count', 1);
        $pmb_ec2_ebs_optimized = $request->request->get('pmb_ec2_ebs_optimized', false);
        $pmb_ec2_security_groups = $request->request->get('pmb_ec2_security_groups', ['default']); // All traffic for In-/Outbound traffic
        $pmb_ec2_instance_type = $request->request->get('pmb_ec2_instance_type', 't2.micro'); // Linux, Windows, MacOSX, FreeBSD
        $pmb_ec2_instance_profile_name = $request->request->get('pmb_ec2_instance_profile_name', '');
        $pmb_ec2_user_data = $request->request->get('pmb_ec2_user_data');
        $pmb_ec2_ami_id = $request->request->get('pmb_ec2_ami_id', 'ami-059d836af932792c3'); // Ubuntu 18.04 - us-east-2 (Ohio)
        $pmb_ec2_monitoring = $request->request->get('pmb_ec2_monitoring', false);
        $pmb_ec2_region = $request->request->get('pmb_ec2_region', 'us-east-2'); // us-east-2 == Ohio
        $pmb_ec2_vpc_subnet_id = $request->request->get('pmb_ec2_vpc_subnet_id', 'subnet-f0f6ff98');
        $pmb_ec2_customer_id = $request->request->get('pmb_ec2_customer_id', 'unknown');
        $pmb_ec2_state = $request->request->get('pmb_ec2_state', 'present');
        $pmb_ec2_volume_size = $request->request->get('pmb_ec2_volume_size', 8);
        $pmb_ec2_delete_on_termination = $request->request->get('pmb_ec2_delete_on_termination', true);
        $pmb_ec2_security_group_name = $request->request->get('pmb_ec2_security_group_name', 'pmb-default-security-group');
        $pmb_ec2_instance_tags = [
            "pmb_ec2_customer_id" => $pmb_ec2_customer_id,
            "pmb_ec2_instance_type" => $pmb_ec2_instance_type,
            "Name" => "$pmb_ec2_customer_id::$pmb_ec2_region::$pmb_ec2_instance_type",
        ];
        $pmb_hostname = $request->request->get('pmb_hostname', null);
        $pmb_remote_user = $request->request->get('pmb_remote_user', 'ubuntu');
        $pmb_label = $request->request->get('pmb_label');
        $pmb_ec2_private_ip = $request->request->get('pmb_ec2_private_ip');

        $variables = [];
        $pmb_ec2_keypair_id = $request->request->get('pmb_ec2_keypair_id');
        if (!empty($pmb_ec2_keypair_id)) {
            $keyPair = $keyPairRepository->findOneById($pmb_ec2_keypair_id);
            $pmb_ec2_key_name = $keyPair->getName();
        }
        $pmb_ec2_software_keys = $request->request->get('pmb_ec2_software_keys', []);

        $variables = [
            "pmb_ec2_assign_public_ip" => $pmb_ec2_assign_public_ip,
            "pmb_ec2_count" => $pmb_ec2_count,
            "pmb_ec2_ebs_optimized" => $pmb_ec2_ebs_optimized,
            "pmb_ec2_security_groups" => $pmb_ec2_security_groups,
            "pmb_ec2_instance_type" => $pmb_ec2_instance_type,
            "pmb_ec2_instance_profile_name" => $pmb_ec2_instance_profile_name,
            "pmb_ec2_user_data" => $pmb_ec2_user_data,
            "pmb_ec2_ami_id" => $pmb_ec2_ami_id,
            "pmb_ec2_monitoring" => $pmb_ec2_monitoring,
            "pmb_ec2_region" => $pmb_ec2_region,
            "pmb_ec2_vpc_subnet_id" => $pmb_ec2_vpc_subnet_id,
            "pmb_ec2_customer_id" => $pmb_ec2_customer_id,
            "pmb_ec2_state" => $pmb_ec2_state,
            "pmb_ec2_volume_size" => $pmb_ec2_volume_size,
            "pmb_ec2_delete_on_termination" => $pmb_ec2_delete_on_termination,
            "pmb_ec2_software_keys" => $pmb_ec2_software_keys,
            "pmb_ec2_security_group_name" => $pmb_ec2_security_group_name,
            "pmb_ec2_instance_tags" => $pmb_ec2_instance_tags,
            "pmb_ec2_private_ip" => $pmb_ec2_private_ip,
        ];

        if (isset($pmb_ec2_key_name)) {
            $variables["pmb_ec2_key_name"] = $pmb_ec2_key_name;
        }

        if (isset($pmb_hostname)) {
            $variables["pmb_hostname"] = $pmb_hostname;
        }

        if (isset($pmb_remote_user)) {
            $variables["pmb_remote_user"] = $pmb_remote_user;
        }

        $variablesAsJsonString = json_encode($variables);

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

            // provision server at cloud provider
            $this->dispatchMessage(new ProvisionServer($variablesAsJsonString));

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

    /**
     * Provision server
     *
     * @Rest\Post("/server/provision", name="api_server_provision")
     *
     * @return Response
     */
    public function provision(
        Request $request,
        Ec2Client $client,
        KeyPairRepository $keyPairRepository,
        StartupScriptRepository $startupScriptRepository
    ) {
        $region = $request->request->get('pmb_ec2_region', 'us-east-2'); // us-east-2 == Ohio
        $ami = $request->request->get('pmb_ec2_ami_id', 'ami-059d836af932792c3'); // Ubuntu 18.04 - us-east-2 (Ohio)
        $instanceType = $request->request->get('pmb_ec2_instance_type', 't2.micro'); // Linux, Windows, MacOSX, FreeBSD
        $securityGroup = $request->request->get('pmb_ec2_security_group', 'pmb-default-security-group'); // All traffic for In-/Outbound traffic
        $serverQuantity = $request->request->get('serverQuantity', 1);
        $subnetId = $request->request->get('subnetId', 'subnet-f0f6ff98');
        $volumeSize = $request->request->get('volumeSize', 8);
        $startupScriptId = $request->request->get('pmb_ec2_startup_script_id');
        if (!empty($startupScriptId)) {
            $startupScript = $startupScriptRepository->findOneById($startupScriptId);
            $startupScriptContent = $startupScript->getContent();
        }

        $variables = [
            'ImageId' => $ami,
            'InstanceType' => $instanceType,
            'MaxCount' => 1,
            'MinCount' => 1,
            // 'Ipv6AddressCount' => 1,
            // 'SubnetId' => "subnet-f0f6ff98",
            'SecurityGroups' => [
                $securityGroup,
            ],
            'UserData' => base64_encode($startupScriptContent),
            'TagSpecifications' => [
                [
                    'ResourceType' => 'instance',
                    'Tags' => [
                        [
                            'Key' => 'Playmobox',
                            'Value' => 'Instance',
                        ],
                        [
                            'Key' => 'Name',
                            'Value' => "$region::$instanceType",
                        ],
                    ],
                ],
            ],
        ];

        $keyPairId = $request->request->get('pmb_ec2_keypair_id');
        if (!empty($keyPairId)) {
            $keyPair = $keyPairRepository->findOneById($keyPairId);
            $keyPairName = $keyPair->getName();
            $variables['KeyName'] = $keyPairName;
        }

        try {
            $promise = $client->runInstancesAsync($variables);
            $results = $promise->wait();

            $client->waitUntil('InstanceRunning', ['InstanceIds' => $results->search('Instances[*].InstanceId')]);

            return $client->describeInstances([
                'InstanceIds' => $results->search('Instances[*].InstanceId'),
            ]);
        } catch (CredentialsException $e) {
            throw new ApiProblemException(new ApiProblem(400, $e->getMessage()));
        } catch (Ec2Exception $e) {
            throw new ApiProblemException(new ApiProblem(400, $e->getMessage()));
        } catch (AwsException $e) {
            throw new ApiProblemException(new ApiProblem(400, $e->getMessage()));
        } catch (\RuntimeException $e) {
            throw new ApiProblemException(new ApiProblem(400, $e->getMessage()));
        }
    }
}
