<?php


namespace app\command;


use app\common\model\User;
use app\common\service\Eth;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SyncExtraRecharge extends Command
{
    protected function configure()
    {
        $this->setName('syncExtraRecharge')->setDescription('获取额外的充值记录入库');
    }

    protected function execute(Input $input, Output $output)
    {
        User::where('status', 'normal')->chunk(
            100,
            function ($users) use ($output) {
                foreach ($users as $user) {
                    try {
                        Eth::getInstance()->syncExtraRecharge($user);
                    } catch (\Exception $e) {
                        $output->writeln("同步错误:" . $e->getMessage());
                        continue;
                    }
                }
            }
        );
    }
}