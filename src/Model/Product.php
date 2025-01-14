<?php

namespace Contatoseguro\TesteBackend\Model;

class Product
{



    public function __construct(
        public int $id,
        public int $companyId,
        public string $title,
        public float $price,
        public bool $active,
        public string $createdAt
    ) {
    }

    public $category;
    public string $LastMod;
    
    public static function hydrateByFetch($fetch): self
    {
        return new self(
            $fetch->id,
            $fetch->company_id,
            $fetch->title,
            $fetch->price,
            $fetch->active,
            $fetch->created_at
        );
    }

    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function setLastMod($LastMod)
    {
        $this->LastMod = $LastMod;
    }
}
