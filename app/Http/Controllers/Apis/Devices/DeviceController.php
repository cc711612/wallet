<?php
namespace App\Http\Controllers\Apis\Devices;

use App\Http\Controllers\ApiController;
use App\Http\Requests\DeviceDestroyRequest;
use App\Http\Requests\DeviceIndexRequest;
use App\Http\Requests\DeviceStoreRequest;
use App\Models\Devices\Databases\Services\DeviceService;

class DeviceController extends ApiController
{
    public function __construct(
        public DeviceService $deviceService
    ) {
    }

    public function index(DeviceIndexRequest $request)
    {
        
        return $this->response()->success(
            $this->deviceService->index($request->user_id, $request->wallet_user_id)
        );
    }

    public function store(DeviceStoreRequest $request)
    {
        $this->deviceService->updateOrCreate($request->all());

        return $this->response()->success();
    }

    public function destroy(DeviceDestroyRequest $request, $id)
    {
        $this->deviceService->deleteById(
            $id,
            $request->user_id,
            $request->wallet_user_id
        );

        return $this->response()->success();
    }
}
