<?php

namespace repayment\src;

class RepaymentCalculation
{
    /* 还款方式 */
    public $wayRepayment = null;

    /* 计息开始时间 */
    public $startTime = null;

    /* 计息结束时间 */
    public $endTime = null;

    /* 投资期限 */
    public $deadline = null;

    /* 投资金额 */
    public $money = null;

    /* 投资年化 */
    public $revenue = null;

    /* 固定还款日 */
    public $fixedRepaymentDay = null;

    /* 原始投资开始时间 */
    public $releaseStarTime = null;

    /* 还款次数 */
    public $num = null;

    /**
     * 初始化赋值
     * RepaymentCalculation constructor.
     * @param $wayRepayment
     * @param $startTime
     * @param $endTime
     * @param $deadline
     * @param $money
     * @param $revenue
     * @param $fixedRepaymentDay
     * @param null $releaseStarTime
     * @param null $num
     */
    public function __construct($wayRepayment, $startTime, $endTime, $deadline, $money, $revenue, $fixedRepaymentDay, $releaseStarTime = null,$num = null)
    {
        $this->wayRepayment = $wayRepayment;
        $this->startTime = strtotime($startTime);
        $this->endTime = strtotime($endTime);
        $this->deadline = $deadline;
        $this->money = $money;
        $this->revenue = bcdiv($revenue, 100, 4);
        $this->fixedRepaymentDay = $fixedRepaymentDay;
        $this->releaseStarTime = strtotime($releaseStarTime);
        $this->num = $num;
    }

    /**
     * 获取还款列表
     * @return mixed
     */
    public function execute()
    {
        switch ($this->wayRepayment) {
            //按月付息，到期还本
            case '1':
                $repayment = new DueToThe($this->startTime, $this->endTime, $this->money, $this->revenue, $this->fixedRepaymentDay, $this->releaseStarTime,$this->num);
                break;
            //按月等额本息
            case '2':
                $repayment = new Average($this->startTime, $this->deadline, $this->money, $this->revenue, $this->num);
                break;
            //到期还本付息
            case '3':
                $repayment = new Disposable($this->startTime, $this->endTime, $this->money, $this->revenue);
                break;
            //等本等息
            case '5':
                $repayment = new Equally($this->startTime, $this->endTime, $this->deadline, $this->money, $this->revenue, $this->fixedRepaymentDay, $this->releaseStarTime, $this->num);
                break;
            default:
                return false;
                break;
        }
        return $repayment->run();
    }
}
