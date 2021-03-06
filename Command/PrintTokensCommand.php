<?php

/*
 * This file is part of the Apisearch Bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Command;

use Apisearch\Model\Token;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PrintTokensCommand.
 */
class PrintTokensCommand extends WithAppRepositoryBucketCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('apisearch:print-tokens')
            ->setDescription('Print all tokens of an app-id')
            ->addArgument(
                'app-name',
                InputArgument::REQUIRED,
                'App name'
            );
    }

    /**
     * Dispatch domain event.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed|null
     */
    protected function runCommand(InputInterface $input, OutputInterface $output)
    {
        $appName = $input->getArgument('app-name');
        $tokens = $this
            ->repositoryBucket->findRepository($appName)
            ->getTokens();

        $indexArray = $this
                ->repositoryBucket
                ->getConfiguration()[$appName]['indexes'] ?? [];

        /**
         * @var Token
         */
        $table = new Table($output);
        $table->setHeaders(['UUID', 'Indices', 'Seconds Valid', 'Max hits per query', 'HTTP Referrers', 'endpoints', 'plugins', 'ttl']);
        foreach ($tokens as $token) {
            $indicesReversed = array_flip($indexArray);
            $indices = array_map(function (string $index) use ($indicesReversed) {
                return $indicesReversed[$index] ?? null;
            }, $token->getIndices());
            $indices = array_filter($indices);

            $table->addRow([
                $token->getTokenUUID()->composeUUID(),
                implode(', ', $indices),
                $token->getSecondsValid(),
                $token->getMaxHitsPerQuery(),
                implode(', ', $token->getHttpReferrers()),
                implode(', ', $token->getEndpoints()),
                implode(', ', $token->getPlugins()),
                $token->getTtl(),
            ]);
        }
        $table->render();
    }

    /**
     * Dispatch domain event.
     *
     * @return string
     */
    protected function getHeader(): string
    {
        return 'Print tokens';
    }

    /**
     * Get success message.
     *
     * @param InputInterface $input
     * @param mixed          $result
     *
     * @return string
     */
    protected function getSuccessMessage(
        InputInterface $input,
        $result
    ): string {
        return '';
    }
}
