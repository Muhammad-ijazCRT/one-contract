<?php

namespace App\Services;

use App\Models\ContactSetting;

class ContactSettingService
{
    public function get(): ContactSetting
    {
        return ContactSetting::query()->firstOrFail();
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function update(array $data): ContactSetting
    {
        $setting = ContactSetting::query()->firstOrFail();
        $setting->update($data);

        return $setting->fresh();
    }
}
