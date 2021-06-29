<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for ssh key pairs
 *
 * @ORM\Table(name="key_pair")
 * @ORM\Entity(repositoryClass="App\Repository\KeyPairRepository")
 * @ORM\HasLifecycleCallbacks
 */
class KeyPair extends EntityBase
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

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $fingerprint;

    /**
     * @ORM\Column(type="blob", nullable=false)
     */
    private $publicKey;

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

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(string $fingerprint): self
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    public function setPublicKey($publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
