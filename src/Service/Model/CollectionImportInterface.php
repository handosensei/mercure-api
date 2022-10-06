<?php

namespace App\Service\Model;

use App\Entity\Collection;

interface CollectionImportInterface
{
    /**
     * Import metadata from API or IPFS server
     * @param Collection $collection
     * @return void
     */
    public function importMetadata(Collection $collection): void;

    /**
     * Save all trait values
     * @param Collection $collection
     * @return void
     */
    public function saveTrait(Collection $collection): void;

    /**
     * Save all traits by token
     * @param Collection $collection
     * @return void
     */
    public function processTokenAttributesBinding(Collection $collection): void;

    /**
     * Count attributes linked
     * @param Collection $collection
     * @return void
     */
    public function processAttributePercent(Collection $collection): void;

    /**
     * Define rank
     * @return void
     */
    public function processRank(Collection $collection): void;

    /**
     * @param Collection $collection
     * @return bool
     */
    public function isSupport(Collection $collection): bool;
}
