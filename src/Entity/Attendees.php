<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for attendees
 *
 * @ORM\Table(name="attendees")
 * @ORM\Entity(repositoryClass="App\Repository\AttendeesRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Attendees extends EntityBase
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
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    private $mobile;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true, "default":0})
     */
    private $amountCompanions;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $companion_1;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $companion_2;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $companion_3;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $companion_4;


    public function __construct()
    {
        $this->amountCompanions = 0;
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

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getAmountCompanions(): ?integer
    {
        return $this->amountCompanions;
    }

    public function setAmountCompanions(integer $amountCompanions): self
    {
        $this->amountCompanions = $amountCompanions;

        return $this;
    }

    public function getCompanion_1(): ?string
    {
        return $this->companion_1;
    }

    public function setCompanion_1(string $companion_1): self
    {
        $this->companion_1 = $companion_1;

        return $this;
    }

    public function getCompanion_2(): ?string
    {
        return $this->companion_2;
    }

    public function setCompanion_2(string $companion_2): self
    {
        $this->companion_2 = $companion_2;

        return $this;
    }

    public function getCompanion_3(): ?string
    {
        return $this->companion_3;
    }

    public function setCompanion_3(string $companion_3): self
    {
        $this->companion_3 = $companion_3;

        return $this;
    }

    public function getCompanion_4(): ?string
    {
        return $this->companion_4;
    }

    public function setCompanion_4(string $companion_4): self
    {
        $this->companion_4 = $companion_4;

        return $this;
    }

}
