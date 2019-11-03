<?php

declare(strict_types=1);

namespace Amenophis\Elasticsearch\Bridge\Symfony\Command;

use Amenophis\Elasticsearch\Bridge\Symfony\ClientCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClientInfoCommand extends Command
{
    protected static $defaultName = 'amenophis:debug:client';
    /**
     * @var ClientCollection
     */
    private $clientCollection;

    public function __construct(ClientCollection $clientCollection)
    {
        parent::__construct();

        $this->clientCollection = $clientCollection;
    }

    public function showClientSummary(SymfonyStyle $output): void
    {
        $output->title('Elasticsearch clients');

        $clientStatus = [];
        foreach ($this->clientCollection->all() as $clientName => $client) {
            $connected      = $client->ping() ? 'Connected' : 'Not connected';
            $clientStatus[] = [$clientName, $connected];
        }

        $output->table(['name', 'info'], $clientStatus);
    }

    public function showClientDetail(SymfonyStyle $output, string $clientName): void
    {
        if (!$this->clientCollection->has($clientName)) {
            $output->error(sprintf('Client "%s" doesn\'t exists', $clientName));

            return;
        }

        $output->title(sprintf('Elasticsearch "%s"', $clientName));

        $client = $this->clientCollection->get($clientName);

        if (!$client->ping()) {
            $output->error('No alive nodes found in your cluster');
        }

        $info    = $client->info();
        $indices = $client->indices()->get(['index' => '*']);

        $infos = [
            ['version', $info['version']['number']],
            ['lucene version', $info['version']['lucene_version']],
            ['cluster uuid', $info['cluster_uuid']],
            ['indices', $this->formatList(array_keys($indices))],
        ];

        $output->table([], $infos);
    }

    protected function configure()
    {
        $this
            ->addArgument('client-name', InputArgument::OPTIONAL, 'Show details on the client if argument is provided.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output     = new SymfonyStyle($input, $output);
        $clientName = $input->getArgument('client-name');

        if (null !== $clientName) {
            $this->showClientDetail($output, $clientName);
        } else {
            $this->showClientSummary($output);
        }
    }

    private function formatList(array $elements, string $separator = ',', string $arround = '"', string $emptyResult = '-')
    {
        if (0 === \count($elements)) {
            return $emptyResult;
        }

        $glue = sprintf('%s%s %s', $arround, $separator, $arround);

        return sprintf('%s%s%s', $arround, implode($glue, $elements), $arround);
    }
}
