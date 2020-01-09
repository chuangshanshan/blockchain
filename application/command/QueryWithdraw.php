<?php


namespace app\command;


use app\common\service\Eth;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class QueryWithdraw extends Command
{
    protected function configure()
    {
        $this->setName('queryWithdraw')->setDescription('查询交易结构');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            Eth::getInstance()->queryWithdraw();
        } catch (\Exception $e) {
            $output->writeln("脚本错误:" . $e->getMessage());
        }
    }
}