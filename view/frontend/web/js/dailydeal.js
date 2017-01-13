/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

function DealTimeCounter() {
    this.init = function (end_time) {
        this.now_time = (new Date()).getTime();
        this.end_time = parseInt(end_time) * 1000;
        this.end = new Date(this.end_time);
        var endDate = this.end;
        this.second = endDate.getSeconds();
        this.minute = endDate.getMinutes();
        this.hour = endDate.getHours();
        this.day = endDate.getDate();
        this.month = endDate.getMonth();
        this.year = endDate.getFullYear();
    }
    
    this.setTimeleft = function (timeleft_id)
    {
        var now = new Date(this.now_time);
        var endtext = '0';
        var timerID;

        var sec = this.second - now.getSeconds();
        var min = this.minute - now.getMinutes();
        var hr = this.hour - now.getHours();
        var dy = this.day - now.getDate();
        var mnth = this.month - now.getMonth();
        var yr = this.year - now.getFullYear();

        var daysinmnth = 32 - new Date(now.getFullYear(), now.getMonth(), 32).getDate();
        if (sec < 0) {
            sec = (sec + 60) % 60;
            min--;
        }
        if (min < 0) {
            min = (min + 60) % 60;
            hr--;
        }
        if (hr < 0) {
            hr = (hr + 24) % 24;
            dy--;
        }
        if (dy < 0) {
            dy = (dy + daysinmnth) % daysinmnth;
            mnth--;
        }
        if (mnth < 0) {
            mnth = (mnth + 12) % 12;
            yr--;
        }
        var sectext = "secs";
        var mintext = "mins";
        var hrtext = "hours";
        var dytext = " days";
        var mnthtext = " months";
        var yrtext = " years";
        if (yr == 1)
            yrtext = " year";
        if (mnth == 1)
            mnthtext = " month";
        if (dy == 1)
            dytext = " day";
        if (hr == 1)
            hrtext = "hour";
        if (min == 1)
            mintext = "min";
        if (sec == 1)
            sectext = "sec";

        if (dy < 10)
            dy = '0' + dy;
        if (hr < 10)
            hr = '0' + hr;
        if (min < 10)
            min = '0' + min;
        if (sec < 10)
            sec = '0' + sec;

        if (yr <= 0)
            yrtext = ''
        else
            yrtext = '<li><span class="timeleft-value">' + yr + '</span><span class="timeleft-label">' + yrtext + '</span></li>'

        if ((mnth <= 0))
            mnthtext = ''
        else
            mnthtext = '<li><span class="timeleft-value">' + mnth + '</span><span class="timeleft-label">' + mnthtext + '</span></li>';

        if (dy <= 0 && mnth > 0)
            dytext = ''
        else
            dytext = '<li><span class="timeleft-value">' + dy + '</span><span class="timeleft-label">' + dytext + '</span></li>';

        if (hr <= 0 && dy > 0)
            hrtext = ''
        else
            hrtext = '<li><span class="timeleft-value">' + hr + '</span><span class="timeleft-label">' + hrtext + '</span></li>';

        if (min < 0)
            mintext = ''
        else
            mintext = '<li><span class="timeleft-value">' + min + '</span><span class="timeleft-label">' + mintext + '</span></li>';

        if (sec < 0)
            sectext = ''
        else
            sectext = '<li><span class="timeleft-value">' + sec + '</span><span class="timeleft-label">' + sectext + '</span></li>';

        if (now >= this.end) {
            document.getElementById(timeleft_id).innerHTML = endtext;
            clearTimeout(timerID);
        } else {
            document.getElementById(timeleft_id).innerHTML = '<ul class="dailydeal-countdown">' + yrtext + mnthtext + dytext + hrtext + mintext + sectext + '</ul>';
        }

        if (this.now_time == this.end_time) {
            location.reload(true);
            return;
        }

        this.now_time = this.now_time + 1000; //increase 1000 miliseconds
        var nowTimeSecond = this.now_time / 1000;
        var endTimeSecond = this.end_time / 1000;
        timerID = setTimeout(function(){setDealTimeleft(endTimeSecond,timeleft_id);}, 1000);
    }
    
    return this;
}

function setDealTimeleft(end_time, timeleft_id)
{
    var counter = new DealTimeCounter();
    counter.init(end_time);
    counter.setTimeleft(timeleft_id);
}
