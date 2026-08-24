<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LandingSettingController extends Controller
{
    public function edit()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $features = $this->padList($settings['web_features'] ?? null, 4);
        $steps = $this->padList($settings['web_steps'] ?? null, 4);

        return view('settings.landing', compact('settings', 'features', 'steps'));
    }

    public function update(Request $request)
    {
        $imageFields = ['web_hero_image', 'web_about_image', 'web_slider_1', 'web_slider_2', 'web_slider_3', 'web_slider_4'];

        $data = $request->except(array_merge(['_token'], $imageFields));

        foreach ($data as $key => $value) {
            if (is_null($value)) continue;
            if (str_ends_with($key, '_remove')) continue;
            if (!str_starts_with($key, 'web_') && $key !== 'brand_text_color') continue;
            if (is_array($value)) $value = json_encode(array_values($value));
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->saveImage($request, 'web_hero_image', 'hero');
        $this->saveImage($request, 'web_about_image', 'about');

        foreach (['1', '2', '3', '4'] as $i) {
            $this->saveSliderImage($request, $i);
        }

        return redirect()->back()->with('success', 'Web informativa actualizada correctamente.');
    }

    private function saveSliderImage(Request $request, string $i)
    {
        $field = 'web_slider_' . $i;

        if ($request->has('web_slider_remove_' . $i)) {
            $old = Setting::where('key', $field)->value('value');
            if ($old && Storage::disk('public')->exists('web/' . $old)) {
                Storage::disk('public')->delete('web/' . $old);
            }
            Setting::updateOrCreate(['key' => $field], ['value' => '']);
            return;
        }

        if (!$request->hasFile($field)) return;

        $request->validate([$field => 'image|max:4096']);

        $old = Setting::where('key', $field)->value('value');
        if ($old && Storage::disk('public')->exists('web/' . $old)) {
            Storage::disk('public')->delete('web/' . $old);
        }

        $file = $request->file($field);
        $filename = 'slider_' . $i . '_' . date('Ymd_His') . '.' . $file->getClientOriginalExtension();
        $file->storeAs('web', $filename, 'public');

        Setting::updateOrCreate(['key' => $field], ['value' => $filename]);
    }

    private function padList(?string $json, int $size): array
    {
        $list = $json ? json_decode($json, true) : [];
        if (!is_array($list)) $list = [];
        $item = ['icon' => '', 'title' => '', 'text' => ''];
        while (count($list) < $size) $list[] = $item;
        return array_slice($list, 0, $size);
    }

    private function saveImage(Request $request, string $field, string $name)
    {
        if (!$request->hasFile($field)) return;

        $request->validate([$field => 'image|max:4096']);

        $old = Setting::where('key', $field)->value('value');
        if ($old && Storage::disk('public')->exists('web/' . $old)) {
            Storage::disk('public')->delete('web/' . $old);
        }

        $file = $request->file($field);
        $filename = $name . '_' . date('Ymd_His') . '.' . $file->getClientOriginalExtension();
        $file->storeAs('web', $filename, 'public');

        Setting::updateOrCreate(['key' => $field], ['value' => $filename]);
    }
}
