<?php

/**  按月等额本息  **/
namespace repayment\src;

class Average {

    protected $start_time = null;
    protected $deadline = null;
    protected $money = null;
    protected $revenue = null;
    protected $num = null;

    public function __construct($start_time, $deadline, $money, $revenue, $num)
    {
        $this->start_time = $start_time;
        $this->deadline = $deadline;
        $this->money = $money;
        $this->revenue = $revenue;
        $this->num = $num;
    }

    public function run()
    {
        //月利率
        $arr = $totalTotalMoney = $totalProfit = null;
        $monthlv = bcdiv($this->revenue, 12, 10);
        $pow = bcpow(bcadd(1, $monthlv, 10), $this->deadline, 10);
        $chushu = bcmul(bcmul($this->money, $monthlv, 10), $pow, 10);

        //每月还款金额
        $oneMonth = bcdiv($chushu, bcsub($pow, 1, 10), 3);
        $oneMonth = number_format($oneMonth, 2, '.', '');

        $tempMoney = $this->money;
        $totalTotalMoney = 0;
        for ($i = 1; $i <= $this->deadline; $i++) {

            //每次还款时间
            $paytime = date('Y-m-d', Tools::nextMonth($this->start_time, $i));

            //本次还款利息
            $profit = bcmul($tempMoney, $monthlv, 2);

            //本次还款本金
            $principal = bcsub($oneMonth, $profit, 2);
            $totalTotalMoney = bcadd($totalTotalMoney, $principal, 2);

            //计算下次投资本金
            $tempMoney = bcsub($tempMoney, $principal, 2);
            //还款时间、月收本息、月收利息、月收本金
            $arr[] = ['paytime' => $paytime, 'totalmoney' => $oneMonth, 'profit' => $profit, 'principal' => $principal];
        }

        // 平衡设置
        $arr[count($arr) - 1]['principal'] = bcsub($this->money, bcsub($totalTotalMoney, $arr[count($arr)-1]['principal'],2), 2);
        $arr[count($arr) - 1]['profit'] = bcsub($oneMonth, $arr[count($arr)-1]['principal'], 2);

        //投资总收益
        $totalProfit = bcsub(bcmul($oneMonth, $this->deadline, 2), $this->money, 2);

        //返回数据
        $data['list'] = $arr;
        if ($this->num !== null){
            $index = isset($this->num) && intval($this->num) ? intval($this->num) : 0;
            return $arr[$index];
        }

        $data['survey'] = array($this->money, date('Y-m-d'), $this->deadline, $totalProfit);

        return $data;
    }
}
