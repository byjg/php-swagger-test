<?php

namespace Tests\Classes;

class Pet
{
    protected int $id;
    protected ?object $category;
    protected string $name;
    protected array $photoUrls;
    protected array $tags;
    protected string $status;

    /**
     * Pet constructor.
     * @param int $id
     * @param object|null $category
     * @param string $name
     * @param array $photoUrls
     * @param array $tags
     * @param string $status
     */
    public function __construct(int $id = 0, ?object $category = null, string $name = "", array $photoUrls = [], array $tags = [], string $status = "")
    {
        $this->id = $id;
        $this->category = $category;
        $this->name = $name;
        $this->photoUrls = $photoUrls;
        $this->tags = $tags;
        $this->status = $status;
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return object|null
     */
    public function getCategory(): ?object
    {
        return $this->category;
    }

    /**
     * @param object|null $category
     */
    public function setCategory(?object $category): void
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getPhotoUrls(): array
    {
        return $this->photoUrls;
    }

    /**
     * @param array $photoUrls
     */
    public function setPhotoUrls(array $photoUrls): void
    {
        $this->photoUrls = $photoUrls;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}