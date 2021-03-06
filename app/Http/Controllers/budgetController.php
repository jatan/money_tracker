<?php

namespace App\Http\Controllers;

use App\Budget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests;
use Carbon\Carbon;
use App\transaction;

class budgetController extends Controller
{

    public function update(){

        $loggedinUserID = Auth::user()->id;

        // Will update only visible bank accounts related transactions
	    $visibleBankAccounts = Auth::user()->visible_accounts()->toArray();
		$bank_accounts_ids = array();
		foreach ($visibleBankAccounts as $current_visibleBankAccount){
			$bank_accounts_ids[] = $current_visibleBankAccount['id'];
		}

        //Get Months and Years array based on if transaction occured for that month of the year
        $inScopeTransactionDates = transaction::where('pending', 0)
	                                          ->whereIn('bank_accounts_id', $bank_accounts_ids)
	                                          ->where('category', '!=', '')
	                                          ->select('date')
	                                          ->distinct()
	                                          ->get()
	                                          ->toArray();

        // dd($inScopeTransactionDates);
        $budgetUpdateScope = array();
        foreach ($inScopeTransactionDates as $current_Date) {
            $checkYear = substr($current_Date['date'], 0, 4);
            $checkMonth = substr($current_Date['date'], 5, 2);
            if (!array_key_exists($checkYear, $budgetUpdateScope)) {
                $budgetUpdateScope[$checkYear] = array();
                $budgetUpdateScope[$checkYear][] = $checkMonth;
            }
            else {
                if (!in_array($checkMonth, $budgetUpdateScope[$checkYear])) {
                    $budgetUpdateScope[$checkYear][] = $checkMonth;
                }
           }

       }

        foreach ($budgetUpdateScope as $year => $months) {
            foreach ($months as $c_month) {
	            $UnbudgetedAmountSum = 0;
            	// This will consider all transactions of given year and month.
                $sourceTransactions = transaction::where('pending', 0)
                                                ->where('date', '>=', $year.'-'.$c_month.'-01')
                                                ->where('date', '<=', $year.'-'.$c_month.'-31')
                        	                    ->whereIn('bank_accounts_id', $bank_accounts_ids)
                        	                    ->where('category', '!=', '')
                                                ->groupBy('category')
                                                ->selectRaw('category, sum(amount) as Total')
                                                ->get()             // Returns collection object
                                                ->toArray();        // Converts collection into Array
                // dump($sourceTransactions);
                $allBudgetsOfUser = Budget::all()->where('User_ID', $loggedinUserID)
                                                ->where('Month', intval($c_month))
                                                ->where('Year', $year);
                // dump($allBudgetsOfUser);
                foreach ($sourceTransactions as $current_sourceTransactions) {
	                $budgetFound = false;
	                foreach ($allBudgetsOfUser as $current_allBudgetsOfUser) {
		                if (!$budgetFound && $current_allBudgetsOfUser['Name'] == $current_sourceTransactions['category']) {
			                $budgetFound = true;
			                $current_allBudgetsOfUser['SpentValue'] = $current_sourceTransactions['Total'];
			                $current_allBudgetsOfUser->save();
		                }
	                }
	                if (!$budgetFound) {
		                // Budget is not created for current category
		                $UnbudgetedAmountSum = $UnbudgetedAmountSum + $current_sourceTransactions['Total'];
	                }
                }

                $findUnBudgeted = Budget::all()->where('User_ID', $loggedinUserID)
								                ->where('Month', intval($c_month))
								                ->where('Year', $year)
								                ->where('Name', "UnBudgeted")
								                ->first();

                if (isset($findUnBudgeted)){    // Un-Budgeted category is already created - just update Total
	                $findUnBudgeted['SetValue'] = $UnbudgetedAmountSum;
	                $findUnBudgeted->save();
                }
                else{   // Create Un-Budgeted category
	                $newBudget = new Budget();

	                $newBudget['User_ID'] = Auth::user()->id;
	                $newBudget['Name'] = 'UnBudgeted';
	                $newBudget['SetValue'] = $UnbudgetedAmountSum;
	                $newBudget['SpentValue'] = 0;
	                $newBudget['Month'] = $c_month;
	                $newBudget['Year'] = $year;
	                $newBudget->save();
                }
            }   //-- Complete Month iteration
        }   //-- Complete Year iteration
        // exit();
	    return (redirect::to('user/budget'));
    }

