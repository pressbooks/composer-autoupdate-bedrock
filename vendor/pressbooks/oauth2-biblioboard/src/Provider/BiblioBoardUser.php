<?php

namespace League\OAuth2\Client\Provider;

class BiblioBoardUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Get unique ID.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->response['id'];
    }

    /**
     * Get email address.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['emailAddress'];
    }

    /**
     * Get username.
     *
     * @return string|null
     */
    public function getUserName()
    {
        return $this->response['username'];
    }

    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
