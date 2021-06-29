<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* Entity for app and file system tree (including boxes)
*
* @ORM\Table(name="app")
* @ORM\Entity(repositoryClass="App\Repository\AppRepository")
* @ORM\HasLifecycleCallbacks
*/
class App extends EntityBase
{
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue
    */
    private $id;

    /**
    * @ORM\Column(type="string", length=32, nullable=false)
    */
    private $shortid;

    /**
    * @var string
    *
    * @ORM\Column(name="title", type="string", length=255)
    */
    private $title;

    /**
    * @ORM\Column(type="integer", nullable=true, options={"unsigned":true, "default":0})
    */
    private $version;

    public function __construct()
    {
        $this->version = 0;
    }

    /**
    * Get title
    *
    * @return string
    */
    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
    * Get id
    *
    * @return integer
    */
    public function getId(): int
    {
        return $this->id;
    }

    /**
    * Get shortid
    *
    * @return integer
    */
    public function getShortid(): string
    {
        return $this->shortid;
    }

    /**
    * Set shortid
    *
    * @param integer $shortid
    *
    * @return App
    */
    public function setShortid(string $shortid): self
    {
        $this->shortid = $shortid;

        return $this;
    }

    /**
    * Get title
    *
    * @return string
    */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
    * Set title
    *
    * @param string $title
    *
    * @return App
    */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
    * Get version number of app
    *
    * @return integer
    */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
    * Set version number of app
    *
    * @param integer $version
    *
    * @return App
    */
    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
    * Clones current node
    *
    * @return App
    */
    public function clone(): App
    {
        $copy = new App();
        $copy->setShortid($this->getShortid());
        $copy->setTitle($this->getTitle());
        $copy->setContentVersion($this->getContentVersion());
        return $copy;
    }
}
