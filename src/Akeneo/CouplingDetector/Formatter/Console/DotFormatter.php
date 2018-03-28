<?php

namespace Akeneo\CouplingDetector\Formatter\Console;

use Akeneo\CouplingDetector\Event\PostNodesParsedEvent;
use Akeneo\CouplingDetector\Event\NodeChecked;
use Akeneo\CouplingDetector\Event\NodeParsedEvent;
use Akeneo\CouplingDetector\Event\PreNodesParsedEvent;
use Akeneo\CouplingDetector\Event\PreRulesCheckedEvent;
use Akeneo\CouplingDetector\Event\RuleCheckedEvent;
use Akeneo\CouplingDetector\Event\PostRulesCheckedEvent;
use Akeneo\CouplingDetector\Formatter\AbstractFormatter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Output the results as dots in the console.
 *
 * @author  Julien Janvier <j.janvier@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
class DotFormatter extends AbstractFormatter
{
    /** @var OutputInterface */
    private $output;

    /**
     * DotFormatter constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    protected function outputPreNodesParsed(PreNodesParsedEvent $event)
    {
        $this->output->writeln('Parsing nodes');
    }

    /**
     * {@inheritdoc}
     */
    protected function outputNodeParsed(NodeParsedEvent $event)
    {
        $this->output->write('<passed>.</passed>');
        $this->displayProgress($this->output, $this->parsingNodeIteration, $this->nodeCount);
    }

    /**
     * {@inheritdoc}
     */
    protected function outputPostNodesParsed(PostNodesParsedEvent $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function outputPreRulesChecked(PreRulesCheckedEvent $event)
    {
        $this->output->writeln("\n\nChecking rules");
    }

    /**
     * {@inheritdoc}
     */
    protected function outputNodeChecked(NodeChecked $event)
    {
        $key = $event->getNode()->getFilepath();
        if (null !== $event->getViolation() && !in_array($key, $this->nodesOnError)) {
            $this->nodesOnError[] = $key;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function outputRuleChecked(RuleCheckedEvent $event)
    {
        $nbErrors = count($event->getViolations());
        if (0 === $nbErrors) {
            $this->output->write('<passed>.</passed>');
        } else {
            $this->output->write('<broken-bg>E</broken-bg>');
        }

        $this->displayProgress($this->output, $this->checkingRuleIteration, $this->ruleCount);
    }

    /**
     * {@inheritdoc}
     */
    protected function outputPostRulesChecked(PostRulesCheckedEvent $event)
    {
        $this->output->writeln('');
        $this->output->writeln('');
        $this->output->writeln(
            sprintf(
                '%d rules (<passed>%d passed</passed>, <broken>%d broken</broken>)',
                $this->ruleCount,
                $this->ruleCount - count($this->rulesOnError),
                count($this->rulesOnError)
            )
        );
        $this->output->writeln(
            sprintf(
                '%d nodes (<passed>%d passed</passed>, <broken>%d broken</broken>)',
                $this->nodeCount,
                $this->nodeCount - count($this->nodesOnError),
                count($this->nodesOnError)
            )
        );

        $this->output->writeln('');
        $this->output->writeln('');

        if (0 === $this->violationsCount) {
            $this->output->write('<passed-bg>No coupling issues found </passed-bg>');
            $this->output->write("<passed-bg>\xE2\x9C\x94</passed-bg>");
            $this->output->write("<passed-bg>\xF0\x9F\x98\x83</passed-bg>");
            $this->output->writeln("<passed-bg>\xF0\x9F\x8D\xBB</passed-bg>");
        } else {
            $this->output->write(
                sprintf('<broken-bg>%d coupling issues found </broken-bg>', $this->violationsCount)
            );
            $this->output->write("<broken-bg>\xE2\x9C\x96</broken-bg>");
            $this->output->write("<broken-bg>\xF0\x9F\x98\xA5</broken-bg>");
            $this->output->writeln("<broken-bg>\xF0\x9F\x8D\x86</broken-bg>");
        }
    }

    /**
     * @param OutputInterface $output
     * @param int             $iteration
     * @param int             $total
     */
    private function displayProgress(OutputInterface $output, $iteration, $total)
    {
        if ($iteration % 50 === 0) {
            $length = strlen((string) $total);
            $format = sprintf(' %%%dd / %%%dd', $length, $length);
            $output->write(sprintf($format, $iteration, $total));

            if ($iteration !== $total) {
                $output->writeln('');
            }
        }
    }
}
