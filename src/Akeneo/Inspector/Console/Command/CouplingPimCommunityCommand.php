<?php

namespace Akeneo\Inspector\Console\Command;

use Akeneo\Inspector\Coupling\Detector;
use Akeneo\Inspector\Coupling\ViolationsFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Detects the coupling issues in pim-community-dev
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 */
class CouplingPimCommunityCommand extends Command
{
    /**
     * {@inheritedDoc}
     */
    protected function configure()
    {
        $this
            ->setName('coupling-pim-community-dev')
            ->setDefinition(
                array(
                    new InputArgument('path', InputArgument::REQUIRED, 'The pim community dev src path', null),
                    new InputOption('strict', '', InputOption::VALUE_NONE, 'Apply strict rules without legacy exceptions'),
                )
            )
            ->setDescription('Detect coupling violations in pim-community-dev');
    }

    /**
     * {@inheritedDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path       = $input->getArgument('path');
        $strictMode = $input->getOption('strict');

        $namespaceToForbiddenUse = [
            'Akeneo/Component' => ['Pim', 'PimEnterprise', 'Bundle'],
            'Akeneo/Bundle'    => ['Pim', 'PimEnterprise'],
            'Pim/Component'    => ['PimEnterprise', 'Bundle'],
            'Pim/Bundle'       => ['PimEnterprise'],
        ];

        $legacyExclusions = [
            'Akeneo/Component' => [
                'Pim\Bundle\TranslationBundle\Entity\TranslatableInterface'
            ],
            'Akeneo/Bundle'    => [],
            'Pim/Component'    => [
                'Pim\Bundle\CatalogBundle\Repository\AssociationTypeRepositoryInterface'
            ],
            'Pim/Bundle'       => [],
        ];
        $violationFilter = new ViolationsFilter($legacyExclusions);

        $output->writeln(sprintf('<info> Detect coupling violations (strict mode %s)</info>', $strictMode ? 'enabled' : 'disabled'));
        $totalCount = 0;
        foreach ($namespaceToForbiddenUse as $namespace => $forbiddenUse) {
            $detector = new Detector($namespace, $forbiddenUse);
            $violations = $detector->detectCoupling($path, $namespace, $forbiddenUse);
            if (!$strictMode) {
                $violations = $violationFilter->filter($violations);
            }
            $forbiddenUseCounter = $violations->getSortedForbiddenUsesCounter();
            $namespaceCount = 0;
            $output->writeln(sprintf('<info>>> Inspect namespace %s</info>', $namespace));
            foreach ($forbiddenUseCounter as $fullName => $count) {
                $output->writeln(sprintf('<info> - %d x %s</info>', $count, $fullName));
                $namespaceCount += $count;
            }
            $output->writeln(sprintf('<info>%d coupling issues for namespace %s</info>', $namespaceCount, $namespace));
            $totalCount += $namespaceCount;
        }

        $output->writeln(sprintf('<info>Total coupling issues %s</info>', $totalCount));

        return $totalCount;
    }
}
