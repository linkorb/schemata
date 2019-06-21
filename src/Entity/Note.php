<?php

namespace LinkORB\Schemata\Entity;

use DateTime;

class Note
{
    private $author;

    private $createdAt;

    private $message;

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     * @return Note
     */
    public function setAuthor($author): Note
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     * @return Note
     */
    public function setCreatedAt($createdAt): Note
    {
        $this->createdAt = DateTime::createFromFormat('Ymd', $createdAt);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     * @return Note
     */
    public function setMessage($message): Note
    {
        $this->message = $message;

        return $this;
    }
}
