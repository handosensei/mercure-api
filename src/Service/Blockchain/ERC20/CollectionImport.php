<?php

namespace App\Service\Blockchain\ERC20;

use App\Entity\Attribute;
use App\Entity\Collection;
use App\Entity\Rank;
use App\Entity\TokenAttribute;
use App\Entity\TraitType;
use App\Enum\BlockchainEnum;
use App\Enum\CollectionStatusEnum;
use App\Repository\AttributeRepository;
use App\Repository\RankRepository;
use App\Repository\TokenRepository;
use App\Service\AttributeService;
use App\Service\FileSystem;
use App\Service\Model\CollectionImportAbstract;
use App\Service\Model\CollectionImportInterface;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;

class CollectionImport extends CollectionImportAbstract implements CollectionImportInterface
{
    private FileSystem $fileSystem;

    private EntityManagerInterface $em;

    private AttributeRepository $attributeRepository;

    private TokenRepository $tokenRepository;

    private RankRepository $rankRepository;

    private AttributeService $attributeService;

    private TokenService $tokenService;

    public function __construct(
        FileSystem $fileSystem,
        EntityManagerInterface $em,
        AttributeRepository $attributeRepository,
        TokenRepository $tokenRepository,
        RankRepository $rankRepository,
        AttributeService $attributeService,
        TokenService $tokenService
    )
    {
        $this->em = $em;
        $this->fileSystem = $fileSystem;
        $this->tokenRepository = $tokenRepository;
        $this->attributeRepository = $attributeRepository;
        $this->rankRepository = $rankRepository;
        $this->attributeService = $attributeService;
        $this->tokenService = $tokenService;
    }

    public function importMetadata(Collection $collection): void
    {
        dump('import Metadata');
        $directory = $this->fileSystem->getMetadataDirectory($collection);
        if (!$this->fileSystem->hasMetadataDirectory($collection)) {
            throw new \Exception(sprintf('Import metadata before add NFT collection and put in %s', $directory));
        }

        $files = scandir($directory);

        $strlenExtension = 0;
        if ($collection->getTraitFileExtension()) {
            $strlenExtension = strlen($collection->getTraitFileExtension()) + 1;
        }

        $strlenMetadataFile = strlen(count($files)) + $strlenExtension;

        foreach ($files as $filename) {
            if (!$this->canHandleFile($collection, $filename)) {
                continue;
            }

            $newFilename = str_pad($filename, $strlenMetadataFile, "0", STR_PAD_LEFT);
            $filenameOrigin = $directory.$filename;
            $filenameTarget = $directory.$newFilename;

            if(!rename($filenameOrigin, $filenameTarget)) {
                throw new \Exception('Rename failed ' . $filename);
            }
        }

        $collection->setStatus(CollectionStatusEnum::METADATA_IMPORTED->value);
        $this->em->persist($collection);
        $this->em->flush();
    }