    public function index(){

	   $monthShortName = [
		   1 => 'Jan',
		   2 => 'Feb',
		   3 => 'Mar',
		   4 => 'Apr',
		   5 => 'May',
		   6 => 'Jun',
		   7 => 'Jul',
		   8 => 'Aug',
		   9 => 'Sep',
		   10 => 'Oct',
		   11 => 'Nov',
		   12 => 'Dec'
       ];
	    $monthFullName = [
		    1 => 'January',
		    2 => 'February',
		    3 => 'March',
		    4 => 'April',
		    5 => 'May',
		    6 => 'June',
		    7 => 'July',
		    8 => 'August',
		    9 => 'September',
		    10 => 'October',
		    11 => 'November',
		    12 => 'December'
	    ];

	    $now = Carbon::now();
	    $year = $now->year;
	    $month = $now->month;

	    $sortMonthShortName = [];
	    $sortMonthFullName = [];
	    for ($i = $month+12; $i > $month; $i--){
	    	$i > 12 ? $sortMonthShortName[$i-12] = $monthShortName[$i-12] : $sortMonthShortName[$i] = $monthShortName[$i];
	    	$i > 12 ? $sortMonthFullName[$i-12] = $monthFullName[$i-12] : $sortMonthFullName[$i] = $monthFullName[$i];
	    }

	    $reversed = array_reverse($sortMonthShortName, true);
	    $reversedFull = array_reverse($sortMonthFullName, true);
	    $allBudgets = Budget::select('User_ID', 'Name', 'SetValue', 'SpentValue', 'Month', 'Year')
		                    ->where([['Month', '>' , $month],
                                    ['Year', '=', $year-1],
                                    ['User_ID', Auth::user()->id]])
		                    ->orWhere([['Month', '<=' , $month],
                                      ['Year', '=', $year],
                                      ['User_ID', Auth::user()->id]])
		                    ->get()
	                        ->toArray();
	    $masterList = [];
	    foreach ($allBudgets as $budget){
	    	$masterList[$budget['Month']][] = $budget;
	    }

        return (view('budget.bu_index')->with([
        	                                    'CurrentYear' => $year,
	                                            'CurrentMonth' => $month,
        	                                    'budgetLists' => $masterList,
	                                            'monthFullName' => $reversedFull,
	                                            'monthShortName' => $reversed]));
    }

    public function createBudget(){

	    $newBudget = new Budget();

	    $newBudget['User_ID'] = Auth::user()->id;
	    $newBudget['Name'] = $_POST['category'];
	    $newBudget['SetValue'] = $_POST['Setamount'];
	    $newBudget['SpentValue'] = 0;
	    $newBudget['Month'] = $_POST['budgetForMonth'];
	    $newBudget['Year'] = $_POST['budgetForYear'];

	    $newBudget->save();

	    return (redirect::to('/update'));
    }

    public function updateBudget(){
        $userID = Auth::user()->id;

        $findBudget = Budget::where('User_ID', $userID)
                            ->where('Name', $_POST['category'])
	                        ->where('Month', $_POST['budgetForMonth'])
                            ->where('Year', $_POST['budgetForYear'])
                            ->first();
                            //->toArray();
        if (count($findBudget) > 0) {

            $findBudget['SetValue'] = $_POST['Setamount'];
            $findBudget->save();
        } else {

            $newBudget = new Budget();

            $newBudget['User_ID'] = Auth::user()->id;
            $newBudget['Name'] = $_POST['category'];
            $newBudget['SetValue'] = $_POST['Setamount'];
            $newBudget['SpentValue'] = 0;
	        $newBudget['Month'] = $_POST['budgetForMonth'];
	        $newBudget['Year'] = $_POST['budgetForYear'];

            $newBudget->save();

        }
        return (redirect::to('/update'));
    }

    public function deleteBudget_byId(){
	    $userID = Auth::user()->id;

	    $deletedRows = Budget::where('User_ID', $userID)
		                        ->where('Name', $_POST['Name'])
		                        ->delete();
        return (redirect::to('user/budget'));
    }
}
