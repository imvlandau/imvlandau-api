<?php
namespace App\Message;

class ProvisionServer
{
    /**
     * @var string
     */
    private $variables;

    public function __construct(string $variables = "", string $workingDirectory = "ansible")
    {
        $this->variables = $variables;
        $this->workingDirectory = $workingDirectory;
    }

    public function getVariables(): string
    {
        return $this->variables;
    }

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }
}
