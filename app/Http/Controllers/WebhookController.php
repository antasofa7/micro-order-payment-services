<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        $data = $request->all();

        $signatureKey = $data['signature_key'];
        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serveKey = env('MIDTRANS_SERVER_KEY');

        // create my signature key to use hash sha512
        $mySignatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serveKey);

        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        if ($signatureKey !== $mySignatureKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature key!'
            ], 400);
        }

        
        $realOrderId = explode('-', $orderId);
        
        $order = Order::find($realOrderId[0]);
        echo "<prev>".print_r($order->status, 1)."</prev>";

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order id not found!'
            ], 404);
        }

        if ($order->status === 'SUCCESS') {
            return response()->json([
                'status' => 'error',
                'message' => 'Operation not permitted'
            ], 405);
        }

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') {
                // TODO set transaction status on your database to 'success'
                // and response with 200 OK
                $order->status = 'SUCCESS';
            }
        } else if ($transactionStatus == 'settlement') {
            // TODO set transaction status on your database to 'success'
            // and response with 200 OK
            $order->status = 'SUCCESS';
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            // TODO set transaction status on your database to 'failure'
            // and response with 200 OK
            $order->status = 'FAILURE';
        } else if ($transactionStatus == 'pending') {
            // TODO set transaction status on your database to 'pending' / waiting payment
            // and response with 200 OK
            $order->status = 'PENDING';
        }

        $dataLog = [
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'order_id' => $realOrderId[0],
            'payment_type' => $type
        ];

        PaymentLog::create($dataLog);

        $order->save();

        if ($order->status === 'SUCCESS') {
            createPremiumAccess([
                'user_id' => $order->user_id,
                'course_id' => $order->course_id
            ]);
        }

        return response()->json(
            'Ok'
        );
    }
}
