<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
* Class EntityBase
*
* PHP version 7.1
*
* LICENSE: MIT
*
* @package    AppBundle\Mapping
* @author     Lelle - Daniele Rostellato <lelle.daniele@gmail.com>
* @license    MIT
* @version    1.0.0
* @since      File available since Release 1.0.0
* @ORM\HasLifecycleCallbacks
*/
class EntityBase implements EntityBaseInterface
{
    /**
    * @var DateTime $created
    *
    * @ORM\Column(name="created_at", type="datetime", nullable=false)
    */
    protected $createdAt;

    /**
    * @var DateTime $updated
    *
    * @ORM\Column(name="updated_at", type="datetime", nullable=false)
    */
    protected $updatedAt;

    /**
    * @ORM\PrePersist
    * @ORM\PreUpdate
    */
    public function updatedTimestamps(): void
    {
        $dateTimeNow = new DateTime('now');

        $this->setUpdatedAt($dateTimeNow);

        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt($dateTimeNow);
        }
    }

    /**
    * {@inheritdoc}
    */
    public function getCreatedAt() :?DateTime
    {
        return $this->createdAt;
    }

    /**
    * {@inheritdoc}
    */
    public function setCreatedAt(DateTime $createdAt): EntityBaseInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
    * {@inheritdoc}
    */
    public function getUpdatedAt() :?DateTime
    {
        return $this->updatedAt;
    }

    /**
    * {@inheritdoc}
    */
    public function setUpdatedAt(DateTime $updatedAt): EntityBaseInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