    public function saveTrait(Collection $collection): void
    {
        dump('saveTrait');
        $directory = $this->fileSystem->getMetadataDirectory($collection);
        $attributes = [];

        try {
            foreach (scandir($directory) as $filename) {
                if (!$this->canHandleFile($collection, $filename)) {
                    continue;
                }

                $json = file_get_contents($directory.$filename);
                $metadata = json_decode($json, true);
                foreach ($metadata['attributes'] as $trait) {
                    if (!isset($attributes[$trait['trait_type']])) {
                        $attributes[$trait['trait_type']] = [];
                        $attributes[$trait['trait_type']][] = null;
                    }

                    if (!in_array($trait['value'], $attributes[$trait['trait_type']])) {
                        $attributes[$trait['trait_type']][] = $trait['value'];
                    }
                }
            }
        } catch (\Exception $e) {
             throw new \Exception('Sort all attributes failed');
        }

        try {
            foreach ($attributes as $strTraitType => $values) {
                $trait = new TraitType();
                $trait
                    ->setName($strTraitType)
                    ->setCollection($collection)
                ;
                foreach ($values as $value) {
                    $attribute = new Attribute();
                    $attribute
                        ->setValue($value)
                        ->setCollection($collection)
                        ->setTraitType($trait)
                    ;

                    $this->em->persist($attribute);
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Save attributes failed');
        }

        $collection->setStatus(CollectionStatusEnum::TRAIT_SAVED->value);
        $this->em->persist($collection);
        $this->em->flush();
    }

    public function processTokenAttributesBinding(Collection $collection): void
    {
        dump('processTokenAttributes');

        $attributeData = $this->attributeService->getAttributesWithValue($collection);
        $nullAttributeData = $this->attributeService->getAttributesWithoutValue($collection);

        $directory = $this->fileSystem->getMetadataDirectory($collection);
        $count = 0;
        $countTokenAttributes = 0;
        foreach (scandir($directory) as $filename) {
            if (!$this->canHandleFile($collection, $filename)) {
                continue;
            }

            $json = file_get_contents($directory.$filename);
            $metadata = json_decode($json, true);
            $tokenNumber = $this->defineTokenByFilename($collection, $filename);
            try {
                $token = $this->tokenService->create($collection, $tokenNumber, $metadata['name'], $metadata['image']);
            } catch (\Exception $e) {
                throw new \Exception('Cant insert token ' . $tokenNumber);
            }

            $attributeKeyValue = $this->attributeService->sortMetadataAttributesByKeyValue($metadata['attributes']);
            try {
                foreach ($nullAttributeData as $traitTypeName => $attributeNull) {

                    if (isset($attributeKeyValue[$traitTypeName])) {
                        $value = $attributeKeyValue[$traitTypeName];
                        if (is_numeric($value)) {
                            continue;
                        }
                        $attribute = $attributeData[$traitTypeName][$value];
                    } else {
                        $attribute = $attributeNull;
                    }
                    $tokenAttribute = new TokenAttribute();
                    $tokenAttribute->setToken($token);
                    $tokenAttribute->setAttribute($attribute);

                    $this->em->persist($tokenAttribute);
                    if (0 == $countTokenAttributes % 500) {
                        echo '.';
                        $this->em->flush();
                    }
                    $countTokenAttributes++;
                }
                $count++;
            } catch (\Exception $e) {
                throw new \Exception(sprintf('Save token attribute failed : %s, trait %s - value %s',
                    $e->getMessage(),
                    $traitTypeName,
                    $attributeKeyValue[$traitTypeName]
                ));
            }

        }

        $collection
            ->setStatus(CollectionStatusEnum::TOKEN_ATTRIBUTE_SAVED->value)
            ->setSupply($count)
        ;
        $this->em->flush();
    }

    public function processAttributePercent(Collection $collection): void
    {
        dump('processAttributePercent');
        $countAttributes = [];
        $attributeData = $this->attributeService->getAttributesSortTraitNameValue($collection);
        $nullAttributeData = $this->attributeService->getAttributesWithoutValue($collection);
        $directory = $this->fileSystem->getMetadataDirectory($collection);
        foreach (scandir($directory) as $filename) {
            if (!$this->canHandleFile($collection, $filename)) {
                continue;
            }
            $json = file_get_contents($directory.$filename);
            $metadata = json_decode($json, true);
            $metadataAttributesKeyValue = $this->attributeService->sortMetadataAttributesByKeyValue($metadata['attributes']);

            foreach ($nullAttributeData as $traitTypeName => $nullAttribute) {
                if (isset($metadataAttributesKeyValue[$traitTypeName])) {
                    $val = $metadataAttributesKeyValue[$traitTypeName];
                    if (is_numeric($val)) {
                        continue;
                    }
                    $attribute = $attributeData[$traitTypeName][$val];
                } else {
                    $attribute = $nullAttribute;
                }

                if (isset($countAttributes[$attribute->getId()])) {
                    $countAttributes[$attribute->getId()]++;
                    continue;
                }

                $countAttributes[$attribute->getId()] = 1;
            }
        }

        unset($attributeData);
        unset($nullAttributeData);

        $attributes = $this->attributeRepository->findBy(['collection' => $collection]);

        foreach ($attributes as $attribute) {
            if (!isset($countAttributes[$attribute->getId()])) {
                continue;
            }
            $percent = $countAttributes[$attribute->getId()]*(100/($collection->getSupply()));
            $attribute->setPercent(round($percent, 5));
            $this->em->persist($attribute);
        }

        $collection->setStatus(CollectionStatusEnum::ATTRIBUTE_PERCENT_PROCESSED->value);
        $this->em->flush();
    }

    private function processScoreCollection(Collection $collection): void
    {
        dump('process ScoreCollection');
        $offset = 0;
        $limit = 1000;
        $count = 0;
        while ($offset <= $collection->getSupply()) {
            $tokens = $this->tokenRepository->findBy(
                ['collection' => $collection],
                ['id' => 'ASC'],
                $limit,
                $offset
            );
            $offset += $limit;
            foreach ($tokens as $token) {
                $rank = $this->processScoreByToken($token);
                $this->em->persist($rank);
                if ($count % 1000 === 0) {
                    $this->em->flush();
                }
                $count++;
                echo '.';
            }
        }
        $this->em->flush();
    }

    private function processScoreByToken($token)
    {
        $sumWithoutNull = 0;
        /** @var TokenAttribute $tokenAttribute */
        foreach ($token->getTokenAttributes() as $tokenAttribute) {
            if ($tokenAttribute->getAttribute()->getValue() !== null) {
                $sumWithoutNull += $tokenAttribute->getAttribute()->getPercent();
            }
        }
        $rank = new Rank();
        $rank
            ->setToken($token)
            ->setCollection($token->getCollection())
            ->setHandoScore($sumWithoutNull)
        ;

        return $rank;
    }

    public function processRank(Collection $collection): void
    {
        $this->processScoreCollection($collection);
        dump('process rank');
        $ranking = 1;

        $ranks = $this->rankRepository->findBy(
            ['collection' => $collection],
            ['handoScore' => 'ASC']
        );

        foreach ($ranks as $rank) {
            $rank->setHandoRank($ranking);
            $this->em->persist($rank);
            if ($ranking % 500 == 0) {
                $this->em->flush();
            }
            $ranking++;
            echo '.';
        }
        $collection->setStatus(CollectionStatusEnum::RANK_EXECUTED->value);
        $this->em->flush();
    }

    private function defineTokenByFilename(Collection $collection, string $filename)
    {
        if ($collection->getTraitFileExtension()) {
            $token = substr($filename, 0, strlen($collection->getTraitFileExtension()) + 1);
        } else {
            $token = $filename;
        }

        return abs((int) $token);
    }

    /**
     * @param Collection $collection
     * @return bool
     */
    public function isSupport(Collection $collection): bool
    {
        return $collection->getBlockchain() === BlockchainEnum::ERC20->value;
    }
}