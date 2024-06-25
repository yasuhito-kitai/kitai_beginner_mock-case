<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\BreakTime;
use App\Models\User;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;




class StampController extends Controller
{   // ホーム画面
    public function stamp()
    {   // ボタン活性・非活性
        
        $user = Auth::user();
      
        $id = $user->id;
        $today = Carbon::now()->format('Y-m-d');
        $now = Carbon::now()->format('h:i:s');

        $clock_in_status= Item::where("user_id", "=", $id)
                                ->where("date", "=", $today)
                                ->whereNotNull("clock_in")
                                ->whereNull("clock_out")
                                ->first(); /* true＝出勤している＝レコード取得 */

        $clock_out_status = Item::where("user_id", "=", $id)
                                ->where("date", "=", $today)
                                ->whereNotNull("clock_in")
                                ->whereNotNull("clock_out")
                                ->first(); /* true＝勤務後に退勤＝レコード取得 */

        $break_end_possible = BreakTime::where("user_id", "=", $id)
                                ->where("date", "=", $today)
                                ->whereNotNull("break_start")
                                ->whereNull("break_end")
                                ->first();/*true＝休憩中*/

        $break_end_stamped= BreakTime::where("user_id", "=", $id)
                                ->where("date", "=", $today)
                                ->whereNotNull("break_start")
                                ->whereNotNull("break_end")
                                ->first();/*true＝休憩終了*/

        if($clock_in_status !== null && $break_end_possible == null ) { /*nullでない（レコード取得している）＝出勤している＆休憩していない⇒勤務終了、休憩開始が表示される*/
            $disabled_clock_in = "disabled";
            $disabled_clock_out = "";
            $disabled_break_start = "";
            $disabled_break_end = "disabled";
            return view("stamp", compact('disabled_clock_in', 'disabled_clock_out', 'disabled_break_start', 'disabled_break_end'));

        }elseif($clock_in_status !== null && $break_end_possible !== null) { /*出勤している＆休憩している⇒休憩終了のみが表示される*/
            $disabled_clock_in = "disabled";
            $disabled_clock_out = "disabled";
            $disabled_break_start = "disabled";
            $disabled_break_end = "";
            return view("stamp", compact('disabled_clock_in', 'disabled_clock_out', 'disabled_break_start', 'disabled_break_end'));

        }elseif($clock_in_status == null || $clock_out_status !== null) { /*出勤前or退勤後⇒勤務開始のみが表示される*/
            $disabled_clock_in = "";
            $disabled_clock_out = "disabled";
            $disabled_break_start = "disabled";
            $disabled_break_end = "disabled";
            return view("stamp", compact('disabled_clock_in', 'disabled_clock_out', 'disabled_break_start', 'disabled_break_end'));


        }
    }

    // 勤務開始
    public function clock_in()
    {
        $user = Auth::user();
        Item::create([
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'clock_in' => Carbon::now()->format('H:i:s')
        ]);

        return redirect('/stamp');
    }

