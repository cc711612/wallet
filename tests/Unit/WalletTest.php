<?php

namespace Tests\Unit;

use Tests\TestCase;
use Database\Seeders\TestUserWalletSeeder;
use Tests\Traits\AuthTrait;
use Tests\Traits\ResponseTrait;
use Illuminate\Support\Arr;
use Tests\Traits\WalletTrait;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;

class WalletTest extends TestCase
{
    use WalletTrait, AuthTrait, ResponseTrait;

    private $member_data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member_account = config('testing.user.account');
        $this->member_password = config('testing.user.password');
    }

    public function test_get_wallet_list_fail()
    {
        $response = $this->getWalletList();
        $response->assertJson(function (AssertableJson $json) {
            $json
                ->where('status', false)
                ->where('code', HttpResponse::HTTP_BAD_REQUEST)
                ->has('message')
                ->etc();
        });
        $response->assertOk();
    }

    public function test_get_wallet_user_fail()
    {
        $response = $this->getWalletUser(Str::random(10));
        $response->assertJson(function (AssertableJson $json) {
            $json
                ->where('status', false)
                ->where('code', HttpResponse::HTTP_BAD_REQUEST)
                ->has('message')
                ->etc();
        });
        $response->assertOk();
    }

    public function test_store_wallet()
    {
        $response = $this->storeWallet();
        $response->assertJson(function (AssertableJson $json) {
            $json
                ->where('status', false)
                ->where('code', HttpResponse::HTTP_BAD_REQUEST)
                ->has('message')
                ->etc();
        });
        $response->assertOk();
    }

    public function test_update_wallet()
    {
        $ResponseList = $this->getWalletList();
        $ResponseList->assertOk();
        $wallet_id = 1;
        $response = $this->updateWallet($wallet_id);
        $response->assertJson(function (AssertableJson $json) {
            $json
                ->where('status', false)
                ->where('code', HttpResponse::HTTP_BAD_REQUEST)
                ->has('message')
                ->etc();
        });
        $response->assertOk();
    }
}
