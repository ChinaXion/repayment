<?php

/** 按月付息，到期换本 **/
namespace repayment\src;

class DueToThe
{
    protected $start_time = null;
    protected $end_time = null;
    protected $money = null;
    protected $revenue = null;
    protected $release_time = null;
    protected $fixedRepaymentDay  =  null;
    protected $num  =  null;


    public function __construct($start_time, $end_time, $money, $revenue, $fixedRepaymentDay,$releaseTime,$num)
    {
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->money = $money;
        $this->revenue = $revenue;
        $this->release_time = $releaseTime;
        $this->fixedRepaymentDay = $fixedRepaymentDay;
        $this->num = $num;
    }

    public function run()
    {
        $arr = [];
        $totalProfit = $totalDay = 0;
        // 此处为了兼容，如果传过来的是9，则转化成 09，如果是00，怎不变。
        if(!empty($this->fixedRepaymentDay)){
            $this->fixedRepaymentDay =
                (intval($this->fixedRepaymentDay) > 9) ? $this->fixedRepaymentDay :
                    "0".intval($this->fixedRepaymentDay);
        }

        //计算每次还款时间
        $paytime =  Tools::everypaytime($this->start_time , $this->end_time,$this->fixedRepaymentDay,$this->release_time);
        foreach ($paytime as $key => $value) {
            //每次还款收益
            $profit = bcdiv(bcmul(bcmul($this->revenue, $this->money, 10), $value['days'], 10), 365, 3);

            //修正每次还款金额
            $profit = number_format($profit, 2, '.', '');

            //累计投资总天数
            $totalDay = bcadd($value['days'], $totalDay);
            //累计投资总收益
            $totalProfit = bcadd($totalProfit, $profit, 2);
            //还款时间、月收本息、月收本金、月收利息
            $arr[] = ['paytime' => $value['paytime'], 'totalmoney' => $profit, 'principal' => '0.00', 'profit' => $profit];
        }

        //从新计算最后一次收益
        $totalProfit2 = bcdiv(bcmul(bcmul($this->revenue, $this->money, 10), $totalDay, 10), 365, 3);
        $totalProfit2 = bcdiv(round(bcmul($totalProfit2,100,3)),100,2);

        //最后一次还款收益
        $arr[count($arr) - 1]['profit'] = bcadd($arr[count($arr) - 1]['profit'], bcsub($totalProfit2, $totalProfit, 2), 2);;

        //给最后一次元素添加本金
        $arr[count($arr) - 1]['principal'] = bcadd(0, $this->money, 2);
        $arr[count($arr) - 1]['totalmoney'] = bcadd($arr[count($arr) - 1]['profit'], $this->money, 2);

        //返回数据
        $data['list'] = $arr;
        $data['survey'] = array($this->money, date('Y-m-d', $this->start_time), $totalDay, $totalProfit2);
        if ($this->num !== null) return $arr[$this->num];
        return $data;
    }
}
