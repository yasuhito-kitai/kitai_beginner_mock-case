<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;
use App\Models\BreakTime;
use Carbon\Carbon;

class AutoStamp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:AutoStamp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '日付が変わって退勤打刻されていなければ自動で出勤状態にする';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::now()->subDay()->format('Y-m-d');


//休憩から精算していく

    //（定義）休憩：日を跨いだ時点で（休憩開始状態で）休憩終了打刻がされていないレコード抽出
    $auto_update_break_times_records = BreakTime::where("date", "=", "$yesterday")->whereNull("break_end")->get();

            //休憩：日を跨いだ時点で休憩終了打刻がされていないレコードは23:59:59で休憩終了打刻を自動で行う
            BreakTime::where("date", "=", "$yesterday")->whereNull("break_end")->update(["break_end" => "23:59:59"]);


            // 休憩：差分計算・登録
            foreach ($auto_update_break_times_records as $auto_update_break_times_record) {
            // 1.休憩開始時間
            $startTime = BreakTime::select("break_start")->where("user_id", "=", "$auto_update_break_times_record->user_id")->where("date", "=", "$yesterday")->orderBy('updated_at', 'desc')->first();
            $startTime_carbon = Carbon::parse($startTime["break_start"]);

            // 2.休憩終了時間
            $endTime = BreakTime::select("break_end")->where("user_id", "=", "$auto_update_break_times_record->user_id")->where("date", "=", "$yesterday")->orderBy('updated_at', 'desc')->first();
            $endTime_carbon = Carbon::parse($endTime["break_end"]);

            // 3.差分を計算
            $diffInSeconds = $startTime_carbon->diffInSeconds($endTime_carbon);

            $hours_raw = floor($diffInSeconds / 3600);
            $hours = sprintf("%02d", $hours_raw);

            $minutes_raw = floor(($diffInSeconds % 3600) / 60);
            $minutes = sprintf("%02d", $minutes_raw);

            $seconds_raw = $diffInSeconds % 60;
            $seconds = sprintf("%02d", $seconds_raw);

            $break_time = $hours . ":" . $minutes . ":" . $seconds;

            // 4.差分を登録
            BreakTime::where("item_id", "=", $auto_update_break_times_record->item_id)->whereNull("break_time")->update(['break_time' => "$break_time"]);

        }

//ここまでで休憩し続けている人の（break_timeテーブル上の）休憩時間の計算、新規出勤・新規休憩レコードの作成が完了。



//次に、退勤打刻をしていない人という観点で精算していく。（上記で処理した休憩し続けている人も含まれている）

    //（定義）退勤：日を跨いだ時点で退勤打刻がされていないレコード抽出（休憩の有無関係なく）
    $auto_update_items_records = Item::where("date", "=", "$yesterday")->whereNull("clock_out")->get();

            // その人たちの休憩の合計を計算・登録（休憩がなければ０で登録）
            foreach($auto_update_items_records as $auto_update_items_record){
        //break_time_totalを計算
            $break_time_total_raw = BreakTime::where("item_id", "=", "$auto_update_items_record->id")->selectRaw('SUM(TIME_TO_SEC(break_time)) as break_time_sum')->first();/* TIME_TO_SEC(時刻)で秒に直す*/

            $break_time_hours_raw = floor($break_time_total_raw["break_time_sum"] / 3600);
            $break_time_hours = sprintf("%02d", $break_time_hours_raw);

            $break_time_minutes_raw = floor(($break_time_total_raw["break_time_sum"] % 3600) / 60);
            $break_time_minutes = sprintf("%02d", $break_time_minutes_raw);

            $break_time_seconds_raw = ($break_time_total_raw["break_time_sum"] % 60);
            $break_time_seconds = sprintf("%02d", $break_time_seconds_raw);

            $break_time_total = $break_time_hours . ":" . $break_time_minutes . ":" . $break_time_seconds; /*Itemsテーブルに登録する合計休憩時間*/

        //break_time_totalを登録
            Item::where("id", "=", "$auto_update_items_record->id")->update(["break_time_total" => $break_time_total]);

        //23:59:59で退勤打刻
            Item::where("id","=",$auto_update_items_record->id)->update(["clock_out" => "23:59:59"]);

        //time_workedを計算
            // 1.開始時間
            $startTime = Item::select("clock_in")->where("user_id", "=", "$auto_update_items_record->user_id")->where("date", "=", $yesterday)->orderBy('created_at', 'desc')->first();
            $startTime_carbon = Carbon::parse($startTime["clock_in"]);/*carbonに変換*/
            // 2.終了時間
            $endTime = Item::select("clock_out")->where("user_id", "=", "$auto_update_items_record->user_id")->where("date", "=", $yesterday)->orderBy('updated_at', 'desc')->first();
            $endTime_carbon = Carbon::parse($endTime["clock_out"]);/*carbonに変換*/

            // 3.差分を計算
            $time_worked_diffInSeconds = $startTime_carbon->diffInSeconds($endTime_carbon);/*(A)休憩時間を考慮しない勤務時間(秒)*/


            //4.実勤務時間の計算
            $time_worked_diffInSeconds_int = (int)$time_worked_diffInSeconds;/*(A)をintに変換*/
            $break_time_total_raw_int = (int)$break_time_total_raw["break_time_sum"];/*(B)をintに変換*/
            $time_worked_raw = ($time_worked_diffInSeconds_int) - ($break_time_total_raw_int); /*(A)-(B)=実勤務時間（秒）*/

            $time_worked_hours_raw = floor($time_worked_raw / 3600);
            $time_worked_hours = sprintf("%02d", $time_worked_hours_raw);

            $time_worked_minutes_raw = floor(($time_worked_raw % 3600) / 60);
            $time_worked_minutes = sprintf("%02d", $time_worked_minutes_raw);

            $time_worked_seconds_raw = ($time_worked_raw % 60);
            $time_worked_seconds = sprintf("%02d", $time_worked_seconds_raw);

            $time_worked = $time_worked_hours . ":" . $time_worked_minutes . ":" . $time_worked_seconds; /*Itemsテーブルに登録する変数*/

        //time_workedを登録
            Item::where("id", "=", "$auto_update_items_record->id")->orderBy('updated_at', 'desc')->first()->update(["time_worked" => $time_worked]);

        //新規出勤レコードを作成
            Item::create([
                "user_id" => $auto_update_items_record->user_id,
                "date" => $today,
                "clock_in" => "00:00:00"
            ]);

        }

    //ここまででitemsテーブルのbreak_time_total、clock_out、time_workedの登録、新規出勤レコードの作成完了。

//最後に休憩し続けている人の休憩状態レコードを作成
        foreach($auto_update_break_times_records as $auto_update_break_times_record)
            BreakTime::create([
                "item_id" => Item::where("user_id", "=", $auto_update_break_times_record->user_id)->where("date", "=", $today)->where("clock_in", "=", "00:00:00")->first()->id,
                "user_id" => $auto_update_break_times_record->user_id,
                "date" => $today,
                "break_start" => "00:00:00"
        ]);

    }
}
