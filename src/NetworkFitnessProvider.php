<?php

namespace Podorozhny\Dissertation;

use Assert\Assert;

final class NetworkFitnessProvider
{
    const GOAL = 1000;

    private static $instance;

    /** @var Node[] */
    private static $nodes;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    protected function __wakeup()
    {
    }

    /**
     * @return NetworkFitnessProvider
     */
    public static function getInstance(): NetworkFitnessProvider
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * @param array $nodes
     */
    public static function setNodes(array $nodes)
    {
        self::$nodes = array_values($nodes);
    }

    /**
     * @param bool[] $bits
     *
     * @return string
     */
    public static function getFitness(array $bits): string
    {
        Assert::thatAll($bits)->boolean();

        Assert::that(count($bits))->eq(count(self::$nodes));

        if (array_sum($bits) === 0) {
            return 0;
        }

        $baseStation = new BaseStation(FIELD_SIZE * 10 / 2, FIELD_SIZE * 10 / 2);

        $clusterHeads = [];
        $clusterNodes = [];

        foreach (self::$nodes as $key => $node) {
            if (!$bits[$key]) {
                continue;
            }

            $node->makeClusterHead();

            $clusterHeads[] = $node;
        }

        foreach (self::$nodes as $key => $node) {
            if ($bits[$key]) {
                continue;
            }

            $node->makeClusterNode($node->getNearestNeighbor($clusterHeads));

            $clusterNodes[] = $node;
        }

        $network = new Network($baseStation, $clusterHeads, $clusterNodes);

        foreach ($network->getNodes() as $node) {
            $node->restoreCharge();
        }

        $totalCharge = $network->getTotalCharge();
//        $deadNodesCount = $network->getDeadNodesCount();

        (new OneRoundChargeReducer())->reduce($network);

        $totalChargeConsumption = bcsub($totalCharge, $network->getTotalCharge(), BC_SCALE);

//        $nodesDied = $network->getDeadNodesCount() - $deadNodesCount;

        $averageChargeConsumption = bcdiv($totalChargeConsumption, $network->getNodesCount(), BC_SCALE);

        if ($averageChargeConsumption == 0) {
            return self::GOAL;
        }

        $fitness = bcdiv(1, $averageChargeConsumption, BC_SCALE);

//        if ($nodesDied > 0) {
//            $fitness += (1 / $nodesDied);
//        }

        return $fitness;
    }
}
