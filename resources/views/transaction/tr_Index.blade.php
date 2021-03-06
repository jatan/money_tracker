@extends('main')

@section('css')
    <link rel="stylesheet" href="../css/trans-1.css">
@stop

@section('content')

        <div class="transactions-main">
            <div class="utility-buttons">
                <form class="form-inline" action="../run" method="get">
                    <!-- Add Transaction Button -->
                    <button style="float:left; margin:10px;" type="button" class="btn btn-default" data-toggle="modal" data-target="#AddTransaction"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>
                    <!-- Automatic Add random transaction -->
                    <input type="text" class="form-control" name="days" value="0" style="margin: 10px;">
                    <button type="submit" class="btn btn-success">RUN</button>
                    <!-- Search transaction table -->
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search Transactions..." style="margin: 10px; margin-left: 25px;">
                    <!-- Import transaction button -->
                    <button style="float:right; margin:10px;" type="button" class="btn btn-default" data-toggle="modal" data-target="#UploadFileModal"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span></button>
                </form>
            </div>
            <div id="table-container">
                <table id="maintable" class="table table-hover table-responsive">
                    <thead class="table__header-bg-color">
                    <tr>
                        <th>Merchant</th>
                        <th>Amount</th>
                        <th>Category</th>
                        <th>Account</th>
                        <th>Options</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $displayDate = '' ?>
                    <?php $now = \Carbon\Carbon::now() ?>
                    @foreach($transactions as $transaction)
                        <?php $transDate = \Carbon\Carbon::parse($transaction -> date) ?>
                        @if($displayDate != $transDate)
                            <?php $displayDate = $transDate ?>
                            <tr><td colspan="5" style="text-align: left; margin-left: 0px; border: none; background-color: #e6e6e6;">{{($displayDate->diffInDays($now)<1) ? "Today" : $displayDate->toDateString()}}</td>
                            </tr>
                        @endif
                        <tr style='color:{{($transaction->pending == 1) ? "blue" : "black"}};'>
                            <td>{{substr($transaction->name,0,60)}}</td>
                            <td class='{{($transaction->amount > 0) ? "amount_red" : "amount_green"}}'>{{ (-1.0)*($transaction->amount) }}</td>
                            <td>{{ json_decode($transaction -> category,1)[0] }}</td>
                            <td>{{$transaction ->bank_name}} - {{$transaction->b_name}}</td>
                            <td>
                                <form class="" action="transaction/delete/{{$transaction->id}}" method="get">
                                    <button class="btn btn-danger" style="float: right; margin-left: 5px;"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></button>
                                </form>
                                    <button class="btn btn-success editJSOperation" data-transID="{{$transaction->id}}" style="float: right;"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div id="bottom_anchor"></div>
            </div>
	        {{ $transactions->links() }}
        </div>
        <div id="AddTransaction" class="modal fade" role="dialog" style="display: none;">
	        <div class="modal-dialog">
		        <div class="modal-content">
			        <div class="modal-header">
				        <div class="modal-title">
					        <button type="button" class="btn close" data-dismiss="modal">×</button>
					        <h4>Add Transaction Details</h4>
				        </div>
			        </div>
			        <div class="modal-body">
				        {!! Form::open(array('id' => "register", 'method' => "post", 'url' => 'user/transaction/create')) !!}
				        {{ csrf_field() }}
				        <div id="">

					        <div class="form-inline">
						        <label for="transactionDate" style="margin-right: 20px;">DATE</label>
						        <input id="transactionDate" name="transactionDate" class="form-control" type="date" style="margin-right: 10px;">
						        <label for="Merchant" style="margin-right: 20px;">MERCHANT</label>
						        <input id="Merchant" name="Merchant" class="form-control" type="text" style="margin-right: 10px;">
						        <label for="Category" style="margin-right: 20px;">CATEGORY</label>
						        <select id="Category" name="Category" class="form-control" style="margin-right: 10px;">
							        <option value="Shops" selected>Shopping</option>
							        <option value="Food and Drink">Food & Drinks</option>
							        <option value="Gas and Fuel">Gas & Fuel</option>
                                    <option value="Bank Fees">Bank Fees</option>
                                    <option value="Cash Advance">Cash Advance</option>
                                    <option value="Community">Community</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="Interest">Interest</option>
                                    <option value="Payment">Payment</option>
                                    <option value="Recreation">Recreation</option>
                                    <option value="Service">Service</option>
                                    <option value="Tax">Tax</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Travel">Travel</option>
						        </select>
						        <label for="amount" style="margin-right: 20px;">Amount</label>
						        <input id="amount" name="amount" class="form-control" type="text" style="margin-right: 10px;">
                                <label for="bankAccount" style="margin-right: 20px;">BANK ACCT</label>
						        <select id="bankAccount" name="accountID" class="form-control" style="margin-right: 10px;">
							        @foreach($accounts as $bank_account)
                                        <option value="{{$bank_account['id']}}" selected>{{$bank_account['bank_name']}} - {{$bank_account['name']}}</option>
                                    @endforeach
						        </select>
					        </div>
					        <br>

					        <button type="submit" class="btn btn-success left">ADD</button>
					        <button type="button" class="btn btn-danger right" data-dismiss="modal">CANCEL</button>
				        </div>
				        {!! Form::close() !!}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
			        </div>
		        </div>
	        </div>
        </div>

        <div id="UploadFileModal" class="modal fade" role="dialog" style="display: none;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title">
                            <button type="button" class="btn close" data-dismiss="modal">X</button>
                            <h4>Import Mint Transactions</h4>
                        </div>
                    </div>
                    <div class="modal-body">
                        <form id="uploadFile" class="form-inline" enctype="multipart/form-data" method="post" action="transaction/import">
                            {{ csrf_field() }}
                            <input id="file" type="file" name="file" style="display: inline;"/>
                            <input type="submit" value="Upload" name="submit" style="display: inline; margin:10px;" class="btn btn-default uploadbutton" />
                        </form>
                        <div class="result" style="display: none">
                            <label for="MintAccounts" style="margin-right: 20px;">MINT ACCOUNTS</label>
                            <select name="MintAccounts" class="" style="margin-right: 10px;">
                            </select>
                            <br />
                            <label for="ExistingAccounts" style="margin-right: 20px;">CURRENT ACCOUNTS</label>
                            <select name="ExistingAccounts" class="" style="margin-right: 10px;">
                                @foreach($accounts as $account)
                                <option value="{{$account['bank_name']}} - {{$account['name']}}">{{$account['bank_name']}} - {{$account['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@stop

@section('js')
    <script src="../js/transaction.js"></script>
@stop
