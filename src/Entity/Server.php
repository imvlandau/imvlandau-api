<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for servers
 *
 * @ORM\Table(name="server")
 * @ORM\Entity(repositoryClass="App\Repository\ServerRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Server extends EntityBase
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $name;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

}
