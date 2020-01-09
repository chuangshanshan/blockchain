<?php


namespace app\command;


use app\common\service\Eth;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class RequestWithdraw extends Command
{
    protected function configure()
    {
        $this->setName('requestWithdraw')->setDescription('发起提现请求');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            Eth::getInstance()->requestWithdraw();
        } catch (\Exception $e) {
            $output->writeln("脚本错误:" . $e->getMessage());
        }
    }
}