    // 勤務終了
    public function clock_out()
    {
        $user = Auth::user();
        $user_id = $user->id;
        $today = Carbon::now()->format('Y-m-d');
        $clock_out=Carbon::now()->format('H:i:s');
        Item::where("user_id","=", $user_id)->where("date","=",$today)->orderBy('created_at', 'desc')->first()->update(["clock_out"=>$clock_out]);/*勤務終了時間を登録*/

        // 差分計算
        // 1.開始日時
        $startTime = Item::select("clock_in")->where("user_id", "=", $user_id)->where("date", "=", $today)->orderBy('created_at', 'desc')->first();
        $startTime_carbon=Carbon::parse($startTime["clock_in"]);/*carbonに変換*/
        // 2.終了日時
        $endTime =Item::select("clock_out")->where("user_id", "=", $user_id)->where("date", "=", $today)->orderBy('created_at', 'desc')->first();
        $endTime_carbon = Carbon::parse($endTime["clock_out"]);/*carbonに変換*/

        // 3.差分を計算
        $time_worked_diffInSeconds = $startTime_carbon->diffInSeconds($endTime_carbon);/*(A)休憩時間を考慮しない勤務時間(秒)*/


        $update_subject_id=Item::select("id")->where("user_id", "=", $user_id)->where("date", "=", $today)->orderBy('updated_at', 'desc')->first();/*「ログイン中のユーザー」が「今日」「直近更新（勤務終了）」した主キーを持つレコードを勤務時間の計算対象レコードと考える*/

        $break_time_total_raw = BreakTime::where("item_id", "=", "$update_subject_id[id]")->selectRaw('SUM(TIME_TO_SEC(break_time)) as break_time_sum')->first();/*(B)合計休憩時間（秒） TIME_TO_SEC(時刻)で秒に直す*/


            $break_time_hours_raw = floor($break_time_total_raw["break_time_sum"] / 3600);
            $break_time_hours = sprintf("%02d", $break_time_hours_raw);

            $break_time_minutes_raw = floor(($break_time_total_raw["break_time_sum"] % 3600) / 60);
            $break_time_minutes = sprintf("%02d", $break_time_minutes_raw);

            $break_time_seconds_raw = ($break_time_total_raw["break_time_sum"] % 60);
            $break_time_seconds = sprintf("%02d", $break_time_seconds_raw);

            $break_time_total = $break_time_hours . ":" . $break_time_minutes . ":" . $break_time_seconds; /*Itemsテーブルに登録する合計休憩時間*/

        Item::where("user_id", "=", $user_id)->where("date", "=", $today)->orderBy('created_at', 'desc')->first()->update(["break_time_total" => $break_time_total]);/*合計休憩時間を登録*/
        

        $time_worked_diffInSeconds_int = (int)$time_worked_diffInSeconds;/*(A)をintに変換*/
        $break_time_total_raw_int = (int)$break_time_total_raw["break_time_sum"];/*(B)をintに変換*/
        $time_worked_raw = ($time_worked_diffInSeconds_int)-($break_time_total_raw_int); /*(A)-(B)=実勤務時間（秒）*/

            $time_worked_hours_raw = floor($time_worked_raw / 3600);
            $time_worked_hours = sprintf("%02d", $time_worked_hours_raw);

            $time_worked_minutes_raw = floor(($time_worked_raw % 3600) / 60);
            $time_worked_minutes = sprintf("%02d", $time_worked_minutes_raw);

            $time_worked_seconds_raw = ($time_worked_raw % 60);
            $time_worked_seconds = sprintf("%02d", $time_worked_seconds_raw);

            $time_worked = $time_worked_hours . ":" . $time_worked_minutes . ":" . $time_worked_seconds; /*Itemsテーブルに登録する変数*/

        Item::where("user_id", "=", $user_id)->where("date", "=", $today)->orderBy('created_at', 'desc')->first()->update(["time_worked" => $time_worked]);

  
    return redirect('/stamp');
    }

    // 休憩開始
    public function break_start()
    {
        $user = Auth::user();
        $user_id = $user->id;
        $today = Carbon::now()->format('Y-m-d');
        BreakTime::create([
            'item_id' => Item::where("user_id", "=", $user_id)->where("date", "=", $today)->whereNull("clock_out")->first()->id,
            'user_id' => $user->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'break_start' => Carbon::now()->format('H:i:s')
        ]);

        return redirect('/stamp');
    }

    // 休憩終了
    public function break_end()
    {
        $user = Auth::user();
        $user_id = $user->id;
        $today = Carbon::now()->format('Y-m-d');
        $break_end = Carbon::now()->format('H:i:s');
        BreakTime::where("user_id", "=", $user_id)->where("date", "=", $today)->orderBy('created_at', 'desc')->first()->update(["break_end" => $break_end]);

        // 差分計算
        // 1.開始時間
        $startTime = BreakTime::select("break_start")->where("user_id", "=", $user_id)->where("date", "=", $today)->orderBy('created_at', 'desc')->first();
        $startTime_carbon = Carbon::parse($startTime["break_start"]);
        // 2.終了時間
        $endTime = BreakTime::select("break_end")->where("user_id", "=", $user_id)->where("date", "=", $today)->orderBy('created_at', 'desc')->first();
        $endTime_carbon = Carbon::parse($endTime["break_end"]);

        // 3.差分を計算
        $diffInSeconds = $startTime_carbon->diffInSeconds($endTime_carbon);

        $hours_raw= floor($diffInSeconds / 3600);
        $hours=sprintf("%02d", $hours_raw);

        $minutes_raw = floor(($diffInSeconds % 3600) / 60);
        $minutes = sprintf("%02d", $minutes_raw);

        $seconds_raw = $diffInSeconds % 60;
        $seconds = sprintf("%02d", $seconds_raw);

        $break_time= $hours.":". $minutes.":". $seconds;

        // 3.差分を登録
        BreakTime::where("user_id", "=", $user_id)->where("date", "=", $today)->whereNull("break_time")->update(['break_time' => $break_time]);

        return redirect('/stamp');
    }

