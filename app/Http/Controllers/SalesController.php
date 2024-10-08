<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CashDeposit;
use App\Models\DeliveryUser;
use App\Models\IncentiveDeposit;
use Illuminate\Support\Str;

class SalesController extends Controller
{

    public function listSales()
    {
        $page_name = 'sales/list';
        $current_page = 'sales';
        $page_title = 'Sales';

        $data = CashDeposit::
            join('delivery_user', 'cash_deposit.delivery_user_id', '=', 'delivery_user.id')
            ->select(
                'cash_deposit.*',
                'delivery_user.name as delivery_user_name',
                'delivery_user.email as delivery_user_email',
                'delivery_user.total_cash_collected as totalCashCollected',
                'delivery_user.total_cash_deposited as totalDepositAmount',
                'delivery_user.total_cash_pending as totalCashPending'
            )
            ->get();

        return view('backend/admin/main', compact('page_name', 'current_page', 'page_title', 'data'));
    }
    public function listRequest()
    {
        $page_name = 'sales/list_request';
        $current_page = 'deposit-request';
        $page_title = 'Deposit request';
        $data = CashDeposit::
            join('delivery_user', 'cash_deposit.delivery_user_id', '=', 'delivery_user.id')
            // ->join('orders', 'cash_deposit.order_id', '=', 'orders.id')
            // ->whereIn('cash_deposit.status', ['pending', 'verified'])
            ->select(
                'cash_deposit.*',
                'delivery_user.name as delivery_user_name',
                // 'orders.id as order_id'
            )
            ->get();

        // return view('admin.cash_deposit.index', compact('depositRequests'));

        return view('backend/admin/main', compact('page_name', 'current_page', 'page_title', 'data'));

    }

    public function editRequest($id)
    {
        // Fetch the specific deposit request using join
        $page_name = 'sales/edit_request';
        $current_page = 'edit-deposit-request';
        $page_title = 'Edit Deposit request';
        $depositRequest = CashDeposit::
            join('delivery_user', 'cash_deposit.delivery_user_id', '=', 'delivery_user.id')
            ->where('cash_deposit.id', $id)
            ->select(
                'cash_deposit.*',
                'delivery_user.name as delivery_user_name',
            )
            ->first();

        // if (!$depositRequest) {
        //     return redirect()->route('admin.cash_deposit.index')->with('error', 'Deposit request not found.');
        // }

        return view('backend/admin/main', compact('page_name', 'current_page', 'page_title', 'depositRequest'));
    }

    public function updateRequest(Request $request, $id)
    {
        $page_name = 'sales/list_request';
        $current_page = 'deposit-request';
        $page_title = 'Deposit request';

        $request->validate([
            'deposit_amount' => 'required|numeric|min:0',
            'status' => 'required|in:approved,rejected'
        ]);

        // Update the deposit request
        CashDeposit::
            where('id', $id)
            ->update([
                'deposit_amount' => $request->deposit_amount,
                'status' => $request->status,
                'updated_at' => now()
            ]);

        return redirect()->route('deposit-request.list')->with('success', 'Deposit request updated successfully.');

    }
    public function incentive()
    {
        $page_name = 'incentive/list';
        $current_page = 'incentive_list';
        $page_title = 'Incentive List';

        $deliveryUser = DeliveryUser::where('status', 'verified')->get();

        $list = IncentiveDeposit::select(
            'incentive_deposit.*',
            'delivery_user.name as delivery_user_name'
        )
            ->join('delivery_user', 'incentive_deposit.delivery_user_id', '=', 'delivery_user.id')
            ->where('incentive_deposit.status', '1')
            ->get();

        return view('backend/admin/main', compact('page_name', 'current_page', 'page_title', 'deliveryUser', 'list'));
    }

    public function incentivePay(Request $request)
    {
        $validated = $request->validate([
            'delivery_user_id' => 'required|exists:delivery_user,id',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $deliveryUserData = DeliveryUser::
            where('id', $request->delivery_user_id)
            ->first();

        if (!$deliveryUserData) {
            return redirect()->back()->with('error', 'Delivery user not found.');
        }

        if ($request->total_amount > $deliveryUserData->pending_incentive) {
            return redirect()->back()->with('error', 'Amount exceeds pending incentive.');
        }

        $data = [
            'delivery_user_id' => $request->delivery_user_id,
            'total_amount' => $deliveryUserData->pending_incentive,
            'paid_amount' => $request->total_amount,
            'pending_amount' => $deliveryUserData->pending_incentive - $request->total_amount,
        ];

        $response = IncentiveDeposit::create($data);

        $deliveryUserData->paid_incentive += $request->total_amount;
        $deliveryUserData->pending_incentive -= $request->total_amount;
        $deliveryUserData->save();
        if ($deliveryUserData->save()) {
            return redirect()->route('incentive.show')->with('success', 'Incentive deposited successfully.');
        } else {
            throw new \Exception('Failed to save delivery user updates.');
        }
    }

}
