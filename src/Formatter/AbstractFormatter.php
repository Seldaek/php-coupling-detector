<?php

declare(strict_types=1);

namespace Akeneo\CouplingDetector\Formatter;

use Akeneo\CouplingDetector\Domain\NodeInterface;
use Akeneo\CouplingDetector\Domain\RuleInterface;
use Akeneo\CouplingDetector\Event\PostNodesParsedEvent;
use Akeneo\CouplingDetector\Event\Events;
use Akeneo\CouplingDetector\Event\NodeChecked;
use Akeneo\CouplingDetector\Event\NodeParsedEvent;
use Akeneo\CouplingDetector\Event\PreNodesParsedEvent;
use Akeneo\CouplingDetector\Event\PreRulesCheckedEvent;
use Akeneo\CouplingDetector\Event\RuleCheckedEvent;
use Akeneo\CouplingDetector\Event\PostRulesCheckedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Abstract default formatter that collects event statistics.
 * Output should be handled in children implementations.
 *
 * @author  Julien Janvier <j.janvier@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
abstract class AbstractFormatter implements EventSubscriberInterface
{
    /** @var int total number of nodes */
    protected $nodeCount = 0;

    /** @var int total number of rules */
    protected $ruleCount = 0;

    /** @var int current node number being parsed */
    protected $parsingNodeIteration = 0;

    /** @var int current node number being checked for a rule */
    protected $checkingNodeIteration = 0;

    /** @var int current rule number being checked */
    protected $checkingRuleIteration = 0;

    /** @var int total number of violations */
    protected $violationsCount = 0;

    /** @var NodeInterface[] */
    protected $nodesOnError = [];

    /** @var RuleInterface[] */
    protected $rulesOnError = [];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_NODES_PARSED => 'preNodesParsed',
            Events::NODE_PARSED => 'nodeParsed',
            Events::POST_NODES_PARSED => 'postNodesParsed',
            Events::PRE_RULES_CHECKED => 'preRulesChecked',
            Events::NODE_CHECKED => 'nodeChecked',
            Events::RULE_CHECKED => 'ruleChecked',
            Events::POST_RULES_CHECKED => 'postRulesChecked',
        );
    }

    /**
     * @param PreNodesParsedEvent $event
     */
    public function preNodesParsed(PreNodesParsedEvent $event): void
    {
        $this->nodeCount = $event->getFinder()->count();
        $this->outputPreNodesParsed($event);
    }

    /**
     * @param NodeParsedEvent $event
     */
    public function nodeParsed(NodeParsedEvent $event): void
    {
        ++$this->parsingNodeIteration;
        $this->outputNodeParsed($event);
    }

    /**
     * @param PostNodesParsedEvent $event
     */
    public function postNodesParsed(PostNodesParsedEvent $event): void
    {
        $this->outputPostNodesParsed($event);
    }

    /**
     * @param PreRulesCheckedEvent $event
     */
    public function preRulesChecked(PreRulesCheckedEvent $event): void
    {
        $this->ruleCount = count($event->getRules());
        $this->outputPreRulesChecked($event);
    }

    /**
     * @param NodeChecked $event
     */
    public function nodeChecked(NodeChecked $event): void
    {
        ++$this->checkingNodeIteration;
        $key = $event->getNode()->getFilepath();
        if (null !== $event->getViolation() && !in_array($key, $this->nodesOnError)) {
            $this->nodesOnError[] = $event->getNode();
        }

        $this->outputNodeChecked($event);
    }

    /**
     * @param RuleCheckedEvent $event
     */
    public function ruleChecked(RuleCheckedEvent $event): void
    {
        ++$this->checkingRuleIteration;
        $this->checkingNodeIteration = 0;
        $key = $event->getRule()->getSubject().$event->getRule()->getType();
        $nbErrors = count($event->getViolations());
        if (0 !== $nbErrors && !in_array($key, $this->rulesOnError)) {
            $this->rulesOnError[$key] = $event->getRule();
            $this->violationsCount += $nbErrors;
        }

        $this->outputRuleChecked($event);
    }

    /**
     * @param PostRulesCheckedEvent $event
     */
    public function postRulesChecked(PostRulesCheckedEvent $event): void
    {
        $this->outputPostRulesChecked($event);
    }

    abstract protected function outputPreNodesParsed(PreNodesParsedEvent $event): void;
    abstract protected function outputNodeParsed(NodeParsedEvent $event): void;
    abstract protected function outputPostNodesParsed(PostNodesParsedEvent $event): void;
    abstract protected function outputPreRulesChecked(PreRulesCheckedEvent $event): void;
    abstract protected function outputNodeChecked(NodeChecked $event): void;
    abstract protected function outputRuleChecked(RuleCheckedEvent $event): void;
    abstract protected function outputPostRulesChecked(PostRulesCheckedEvent $event): void;
}
