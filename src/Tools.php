<?php

namespace ChinaXion\repayment;

class Tools
{
    /**
     * 下次还款日期
     * @param $starttime
     * @param $n
     * @return false|int
     */
    public static function nextMonth($starttime, $n)
    {
        $nextTime = strtotime(date('Y-m-d', strtotime("+$n month", $starttime)));
        $start = explode('-', date('Y-m-d', $starttime));
        $end = explode('-', date('Y-m-d', $nextTime));

        //处理29,30,31
        if ($end[2] != $start[2]) {    //对应日期多少号不一致
            //月数向前减去一个月，并获取那个月份最后一天的日期号数
            $month = $end[1] - 1;
            $str = "1.{$month}.{$end[0]}";
            $maxday = strtotime(date('Y-m-t', strtotime($str)));
            $nextTime = $maxday;
        }

        return $nextTime;
    }


    /**
     * 计算还款日期
     * @param $startDate // 计息开始时间
     * @param $endDate // 计息结束时间
     * @param null $fixedRepaymentDay // 固定还款日期
     * @param null $releaseTime // 原始投资的计息开始时间
     * @return array
     */
    public static function everypaytime($startDate, $endDate, $fixedRepaymentDay, $releaseTime)
    {
        // 从当月开始计算，还是下个月开始计算。
        // 判断是不是当月就有还款。
        if (!empty($fixedRepaymentDay) && $fixedRepaymentDay > 0) {
            $currentMonthLastDay = date('t', $startDate);
            $nextPayDay = ($fixedRepaymentDay > $currentMonthLastDay) ? $currentMonthLastDay : $fixedRepaymentDay;
            if($nextPayDay > date('d',$startDate)){
                $num = 0;
            }else{
                $num = 1;
            }
        } else {
            $num = 1;
        }

        do {
            $lasttime = empty($nextpaytime) ? $startDate : $nextpaytime;
            $nextpaytime = self::nextMonth($startDate, $num);
            // 如果有固定还款日,如果固定还款日，大于这个月的最后一天了，进行修正
            if (!empty($fixedRepaymentDay) && $fixedRepaymentDay > 0) {
                $currentMonthLastDay = date("t", $nextpaytime);  // 当月的最后一天
                $nextPayDay = ($fixedRepaymentDay > $currentMonthLastDay) ? $currentMonthLastDay : $fixedRepaymentDay;
                $nextpaytime = strtotime(date('Y-m-' . $nextPayDay, $nextpaytime));
            }
            // 说明：这里是为了兼容，当原始标部分转让成功，这时候，计算预计收益的时候，是把剩余的债权当做一笔新投资来计算的。如果有固定还款日，就不需要计算了。
            // 举例说明：生成转让标的投资时间是12月30日，2月28日设置了转让，剩余的债权就按照2月28日当做投资开始时间，下次还款时间就是3月28日了。为了兼容这个。
            if (empty($fixedRepaymentDay) && !empty($releaseTime)) {
                // 原始投资的日期。
                $fixedInvestDate = date('d', $releaseTime);
                // 下次还款日期进行修正。
                if ($releaseTime != $startDate && $fixedInvestDate > 28) {
                    $currentMonthDay = date("t", $nextpaytime);  // 当月的最后一天
                    $payDay = ($fixedInvestDate > $currentMonthDay) ? $currentMonthDay : $fixedInvestDate;
                    // 如果大于 28 天，修正还款日
                    $nextpaytime = strtotime(date('Y-m-' . $payDay, $nextpaytime));
                }
            }
            // 最后一期修正
            if ($nextpaytime > $endDate) {
                $nextpaytime = $endDate;
            }
            $day = ceil(bcdiv(bcsub($nextpaytime, $lasttime, 0), 86400, 1));
            $data[] = ['paytime' => date('Y-m-d', $nextpaytime), 'days' => $day];
            $num++;
        } while ($nextpaytime < $endDate);

        return $data;
    }
}
