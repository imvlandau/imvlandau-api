<?php

namespace App\MessageHandler;

use App\Message\ProvisionServer;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Process\Process;
use Psr\Log\LoggerInterface;

class ProvisionServerHandler extends MessageHandlerBase implements MessageHandlerInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ProvisionServer $message)
    {
        $variables = $message->getVariables();
        $workingDirectory = $message->getWorkingDirectory();

        $cmd = "ansible-playbook playbooks/aws_ec2_prov.yaml --extra-vars " . escapeshellarg($variables);
        $process = new Process($cmd);
        $process->setWorkingDirectory($workingDirectory);
        $process->setTimeout(null);
        $process->run();

        $this->logger->debug($process->getOutput());
        $this->logger->debug($process->getErrorOutput());

        if (!$process->isSuccessful()) {
            return self::MSG_REJECT;
        }

        return self::MSG_ACK;
    }
}
