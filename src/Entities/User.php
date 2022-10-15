<?php

namespace saschanockel\PhpMfaApp\Entities;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="users",indexes={@Index(name="username_idx", fields={"username"})})
 */
class User
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @Column(type="string", unique=true)
     */
    private $username;

    /**
     * @Column(type="string")
     */
    private $password;

    /**
     * @Column(type="text", name="otp_secret", nullable=true)
     */
    private $otpSecret;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return null|string
     */
    public function getOtpSecret(): null|string
    {
        return $this->otpSecret;
    }

    /**
     * @param string $otpSecret
     */
    public function setOtpSecret(string $otpSecret): void
    {
        $this->otpSecret = $otpSecret;
    }
}