<?php

/** 等本等息 **/
namespace repayment\src;

class Equally
{
    protected $start_time = null;
    protected $end_time = null;
    protected $deadline = null;
    protected $money = null;
    protected $revenue = null;
    protected $fixedRepaymentDay = null;
    protected $release_time = null;
    protected $num = null;

    public function __construct($start_time, $end_time, $deadline, $money, $revenue, $fixedRepaymentDay = null, $releaseTime = null, $num = null)
    {
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->deadline = $deadline;
        $this->money = $money;
        $this->revenue = $revenue;
        $this->fixedRepaymentDay = $fixedRepaymentDay;
        $this->release_time = $releaseTime;
        $this->num = $num;
    }

    public function run()
    {
        if (!empty($this->release_time) && $this->release_time != $this->start_time) {
            $start = new \DateTime(date('Ym10', $this->start_time));
            $diff = $start->diff(new \DateTime(date('Ym10', $this->release_time)));
            $this->deadline = $this->deadline - (($diff->y) * 12 + $diff->m);
        }

        // 此处为了兼容，如果传过来的是9，则转化成 09，如果是00，怎不变。
        if(!empty($this->fixedRepaymentDay)){
            $this->fixedRepaymentDay =
                (intval($this->fixedRepaymentDay) > 9) ? $this->fixedRepaymentDay :
                    "0".intval($this->fixedRepaymentDay);
        }

        //总收益
        $totalProfit = bcdiv(bcmul(bcmul($this->money, $this->revenue, 10), $this->deadline, 10), 12, 6);
        //每月还款收益
        $everyMonthProfit = bcdiv($totalProfit, $this->deadline, 2);
        //每次还款本金
        $everyMonthPrincipal = bcdiv($this->money, $this->deadline, 2);
        //每月还款金额
        $everyMonthTotalMoney = number_format(bcadd($everyMonthProfit, $everyMonthPrincipal, 2), 2, '.', '');

        $paytime = Tools::everypaytime($this->start_time, $this->end_time, $this->fixedRepaymentDay, $this->release_time);
        foreach ($paytime as $value) {
            //还款时间、月收本息、月收利息、月收本金
            $arr[] = [
                'paytime' => $value['paytime'],
                'totalmoney' => $everyMonthTotalMoney,
                'profit' => $everyMonthProfit,
                'principal' => $everyMonthPrincipal
            ];
        }
        $totalProfit1 = bcdiv(round(bcmul($totalProfit, 100, 3)), 100, 2);
        // 先修正最后一个月的收益
        $arr[count($arr) - 1]['principal'] = bcsub($this->money, bcmul($everyMonthPrincipal, (count($paytime) - 1), 10), 2);
        $arr[count($arr) - 1]['profit'] = bcsub($totalProfit1, bcmul($everyMonthProfit, (count($paytime) - 1), 10), 2);
        $arr[count($arr) - 1]['totalmoney'] = bcadd($arr[count($arr) - 1]['principal'], $arr[count($arr) - 1]['profit'], 2);

        //修正第一个月的收益、总收益
        // 计算差的天数
        $totalDays = $this->deadline * 30;
        // 每一天的收益
        $perProfit = bcdiv($totalProfit, $totalDays, 6);
        $investDay = date("Ymd", $this->start_time);   // 投资日期
        // 计算出投资当月的还款日

        if ($this->fixedRepaymentDay < date('t', $this->start_time)) {
            $currentMonthFixDay = date("Ym" . $this->fixedRepaymentDay, $this->start_time);
        } else {
            $currentMonthFixDay = date("Ymt", $this->start_time);
        }
        $currentMonthFixDay = date("Ymd", strtotime($currentMonthFixDay));
        if (!empty($this->fixedRepaymentDay) && (1*$this->fixedRepaymentDay > 0) && $investDay != $currentMonthFixDay) {
            if ($investDay < $currentMonthFixDay) {
                // 计算出上个月的固定还款日
                if ($this->fixedRepaymentDay < date('t', strtotime("-1 month", $this->start_time))) {
                    $perMonthFixDay = date("Ym" . $this->fixedRepaymentDay, strtotime("-1 month", $this->start_time));
                } else {
                    $perMonthFixDay = date("Ymt", strtotime("-1 month", $this->start_time));
                }
                $subDays = (strtotime($investDay) - strtotime($perMonthFixDay)) / 86400;
            } else {
                $subDays = (strtotime($investDay) - strtotime($currentMonthFixDay)) / 86400;
            }
        } else {
            $subDays = 0;
        }
        // 修正总收益
        $totalProfit = ($subDays > 0) ? bcsub($totalProfit, bcmul($perProfit, $subDays, 6), 3) : bcadd($totalProfit, 0, 3);
        $totalProfit = bcdiv(round(bcmul($totalProfit, 100, 3)), 100, 2);
        // 修正第一月的收益
        $arr[0]['profit'] = bcsub($arr[0]['profit'], bcmul($subDays, $perProfit, 6), 2);
        $arr[0]['totalmoney'] = bcadd($arr[0]['principal'], $arr[0]['profit'], 2);

        //返回数据
        $data['list'] = $arr;
        if ($this->num !== null) {
            $index = isset($this->num) && intval($this->num) ? intval($this->num) : 0;
            return $arr[$index];
        }

        $data['survey'] = array($this->money, date('Y-m-d'), $this->deadline, $totalProfit);

        return $data;
    }
}
