<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Charge;
use Exception;
use Stripe\Webhook;

class PurchaseController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function purchase(Request $request, $id)
    {
        try {
            $amount = 1;
            $paymentIntent = PaymentIntent::create([
                'amount'                    => $amount * 100, // Stripe使用的是最小货币单位（比如美分）
                'currency'                  => 'usd',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Purchase::create([
                'user_id'          => $request->user()->id,
                'link_id'          => intval($id),
                'stripe_charge_id' => $paymentIntent->id,
                'amount'           => $amount,
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }


//        // 获取认证用户
//        $user = $request->user();
//
//        // 查找链接
//        $link = Link::where('id', $id)->where('user_id', $user->id)->first();
//
//        if (!$link) {
//            return response()->json(['message' => '链接不存在'], 404);
//        }
//
//        // 验证 Stripe Token
//        $request->validate([
//            'stripeToken' => 'required|string',
//        ]);
//
//        // 设置 Stripe 密钥
//        Stripe::setApiKey(env('STRIPE_SECRET'));
//
//        try {
//            // 创建 Stripe 付款
//            $charge = Charge::create([
//                'amount' => 1000, // 金额，单位为美分（$10.00）
//                'currency' => 'usd',
//                'source' => $request->stripeToken,
//                'description' => '购买永久链接',
//            ]);
//
//            // 更新链接为永久有效
//            $link->is_permanent = true;
//            $link->expires_at = null;
//            $link->save();
//
//            // 创建购买记录
//            Purchase::create([
//                'user_id' => $user->id,
//                'link_id' => $link->id,
//                'stripe_charge_id' => $charge->id,
//                'amount' => 10.00,
//            ]);
//
//            return response()->json(['message' => '购买成功，链接已永久有效'], 200);
//        } catch (Exception $e) {
//            return response()->json(['message' => '支付失败: ' . $e->getMessage()], 500);
//        }
    }

    public function paymentSuccess(Request $request)
    {
        // 设置Stripe密钥
//        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // 获取webhook签名
            $signature = $request->header('Stripe-Signature');

            // 验证webhook事件
            $event = Webhook::constructEvent(
                $request->getContent(),
                $signature,
                env('STRIPE_WEBHOOK_SECRET') // webhook密钥需要在配置文件中设置
            );

            // 处理支付成功事件
            if ($event->type === 'payment_intent.succeeded') {
                $paymentIntent = $event->data->object;

                // 从metadata中获取linkId
//                $linkId = $paymentIntent->metadata->linkId;
                $purchase = Purchase::where('stripe_charge_id', $paymentIntent->id)->first();
                if ($purchase) {
                    $purchase->status = 2;
                    $link = Link::find($purchase->link_id);
                    $link->is_permanent = true;
                    DB::transaction(function () use ($purchase, $link) {
                        $link->save();
                        $purchase->save();
                    });

                    return response()->json(['status' => 'success']);
                }
            }
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            // 记录错误日志
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    // 获取当前用户的所有链接
    public function status(Request $request, $id)
    {
        $purchase = Purchase::where('link_id', $id)->where('status', 2)->count();

        return response()->json(['status' => $purchase > 0 ? 'paid' : 'unpaid']);
    }
}
