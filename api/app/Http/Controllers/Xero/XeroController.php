<?php

namespace App\Http\Controllers\Xero;

use App\Http\Controllers\Controller;
use App\Http\Requests\Xero\XeroAuthRequest;
use App\Services\InvoiceService;
use App\Services\LegalEntityService;
use App\Services\Xero\Auth\AuthService;
use App\Services\Xero\XeroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Webfox\Xero\Events\XeroAuthorized;
use \Webfox\Xero\OauthCredentialManager;
use \Webfox\Xero\Oauth2Provider;
use Webfox\Xero\Webhook;
use XeroAPI\XeroPHP\Api\IdentityApi;
use XeroAPI\XeroPHP\ApiException;

class XeroController extends Controller
{
    protected LegalEntityService $legalEntityService;

    public function __construct(LegalEntityService $legalEntityService)
    {
        $this->legalEntityService = $legalEntityService;
    }

    /**
     * @param string $legalEntityId
     * @param XeroAuthRequest $request
     * @param OauthCredentialManager $oauth
     * @param IdentityApi $identity
     * @param Oauth2Provider $provider
     *
     * @return JsonResponse
     *
     * @throws ApiException
     * @throws IdentityProviderException
     */
    public function auth(
        string $legalEntityId,
        XeroAuthRequest $request,
        OauthCredentialManager $oauth,
        IdentityApi $identity,
        Oauth2Provider $provider
    ): JsonResponse {
        $accessToken = $provider->getAccessToken('authorization_code', $request->only('code'));
        Log::info('ACCESS_CONNECTION: ' . json_encode($accessToken));

        $identity->getConfig()->setAccessToken((string) $accessToken->getToken());
        $tenantId = $identity->getConnections()[0]->getTenantId();

        $oauth->store($accessToken, $tenantId);

        Event::dispatch(new XeroAuthorized($oauth->getData()));

        $legalEntity = $this->legalEntityService->getSingle($legalEntityId);
        (new AuthService($legalEntity))->storeNewConnection($oauth);

        return response()->json([]);
    }

    public function invoiceWebhook(Request $request, Webhook $webhook, InvoiceService $invoiceService)
    {
        $payload = file_get_contents('php://input');
        $test    = base64_encode(hash_hmac('sha256', $payload, 'yx6sxXpz82i//JWdXOexPdc8QtYEK7dyVpta1Hu4SooYcI8irbaHwxjmyua8Dkv6waBZxFp2R/y+Qd8BU/ckCA==', true));

        if ($test != $request->header('x-xero-signature')) {
            return response(null, 401);
        }

      // A single webhook trigger can contain multiple events, so we must loop over them
        foreach ($webhook->getEvents() as $event) {
            if ($event->getEventType() === 'CREATE' && $event->getEventCategory() === 'INVOICE') {
                $invoiceService->createFromXero($event->getResource(), $event->getTenantId());
            }
            if ($event->getEventType() === 'UPDATE' && $event->getEventCategory() === 'INVOICE') {
                Storage::put('file.txt', 'payload='.$payload.' tenant='.$event->getTenantId().' header='.$request->header('x-xero-signature'));
                $invoiceService->updateFromXero($event->getResource(), $event->getTenantId());
            }
        }

        return response(null, 200);
    }

    /**
     * Get tax rates from Xero to link them to the tax rates in the app
     *
     * @param string $legalEntityId
     * @return JsonResponse
     * @throws \Exception
     */
    public function taxRates(string $legalEntityId): JsonResponse
    {
        $legalEntity = $this->legalEntityService->getSingle($legalEntityId);

        if ((new AuthService($legalEntity))->exists()) {
            $taxRates = (new XeroService($legalEntity))->getTaxRates();

            return response()->json($taxRates);
        }

        throw new UnprocessableEntityHttpException('Legal entity not linked to Xero.');
    }
}
