<?php

namespace App\Http\Controllers\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContractorCashier;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DashboardController extends Controller
{
    public function index()
    {
        $page_name = 'dashboard/dashboard';
        $current_page = 'dashboard';
        $page_title = 'Dashboard';
        $cash_deposit = ContractorCashier::where('id', session('user_id'))->first();

        function unique_code($limit)
        {
            return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
        }

        $data = unique_code(9);
        $qrCode = QrCode::size(200)->generate($data);
        return view('backend/cashier/main', compact('page_name', 'current_page', 'page_title', 'cash_deposit', 'qrCode'));
    }
}
