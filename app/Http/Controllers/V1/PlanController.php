<?php


namespace App\Http\Controllers\V1;


use App\Constants\Currency;
use App\Libraries\ResourceUtils;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends V1Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = [];

        foreach (config('plans', []) as $plan => $definition)
        {
            $data[$plan] = $this->resolvePlan($plan);
        }

        return $this->respond($data);
    }

    public function show(Request $request, string $name, string $action = null): JsonResponse
    {
        return $this->respond($this->resolvePlan($name));
    }

    private function resolvePlan (string $plan)
    {
        $plans = config('plans');

        if (! isset($plans[$plan]))
            throw new ModelNotFoundException();

        $definedPlan = $plans[$plan];

        $returnedPlan = $definedPlan;
        unset($returnedPlan['resources']);

        foreach ($definedPlan['resources'] as $resource)
        {
            $resolved = ResourceUtils::resolve($resource['type'], $resource['id']);

            $returnedPlan['resources'][] = [
                'id' => (int) $resource['id'],
                'type' => $resource['type'],
                'price' => (float) $resolved->price,
                'currency' => Currency::USD // TODO: Make this multi currency aware, someday.
            ];
        }

        return $returnedPlan;
    }
}