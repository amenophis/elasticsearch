<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\Command;

use Amenophis\Elasticsearch\Bridge\Symfony\Exception\IndexBuilderNotFound;
use Amenophis\Elasticsearch\Bridge\Symfony\Exception\IndexNotFound;
use Amenophis\Elasticsearch\Bridge\Symfony\IndexBuilderCollection;
use Amenophis\Elasticsearch\Bridge\Symfony\IndexCollection;
use Amenophis\Elasticsearch\IndexBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class IndexMigrateCommand extends Command
{
    protected static $defaultName = 'amenophis:index:migrate';
    private $indexCollection;
    private $indexBuilderCollection;

    public function __construct(IndexCollection $indexCollection, IndexBuilderCollection $indexBuilderCollection)
    {
        parent::__construct();

        $this->indexCollection        = $indexCollection;
        $this->indexBuilderCollection = $indexBuilderCollection;
    }

    protected function configure()
    {
        $this
            ->addArgument('client', InputArgument::REQUIRED, 'Client name.')
            ->addArgument('index-alias', InputArgument::REQUIRED, 'Index alias.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output     = new SymfonyStyle($input, $output);
        $clientName = $input->getArgument('client');
        $indexAlias = $input->getArgument('index-alias');

        try {
            $indexBuilder = $this->indexBuilderCollection->get($clientName);
            $index        = $this->indexCollection->get($indexAlias);

            $indexFreshness = $indexBuilder->getMappingFreshness($index);

            switch ($indexFreshness) {
                case IndexBuilder::MAPPING_FRESH:
                    $output->success('Index is fresh !');

                    break;
                case IndexBuilder::INDEX_MISSING:
                case IndexBuilder::MAPPING_NEED_RECREATE:
                    $realName = $indexBuilder->createIndex($index);
                    $indexBuilder->reindex($index, $realName);
                    $indexBuilder->markAsLive($index, $realName);
                    $indexBuilder->purgeOldIndices($index);

                    $action = IndexBuilder::INDEX_MISSING === $indexFreshness ? 'created' : 'recreated';
                    $output->success('Index has been '.$action.' !');

                    break;
            }
        } catch (IndexNotFound $e) {
            $output->error(sprintf('Index "%s" not found', $indexAlias));
        } catch (IndexBuilderNotFound $e) {
            $output->error(sprintf('IndexBuilder "%s" not found', $clientName));
        }
    }
}
