<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for settings
 *
 * @ORM\Table(name="settings")
 * @ORM\Entity(repositoryClass="App\Repository\SettingsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Settings extends EntityBase
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="eventMaximumAmount", type="integer", nullable=false)
     */
    private $eventMaximumAmount;

    /**
     * @ORM\Column(name="eventDate", type="date", nullable=false)
     */
    protected $eventDate;

    /**
     * @ORM\Column(name="eventTime1", type="time", nullable=false)
     */
    protected $eventTime1;

    /**
     * @ORM\Column(name="eventTime2", type="time", nullable=true)
     */
    protected $eventTime2;

    /**
     * @ORM\Column(name="eventTopic", type="string", length=50, nullable=false)
     */
    private $eventTopic;

    /**
     * @ORM\Column(name="eventLocation", type="string", length=100, nullable=false)
     */
    private $eventLocation;

    /**
     * @ORM\Column(name="eventEmailSubject", type="string", length=150, nullable=false)
     */
    private $eventEmailSubject;

    /**
     * @ORM\Column(name="eventEmailTemplate", type="text", nullable=false)
     */
    private $eventEmailTemplate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventMaximumAmount(): ?int
    {
        return $this->eventMaximumAmount;
    }

    public function setEventMaximumAmount(int $eventMaximumAmount): self
    {
        $this->eventMaximumAmount = $eventMaximumAmount;

        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getEventTime1(): ?\DateTimeInterface
    {
        return $this->eventTime1;
    }

    public function setEventTime1(\DateTimeInterface $eventTime1): self
    {
        $this->eventTime1 = $eventTime1;

        return $this;
    }

    public function getEventTime2(): ?\DateTimeInterface
    {
        return $this->eventTime2;
    }

    public function setEventTime2(?\DateTimeInterface $eventTime2): self
    {
        $this->eventTime2 = $eventTime2;

        return $this;
    }

    public function getEventTopic(): ?string
    {
        return $this->eventTopic;
    }

    public function setEventTopic(string $eventTopic): self
    {
        $this->eventTopic = $eventTopic;

        return $this;
    }

    public function getEventLocation(): ?string
    {
        return $this->eventLocation;
    }

    public function setEventLocation(string $eventLocation): self
    {
        $this->eventLocation = $eventLocation;

        return $this;
    }

    public function getEventEmailSubject(): ?string
    {
        return $this->eventEmailSubject;
    }

    public function setEventEmailSubject(string $eventEmailSubject): self
    {
        $this->eventEmailSubject = $eventEmailSubject;

        return $this;
    }

    public function getEventEmailTemplate(): ?string
    {
        return $this->eventEmailTemplate;
    }

    public function setEventEmailTemplate(string $eventEmailTemplate): self
    {
        $this->eventEmailTemplate = $eventEmailTemplate;

        return $this;
    }

}
