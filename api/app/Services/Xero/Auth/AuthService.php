<?php

namespace App\Services\Xero\Auth;

use App\Contracts\Repositories\LegalEntityXeroConfigRepositoryInterface;
use App\Models\LegalEntity;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use \Webfox\Xero\OauthCredentialManager;
use \League\OAuth2\Client\Provider\GenericProvider;

class AuthService
{
    private ?LegalEntity $legalEntity;

    protected LegalEntityXeroConfigRepositoryInterface $legalEntityXeroConfigRepository;

    public function __construct(LegalEntity $legalEntity = null)
    {
        $this->legalEntity = $legalEntity;
        $this->legalEntityXeroConfigRepository = App::make(LegalEntityXeroConfigRepositoryInterface::class);
    }

    public function getAccessToken()
    {
        return $this->legalEntity->xeroConfig->xero_access_token ?? null;
    }
    public function getRefreshToken()
    {
        return $this->legalEntity->xeroConfig->xero_refresh_token ?? null;
    }

    public function getTenantId()
    {
        return $this->legalEntity->xeroConfig->xero_tenant_id ?? null;
    }

    public function exists()
    {
        if ($this->getAccessToken() && $this->getTenantId()) {
            return true;
        }
        return false;
    }

    public function storeNewConnection(OauthCredentialManager $oauthCredentialManager): void
    {
        $payload = [
          'xero_tenant_id' => $oauthCredentialManager->getTenantId(),
          'xero_access_token' => $oauthCredentialManager->getAccessToken(),
          'xero_refresh_token' => $oauthCredentialManager->getRefreshToken(),
          'xero_id_token' => $oauthCredentialManager->getData()['id_token'],
          'xero_expires' => Carbon::createFromTimestamp($oauthCredentialManager->getExpires()),
        ];
        $xeroConfig = $this->legalEntityXeroConfigRepository->create($payload);
        $this->legalEntity->legal_entity_xero_config_id = $xeroConfig->id;
        $this->legalEntity->save();
    }

    public function refreshToken(): string
    {
        $newAccessToken = $this->getProvider()->getAccessToken('refresh_token', [
          'grant_type' => 'refresh_token',
          'refresh_token' => $this->getRefreshToken()
        ]);

        $this->legalEntityXeroConfigRepository->update($this->legalEntity->legal_entity_xero_config_id, [
          'xero_access_token' => $newAccessToken->getToken(),
          'xero_refresh_token' => $newAccessToken->getRefreshToken(),
          'xero_id_token' => $newAccessToken->getValues()['id_token'],
          'xero_expires' => Carbon::createFromTimestamp($newAccessToken->getExpires()),
        ]);

        return $newAccessToken->getToken();
    }

    private function getProvider(): GenericProvider
    {
        $provider = new GenericProvider([
          'clientId'                => config('xero.oauth.client_id'),
          'clientSecret'            => config('xero.oauth.client_secret'),
          'redirectUri'             => config('xero.oauth.redirect_uri'),
          'urlAuthorize'            => config('xero.oauth.url_authorize'),
          'urlAccessToken'          => config('xero.oauth.url_access_token'),
          'urlResourceOwnerDetails' => config('xero.oauth.url_resource_owner_details')
        ]);
        return $provider;
    }

    public function getExpires()
    {
        return strtotime($this->legalEntity->xeroConfig->xero_expires);
    }

    public function getHasExpired(): bool
    {
        return time() >= $this->getExpires();
    }
}
