<?php

namespace App\Command;

use App\Entity\Collection;
use App\Enum\BlockchainEnum;
use App\Enum\CollectionStatusEnum;
use App\Repository\CollectionRepository;
use App\Service\CollectionImport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:collection:add')]
class CollectionAddCommand extends Command
{
    private CollectionImport $collectionImport;
    private CollectionRepository $collectionRepository;

    public function __construct(
        CollectionImport $collectionImport,
        CollectionRepository $collectionRepository)
    {
        $this->collectionImport = $collectionImport;
        $this->collectionRepository = $collectionRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('blockchain', InputArgument::REQUIRED, 'Blockchain (ERC20, Elrond)')
            ->addArgument('project', InputArgument::REQUIRED, 'Project name')
            ->addArgument('identifier', InputArgument::REQUIRED, 'Smart contrart or identifier collection')
            ->addOption('extension-metadata', 'f', InputOption::VALUE_OPTIONAL, 'File metadata extension', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $blockchain = BlockchainEnum::tryFrom($input->getArgument('blockchain'));
        if (null === $blockchain) {
            $output->writeln(sprintf('Unknow blockchain \'%s\'', $input->getArgument('blockchain')));
            return Command::INVALID;
        }

        $collection = $this->collectionRepository->findOneByIdentifier($input->getArgument('identifier'));
        if ($collection === null) {
            $collection = $this->create($input, $output);
        }

        try {
            $this->collectionImport->run($collection);
        } catch (\Exception $e) {
            throw new \Exception('Error import collection: ' . $e->getMessage());
        }
        $output->writeln(sprintf('Import NFT collection %s finished', $input->getArgument('identifier')));

        return Command::SUCCESS;
    }

    private function create(InputInterface $input, OutputInterface $output)
    {
        $collection = new Collection();
        $collection
            ->setBlockchain($input->getArgument('blockchain'))
            ->setName($input->getArgument('project'))
            ->setIdentifier($input->getArgument('identifier'))
            ->setStatus(CollectionStatusEnum::ADDED->value)
        ;

        if (null !== $input->getOption('extension-metadata')) {
            $collection->setTraitFileExtension($input->getOption('extension-metadata'));
        }

        $this->collectionRepository->save($collection, true);

        return $collection;
    }
}
