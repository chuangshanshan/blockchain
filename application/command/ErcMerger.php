<?php


namespace app\command;


use app\common\service\Eth;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class ErcMerger extends Command
{
    protected function configure()
    {
        $this->setName('ercMerger')->setDescription('充值归并代币');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            Eth::getInstance()->ercMerger();
        } catch (\Exception $e) {
            $output->writeln("脚本错误:" . $e->getMessage());
        }
    }
}