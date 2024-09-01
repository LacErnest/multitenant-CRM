<?php


namespace App\Services;


use App\Contracts\Repositories\ContactRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Contracts\Repositories\QuoteRepositoryInterface;
use App\DTO\Quotes\CreateProjectQuoteDTO;
use App\DTO\Quotes\CreateQuoteDTO;
use App\Enums\DownPaymentAmountType;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Http\Requests\Quote\CreateOrderFromQuoteRequest;
use App\Http\Requests\Quote\QuoteCreateForProjectRequest;
use App\Http\Requests\Quote\QuoteCreateRequest;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use App\Repositories\QuoteRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class QuoteService
{
    protected QuoteRepository $quote_repository;
    protected QuoteRepositoryInterface $quoteRepository;
    private LegalEntityRepositoryInterface $legalEntityRepository;
    private LegalEntitySettingRepositoryInterface $legalSettingRepository;
    private ContactRepositoryInterface $contactRepository;
    private ProjectRepositoryInterface $projectRepository;

    public function __construct(
        QuoteRepository $quote_repository,
        QuoteRepositoryInterface $quoteRepository,
        LegalEntityRepositoryInterface $legalEntityRepository,
        LegalEntitySettingRepositoryInterface $legalSettingRepository,
        ContactRepositoryInterface $contactRepository,
        ProjectRepositoryInterface $projectRepository
    ) {
        $this->quote_repository = $quote_repository;
        $this->quoteRepository = $quoteRepository;
        $this->legalEntityRepository = $legalEntityRepository;
        $this->legalSettingRepository = $legalSettingRepository;
        $this->contactRepository = $contactRepository;
        $this->projectRepository = $projectRepository;
    }

    public function createForProject(string $projectId, CreateProjectQuoteDTO $createProjectQuoteDTO): Quote
    {
        $legalEntity = $this->legalEntityRepository->firstById($createProjectQuoteDTO->legal_entity_id);
        $format = $this->legalSettingRepository->firstById($legalEntity->legal_entity_setting_id);
        $project = Project::findOrFail($projectId);

        if (!$createProjectQuoteDTO->vat_status) {
            $createProjectQuoteDTO->vat_status = VatStatus::default()->getIndex();
        }

        $quote = $this->quoteRepository->create(
            array_merge($createProjectQuoteDTO->toArray(), [
              'project_id' => $projectId,
              'number' => transformFormat($format->quote_number_format, $format->quote_number + 1),
              'status' => QuoteStatus::draft()->getIndex()
            ])
        );
        $this->legalSettingRepository->update($format->id, ['quote_number' => $format->quote_number + 1]);

        if ($createProjectQuoteDTO->contact_id && $quote->project->contact_id != $createProjectQuoteDTO->contact_id) {
            $this->projectRepository->update($projectId, ['contact_id' => $createProjectQuoteDTO->contact_id]);
        }

        return $quote;
    }

    public function create(CreateQuoteDTO $createQuoteDTO): Quote
    {
        if (!$contact = $this->contactRepository->firstById($createQuoteDTO->contact_id)) {
            throw new ModelNotFoundException();
        } elseif ($contact->customer === null) {
            throw new ModelNotFoundException();
        }
        if (!$createQuoteDTO->vat_status) {
            $createQuoteDTO->vat_status = VatStatus::default()->getIndex();
        }
        $project = $this->projectRepository->createProjectForQuote($createQuoteDTO->name, $contact, $createQuoteDTO->sales_person_id, $createQuoteDTO->second_sales_person_id);
        $legalEntity = $this->legalEntityRepository->firstById($createQuoteDTO->legal_entity_id);
        $format = $this->legalSettingRepository->firstById($legalEntity->legal_entity_setting_id);

        $quote = $this->quoteRepository->create(
            array_merge($createQuoteDTO->toArray(), [
                'project_id' => $project->id,
                'number' => transformFormat($format->quote_number_format, $format->quote_number + 1),
                'status' => QuoteStatus::draft()->getIndex(),
            ])
        );
        if ($createQuoteDTO->sales_person_id) {
            foreach ($createQuoteDTO->sales_person_id as $salesPersonId) {
                $salesPerson = User::find($salesPersonId);
                $salesPerson->quotes()->attach($quote);
            }
        }
        $this->legalSettingRepository->update($format->id, ['quote_number' => $format->quote_number + 1]);

        return $quote;
    }

    public function update(Quote $quote, BaseRequest $request)
    {
        $format = null;
        if ($request->input('status') == QuoteStatus::ordered()->getIndex()) {
            $legalEntity = $this->legalEntityRepository->firstById($quote->legal_entity_id);
            $format = $this->legalSettingRepository->firstById($legalEntity->legal_entity_setting_id);
            $this->legalSettingRepository->update($format->id, ['order_number' => $format->order_number + 1]);
        }
        $input = $request->validated();
        return $this->quote_repository->update($quote, $input, $format);
    }

    public function checkAuthorization(string $projectId): void
    {
        if (UserRole::isPm(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role)) {
            throw new UnauthorizedException();
        }
    }

    public function checkProjectDelivered(string $projectId): void
    {
        $project = Project::findOrFail($projectId);
        if ($project->order()->where('status', '>=', OrderStatus::delivered()->getIndex())->exists()) {
            throw new UnprocessableEntityHttpException(
                'Selected project has been delivered. No create, update, delete of items, price modifiers allowed.'
            );
        }
    }

    public static function getCommissionPercentage($baseCommission, float $gm):float
    {
        if ($gm >= 50) {
            $commissionPercent = 1;
        } elseif ($gm >= 40 && $gm <50) {
            $commissionPercent = .9;
        } elseif ($gm >= 30 && $gm < 40) {
            $commissionPercent = .5;
        } elseif ($gm >= 20 && $gm < 30) {
            $commissionPercent = .1;
        } else {
            $commissionPercent = 0;
        }

        return round($commissionPercent * $baseCommission, 2);
    }

    public static function calculateCommission($commissionPercentage, $totalPrice)
    {
        return round($totalPrice * $commissionPercentage / 100, 2);
    }

    public function uploadDocument(string $quoteId, $file)
    {
        $quote = $this->quoteRepository->firstById($quoteId);
        $mimetype = getDocumentMimeType($file);
        $quote->addMediaFromBase64($file)->usingFileName($quote->number . '.' . $mimetype)->toMediaCollection('document_quote');
    }

    public function deleteDocument(string $quoteId)
    {
        $quote = $this->quoteRepository->firstById($quoteId);

        if ($media = $quote->getMedia('document_quote')->first()) {
            $media->delete();
        }
    }
}
