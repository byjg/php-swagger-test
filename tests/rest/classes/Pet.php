<?php

namespace Tests;

class Pet
{
    protected $id;
    protected $category;
    protected $name;
    protected $photoUrls;
    protected $tags;
    protected $status;

    /**
     * Pet constructor.
     * @param $id
     * @param $category
     * @param $name
     * @param $photoUrls
     * @param $tags
     * @param $status
     */
    public function __construct($id = "", $category = "", $name = "", $photoUrls = "", $tags = "", $status = "")
    {
        $this->id = $id;
        $this->category = $category;
        $this->name = $name;
        $this->photoUrls = $photoUrls;
        $this->tags = $tags;
        $this->status = $status;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPhotoUrls()
    {
        return $this->photoUrls;
    }

    /**
     * @param mixed $photoUrls
     */
    public function setPhotoUrls($photoUrls)
    {
        $this->photoUrls = $photoUrls;
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}