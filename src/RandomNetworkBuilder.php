<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;

class RandomNetworkBuilder implements NetworkBuilder
{
    const CLUSTER_HEADS_RATIO = 0.1;

    /**
     * {@inheritdoc}
     */
    public function build(BaseStation $baseStation, array $nodes): Network
    {
        /** @var Node[] $nodes */

        Assertion::allIsInstanceOf($nodes, Node::class);

        $nodes = array_filter(
            $nodes,
            function (Node $node) {
                return !$node->isDead();
            }
        );

        $nodesCount = count($nodes);

        if ($nodesCount === 0) {
            return new Network($baseStation, [], []);
        }

        $clusterHeadsCount = ceil($nodesCount * self::CLUSTER_HEADS_RATIO);

        $keys = array_keys($nodes);

        shuffle($keys);

        $tmp = [];

        foreach ($keys as $id) {
            $tmp[$id] = $nodes[$id];
        }

        $nodes = $tmp;

        // Random cluster heads
        $clusterHeads = array_slice($nodes, 0, $clusterHeadsCount);

        /** @var Node[] $clusterNodes */
        $clusterNodes = [];

        foreach ($nodes as $node) {
            if (false !== array_search($node, $clusterHeads, true)) {
                $node->makeClusterHead();

                continue;
            }

            $node->makeClusterNode($node->getNearestNeighbor($clusterHeads));

            $clusterNodes[] = $node;
        }

        return new Network($baseStation, $clusterHeads, $clusterNodes);
    }
}
