<?php

namespace App\Console\Commands\Scheduler;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Services\Xero\Auth\AuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class RefreshXeroAccessToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:refresh_xero_tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh all active xero access tokens';

    private LegalEntityRepositoryInterface $legalEntityRepository;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LegalEntityRepositoryInterface $legalEntityRepository)
    {
        parent::__construct();
        $this->legalEntityRepository = $legalEntityRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $legalEntities = $this->legalEntityRepository->getAll(['xeroConfig']);
        $legalEntities->each(function ($legalEntity) {
            if ($legalEntity->xeroConfig && $legalEntity->xeroConfig->xero_tenant_id) {
                try {
                    $this->authService = new AuthService($legalEntity);
                    if ($this->authService->exists()) {
                        if ($this->authService->getHasExpired()) {
                            DB::beginTransaction();
                            try {
                                DB::table('legal_entity_xero_configs')->where('id', $legalEntity->legal_entity_xero_config_id)->lockForUpdate();
                                $this->authService->refreshToken();
                                DB::commit();
                            } catch (\Exception $e) {
                                DB::rollBack();
                            }
                        }
                    }
                } catch (\Exception $exception) {
                    Log::error($exception->getMessage());
                }
            }
        });

        return 0;
    }
}
