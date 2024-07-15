<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;

class InvoiceFilter extends ApiFilter{
    protected $allowedParams = [
        "id"=> ['eq','gt','lt','lte','gte'],
        'customerId'=> ['eq'],
        'amount'=> ['eq'],
        'status'=> ['eq','ne'],
        'billedDate'=> ['eq'],
        'paidDate'=> ['eq']
    ];

    protected $columnMap = [
        'paidDate'=> 'paid_date',
        'billedDate'=>'billed_date',
        'customerId'=>'customer_id'
    ];

    
    
}