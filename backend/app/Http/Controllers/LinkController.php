<?php

namespace App\Http\Controllers;

use App\Http\Requests\Link\LinkRequest;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\Link;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LinkController extends Controller
{
    // 创建新的短链接
    public function store(LinkRequest $request)
    {
//        $request->validate([
//            'original_url' => 'required|url',
//        ]);
        $param = $request->validated();
        // 生成唯一的短码
        do {
            $shortCode = Str::random(6);
        } while (Link::where('short_code', $shortCode)->exists());

        // 创建链接
        $user = $request->user();
        Link::create([
            'user_id'      => $user->id,
            'original_url' => $param['original_url'],
            'short_code'   => $shortCode,
            'expires_at'   => Carbon::now()->addWeek(),
            'is_permanent' => false,
        ]);

        return response()->json(['short_url' => $shortCode]);
    }

    // 获取当前用户的所有链接
    public function index(Request $request)
    {
        $links = $request->user()->links()->orderByDesc('id')->get();

        return response()->json($links);
    }

    // 更新链接
    public function update(LinkRequest $request, int $id)
    {
        $link = $request->user()->links()->find($id);

        if (!$link) {
            return response()->json(['message' => '链接不存在'], 404);
        }

//        $request->validate([
//            'original_url' => 'required|url',
//        ]);
        $param = $request->validated();

        $link->update([
            'original_url' => $param['original_url'],
        ]);

        return response()->json(['message' => '链接已更新']);
    }

    // 删除链接
    public function destroy(Request $request, $id)
    {
        $link = $request->user()->links()->find($id);

        if (!$link) {
            return response()->json(['message' => '链接不存在'], 404);
        }

        $link->delete();

        return response()->json(['message' => '链接已删除']);
    }

    // 短链接重定向
    public function redirect(Request $request)
    {
        $link = Link::where('short_code', $request->get('code'))->first();

        if (!$link) {
            return response()->json(['message' => '链接不存在'], 404);
        }

        if (!$link->isValid()) {
            return response()->json(['message' => '链接已过期'], 410);
        }
        return response()->json(['original_url' => $link['original_url']]);
    }
}
