<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entity for participant
 *
 * @ORM\Table(name="participant")
 * @ORM\Entity(repositoryClass="App\Repository\ParticipantRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Participant extends EntityBase
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
     * @ORM\Column(type="string", length=50, nullable=false, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(name="token", type="integer", nullable=false, unique=true)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $mobile;

    /**
     * @ORM\Column(name="companion_1", type="string", length=50, nullable=true)
     */
    private $companion1;

    /**
     * @ORM\Column(name="companion_2", type="string", length=50, nullable=true)
     */
    private $companion2;

    /**
     * @ORM\Column(name="companion_3", type="string", length=50, nullable=true)
     */
    private $companion3;

    /**
     * @ORM\Column(name="companion_4", type="string", length=50, nullable=true)
     */
    private $companion4;

    /**
     * @ORM\Column(name="has_been_scanned", type="boolean", options={"default":"0"})
     */
    private $hasBeenScanned;

    /**
     * @ORM\Column(name="has_been_scanned_amount", type="integer", options={"default":0})
     */
    private $hasBeenScannedAmount;

    public function __construct()
    {
        $this->hasBeenScannedAmount = 0;
    }

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getToken(): ?int
    {
        return $this->token;
    }

    public function setToken(int $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getCompanion1(): ?string
    {
        return $this->companion1;
    }

    public function setCompanion1(string $companion1): self
    {
        $this->companion1 = $companion1;

        return $this;
    }

    public function getCompanion2(): ?string
    {
        return $this->companion2;
    }

    public function setCompanion2(string $companion2): self
    {
        $this->companion2 = $companion2;

        return $this;
    }

    public function getCompanion3(): ?string
    {
        return $this->companion3;
    }

    public function setCompanion3(string $companion3): self
    {
        $this->companion3 = $companion3;

        return $this;
    }

    public function getCompanion4(): ?string
    {
        return $this->companion4;
    }

    public function setCompanion4(string $companion4): self
    {
        $this->companion4 = $companion4;

        return $this;
    }

    public function getHasBeenScanned(): ?bool
    {
        return $this->hasBeenScanned;
    }

    public function setHasBeenScanned(bool $hasBeenScanned): self
    {
        $this->hasBeenScanned = $hasBeenScanned;

        return $this;
    }

    public function getHasBeenScannedAmount(): ?int
    {
        return $this->hasBeenScannedAmount;
    }

    public function setHasBeenScannedAmount(int $hasBeenScannedAmount): self
    {
        $this->hasBeenScannedAmount = $hasBeenScannedAmount;

        return $this;
    }

}
