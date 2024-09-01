<?php

namespace App\Services\Xero;

use App\Models\Company;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Webfox\Xero\Oauth2Provider;
use Webfox\Xero\OauthCredentialManager;
use XeroAPI\XeroPHP\JWTClaims;

/**
 * Class EloquentEntityStore
 *
 * Custom implementation for OauthCredentialManager
 */
class EloquentEntityStore implements OauthCredentialManager
{
    /**
     * @var Company $company
     */
    private Company $company;

    /**
     * @var Oauth2Provider
     */
    private Oauth2Provider $oauthProvider;

    /**
     * EloquentEntityStore constructor.
     *
     * @param string         $companyId
     * @param Oauth2Provider $oauthProvider
     */
    public function __construct(
        string $companyId,
        Oauth2Provider $oauthProvider
    ) {
        $company = Company::where('id', $companyId)->first();

        $this->company       = $company;
        $this->oauthProvider = $oauthProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessToken(): string
    {
        return $this->company->xero_access_token;
    }

    /**
     * {@inheritDoc}
     */
    public function getRefreshToken(): string
    {
        return $this->company->xero_refresh_token;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthorizationUrl(): string
    {
        $redirectUrl = $this->oauthProvider->getAuthorizationUrl(['scope' => config('xero.oauth.scopes')]);
        $this->company->update(['xero_oauth2_state' => $this->oauthProvider->getState()]);

        return $redirectUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function getTenantId(): string
    {
        return $this->company->xero_tenant_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getExpires(): int
    {
        return $this->company->xero_expires->timestamp;
    }

    /**
     * {@inheritDoc}
     */
    public function getState(): string
    {
        return $this->company->xero_oauth2_state;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(): bool
    {
        return $this->company->exists &&
          $this->company->xero_tenant_id &&
          $this->company->xero_refresh_token &&
          $this->company->xero_access_token &&
          $this->company->xero_id_token &&
          $this->company->xero_expires;
    }

    /**
     * {@inheritDoc}
     */
    public function isExpired(): bool
    {
        return time() >= $this->getExpires();
    }

    /**
     * {@inheritDoc}
     */
    public function refresh(): void
    {
        $newAccessToken = $this->oauthProvider->getAccessToken('refresh_token', [
          'refresh_token' => $this->getRefreshToken(),
        ]);

        $this->company->update([
          'xero_access_token' => $newAccessToken
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function store(AccessTokenInterface $token, string $tenantId = null): void
    {
        $this->company->update([
          'xero_access_token'  => $token->getToken(),
          'xero_refresh_token' => $token->getRefreshToken(),
          'xero_id_token'      => $token->getValues()['id_token'],
          'xero_expires'       => Carbon::createFromTimestamp($token->getExpires()),
          'xero_tenant_id'     => $tenantId ?? $this->getTenantId()
        ]);
    }

    /**
     *  {@inheritDoc}
     */
    public function getUser(): ?array
    {
        try {
            $jwt = new JWTClaims();
            $jwt->setTokenId($this->data('id_token'));
            $decodedToken = $jwt->decode();

            return [
            'given_name'  => $decodedToken->getGivenName(),
            'family_name' => $decodedToken->getFamilyName(),
            'email'       => $decodedToken->getEmail(),
            'user_id'     => $decodedToken->getXeroUserId(),
            'username'    => $decodedToken->getPreferredUsername(),
            'session_id'  => $decodedToken->getGlobalSessionId()
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        if ($this->exists()) {
            return $this->company->toArray();
        }

        throw new \Exception('Xero oauth credentials are missing');
    }

    /**
     * @param  null $key
     *
     * @return mixed|null
     *
     * @throws Exception
     */
    protected function data($key = null)
    {
        if (!$this->exists()) {
            throw new \Exception('Xero oauth credentials are missing');
        }

        return empty($key) ? $this->company->toArray() : ($this->company->toArray()[$key] ?? null);
    }
}
