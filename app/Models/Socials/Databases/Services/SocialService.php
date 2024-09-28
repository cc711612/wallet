<?php

/**
 * @Author: Roy
 * @DateTime: 2022/6/21 上午 11:25
 */

namespace App\Models\Socials\Databases\Services;

use App\Concerns\Databases\Service;
use App\Models\Socials\Contracts\Constants\SocialType;
use App\Models\Socials\Databases\Entities\SocialEntity;
use Illuminate\Database\Eloquent\Model;

class SocialService extends Service
{
    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @Author: Roy
     * @DateTime: 2022/6/21 上午 11:27
     */
    protected function getEntity(): Model
    {
        // TODO: Implement getEntity() method.
        if (app()->has(SocialEntity::class) === false) {
            app()->singleton(SocialEntity::class);
        }

        return app(SocialEntity::class);
    }

    public function registerLine(array $socialProfiles)
    {
        $socialType = SocialType::SOCIAL_TYPE_LINE;
        $socialEntity = $this->getEntity();
        $socialEntity = $socialEntity->updateOrCreate(
            [
                'social_type' => $socialType,
                'social_type_value' => $socialProfiles['id'],
                'email' => $socialProfiles['email'],
            ],
            [
                'social_type' => $socialType,
                'social_type_value' => $socialProfiles['id'],
                'name' => $socialProfiles['name'],
                'email' => $socialProfiles['email'],
                'image' => $socialProfiles['avatar'],
                'token' => $socialProfiles['token'],
            ]
        );

        return $socialEntity;
    }

    public function getBySocialTypeAndSocialTypeValue(string $socialType, string $socialTypeValue)
    {
        $socialEntity = $this->getEntity();
        $socialEntity = $socialEntity
            ->with([
                'users',
            ])
            ->where('social_type', $socialType)
            ->where('social_type_value', $socialTypeValue)
            ->first();

        return $socialEntity;
    }

    /**
     * update or create
     * @param array $params
     * @return void
     */
    public function updateOrCreate($params)
    {
        $socialType = data_get($params, 'socialType');
        $socialTypeValue = data_get($params, 'socialTypeValue');
        /**
         * @var SocialEntity $socialEntity
         */
        $socialEntity = $this->getEntity();
        return $socialEntity->updateOrCreate(
            [
                'social_type' => $socialType,
                'social_type_value' => $socialTypeValue,
            ],
            [
                'social_type' => $socialType,
                'social_type_value' => $socialTypeValue,
                'name' => data_get($params, 'name'),
                'email' => data_get($params, 'email'),
                'image' => data_get($params, 'image'),
                'token' => data_get($params, 'token'),
            ]
        );
    }
}
