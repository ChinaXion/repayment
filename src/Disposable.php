<?php

/** 到期还本付息 **/
namespace ChinaXion\repayment;

class Disposable
{
    protected $start_time = null;
    protected $end_time = null;
    protected $money = null;
    protected $revenue = null;

    public function __construct($start_time, $end_time, $money, $revenue)
    {
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->money = $money;
        $this->revenue = $revenue;
    }
    
    public function run()
    {
        //投资总天数
        $day = ceil(bcdiv(bcsub($this->end_time, $this->start_time, 0), 86400, 10));

        //还款时间
        $tempdata[0]['paytime'] = date('Y-m-d', $this->end_time);

        //投资收益
        $tempdata[0]['profit'] = bcdiv(bcmul(bcmul($this->revenue, $this->money, 10), $day, 10), 365, 2);

        //投资本金
        $tempdata[0]['principal'] = $this->money;

        //还款总额
        $tempdata[0]['totalmoney'] = bcadd($tempdata[0]['profit'], $tempdata[0]['principal'], 2);

        //返回数据
        $data['list'] = $tempdata;
        $data['survey'] = array($this->money, date('Y-m-d', $this->start_time), $day, $tempdata[0]['profit']);

        return $data;
    }

}
