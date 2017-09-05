<?php

/*
 * This file is part of the Search PHP Bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @author PuntMig Technologies
 */

declare(strict_types=1);

namespace Puntmig\Search\Command;

use Puntmig\Search\Model\Coordinate;
use Puntmig\Search\Model\Item;
use Puntmig\Search\Query\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Puntmig\Search\Repository\RepositoryBucket;

/**
 * ExportIndexCommand.
 */
class ExportIndexCommand extends Command
{
    /**
     * @var RepositoryBucket
     *
     * Repository bucket
     */
    private $repositoryBucket;

    /**
     * ResetIndexCommand constructor.
     *
     * @param RepositoryBucket $repositoryBucket
     */
    public function __construct(RepositoryBucket $repositoryBucket)
    {
        parent::__construct();

        $this->repositoryBucket = $repositoryBucket;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('puntmig:search:export-index')
            ->setDescription('Export your index')
            ->addArgument(
                'repository',
                InputArgument::REQUIRED,
                'Repository name'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'File'
            );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repositoryName = $input->getArgument('repository');
        $file = $input->getArgument('file');
        $resource = fopen($file, 'w');

        $i = 0;
        while (true) {
            $items = $this
                ->repositoryBucket
                ->getRepositoryByName($repositoryName)
                ->query(Query::create('', $i, 10000))
                ->getItems();

            if (empty($items)) {
                return;
            }

            $this->writeItemsToResource(
                $resource,
                $items
            );

            $i++;
        }

        fclose($resource);
    }

    /**
     * Echo items as CSV
     *
     * @param Resource $resource
     * @param Item[] $items
     */
    private function writeItemsToResource(
        $resource,
        array $items
    )
    {
        foreach ($items as $item) {
            fputcsv($resource, [
                $item->getId(),
                $item->getType(),
                json_encode($item->getMetadata()),
                json_encode($item->getIndexedMetadata()),
                json_encode($item->getSearchableMetadata()),
                json_encode($item->getExactMatchingMetadata()),
                json_encode($item->getSuggest()),
                json_encode(
                    ($item->getCoordinate() instanceof Coordinate)
                        ? $item->getCoordinate()->toArray()
                        : null
                ),
            ]);
        }
    }
}