    // 日付ごとの勤怠一覧
    public function date()
    {
        //表示する今日の日付を取得
        $format_date = Carbon::today()->format('Y-m-d');

        //表示されている日付の勤怠レコードを取得
        $item_records = Item::with('user')->where("date","=", "$format_date")->paginate(5);

        return view('date',['display_date' => $format_date], ['item_records' => $item_records]);

    }

    // カレンダーで日付を選択（追加実装）
    public function calendar(Request $request)
    {
        $select_date = $request->all();

        //表示されている日付の勤怠レコードを取得
        $item_records = Item::with('user')->where("date", "=", "$select_date[select_date]")->paginate(5);

        return view('date', ['display_date' => $select_date["select_date"]], ['item_records' => $item_records]);
    }


    public function before_day(Request $request)
    {
        //表示されている日付－１日
        $display_date_string=$request->all();
        $display_date_carbon=Carbon::parse($display_date_string["display_date"]);
        $before_day_raw=$display_date_carbon->subDay();
        $before_day=$before_day_raw->format('Y-m-d');

        //表示されている日付の勤怠レコードを取得
        $item_records = Item::with('user')->where("date", "=", "$before_day")->paginate(5);

        return view('date', ['display_date' => $before_day], ['item_records' => $item_records]);
    }


    public function next_day(Request $request)
    {
         //表示されている日付＋１日
        $display_date_string = $request->all();
        $display_date_carbon = Carbon::parse($display_date_string["display_date"]);
        $next_day_raw = $display_date_carbon->addDay();
        $next_day = $next_day_raw->format('Y-m-d');

        //表示されている日付の勤怠レコードを取得
        $item_records = Item::with('user')->where("date", "=", "$next_day")->paginate(5);

        return view('date', ['display_date' => $next_day], ['item_records' => $item_records]);

    }

    // ユーザー一覧
    public function user()
    {
        //全ユーザー情報を取得
        $user_records = User::select("id","name", "email")->paginate(8);

        return view('users_list', compact('user_records'));
    }

        // ユーザー名（name）の検索
    public function user_search(Request $request)
    {
        $user_records = User::with('item')->KeywordSearch($request->keyword)->paginate(8);
      
        return view('users_list', compact('user_records'));
    }

    // ユーザーごと（月ごと）の勤怠一覧
    public function month(Request $request)
    {
        //表示する今日の日付を取得後、年-月の形にフォーマット
        $this_month = Carbon::today()->format('Y-m');

        //viewから送られてくる検索対象のユーザーidを取得
        $search_user_id = $request->only("id");

        //検索対象のユーザーidと一致する勤怠情報を全件取得
        $item_records = Item::where("user_id","=", "$search_user_id[id]")->where("date", "LIKE", "$this_month%")->get();
        
        //検索対象のユーザーidと一致するユーザー情報を取得、配列から値（名前,id）だけ取り出す
        $user_record = User::where("id","=", "$search_user_id[id]")->get();
        $display_name= $user_record[0]->name;
        $hidden_id = $user_record[0]->id;

        return view('month', compact('item_records', 'display_name', 'hidden_id'),["display_month"=>$this_month]);
        
    }

    public function before_month(Request $request)
    {
        //表示されている月－１月
        $display_month_string = $request->only("display_month");
        
        $display_month_carbon = Carbon::parse($display_month_string["display_month"]);
        $before_month_raw = $display_month_carbon->subMonth();
        $before_month = $before_month_raw->format('Y-m');

        $search_user_id=$request->only("hidden_id");
        
        $user_record=User::where("id","=","$search_user_id[hidden_id]")->get();
        $display_name= $user_record[0]->name;
        $hidden_id=$user_record[0]->id;
        //表示されている日付の勤怠レコードを取得
        $item_records = Item::where("user_id","=","$hidden_id")->where("date", "LIKE", "$before_month%")->get();

        return view('month', ['display_month' => $before_month], compact("display_name","hidden_id","item_records"));
    }

    public function next_month(Request $request)
    {
        //表示されている月＋１月
        $display_month_string = $request->only("display_month");

        $display_month_carbon = Carbon::parse($display_month_string["display_month"]);
        $next_month_raw = $display_month_carbon->addMonth();
        $next_month = $next_month_raw->format('Y-m');

        $search_user_id = $request->only("hidden_id");

        $user_record = User::where("id", "=", "$search_user_id[hidden_id]")->get();
        $display_name = $user_record[0]->name;
        $hidden_id = $user_record[0]->id;
        //表示されている日付の勤怠レコードを取得
        $item_records = Item::where("user_id", "=", "$hidden_id")->where("date", "LIKE", "$next_month%")->get();

        return view('month', ['display_month' => $next_month], compact("display_name", "hidden_id", "item_records"));
    }
}
